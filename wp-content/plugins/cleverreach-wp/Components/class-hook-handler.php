<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components;

use CleverReach\WordPress\Components\BusinessLogicServices\Recipient_Service;
use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Repositories\Recipient_Repository;
use CleverReach\WordPress\Components\Utility\Database;
use CleverReach\WordPress\Components\Utility\Helper;
use CleverReach\WordPress\Components\Utility\Initializer;
use CleverReach\WordPress\Components\Utility\Task_Queue;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Tag;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Recipients;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\AuthProxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\FilterSyncTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\RecipientDeactivateSyncTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\RecipientSyncTask;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException;
use CleverReach\WordPress\Plugin;

/**
 * Class Hook_Handler
 *
 * @package CleverReach\WordPress\Components
 */
class Hook_Handler {

	/**
	 * Recipient repository
	 *
	 * @var Recipient_Repository
	 */
	private $recipient_repository;

	/**
	 * Proxy service
	 *
	 * @var Proxy
	 */
	private $proxy;

	/**
	 * Config service
	 *
	 * @var Config_Service
	 */
	private $config_service;

	/**
	 * Register all hooks
	 */
	public function register_hooks() {
		if ( !$this->get_config_service()->is_recipient_sync_enabled() ) {
			return false;
		}

		if ( is_multisite() ) {
			add_action( 'add_user_to_blog', array( $this, 'cleverreach_add_user' ), 100, 1 );
			add_action( 'profile_update', array( $this, 'cleverreach_update_multisite_user' ), 100, 2 );
			add_action( 'remove_user_from_blog', array( $this, 'cleverreach_delete_user' ), 100, 1 );
			add_action( 'grant_super_admin', array( $this, 'cleverreach_add_super_admin_multisite' ), 100, 1 );
			add_action( 'revoke_super_admin', array( $this, 'cleverreach_revoke_super_admin_multisite' ), 100, 1 );
			add_action( 'delete_blog', array( $this, 'cleverreach_delete_site' ), 100, 1 );
		} else {
			add_action( 'user_register', array( $this, 'cleverreach_add_user' ), 100, 1 );
			add_action( 'profile_update', array( $this, 'cleverreach_update_user' ), 100, 2 );
			add_action( 'delete_user', array( $this, 'cleverreach_delete_user' ), 100, 1 );
		}

		add_action( 'ure_user_permissions_update', array( $this, 'cleverreach_add_user_role' ), 100, 1 );
		add_action( 'set_user_role', array( $this, 'cleverreach_add_user_role' ), 100, 2 );
		add_action( 'updated_option', array( $this, 'cleverreach_options_hook' ), 100, 3 );

		return true;
	}

	/**
	 * Hook handler for update user
	 *
	 * @param int $user_id User id.
	 *
	 * @throws QueueStorageUnavailableException Queue storage unavailable exception.
	 * @throws RepositoryClassException
	 */
	public function cleverreach_add_user( $user_id ) {
		Initializer::register();
		if ( ! Helper::is_plugin_enabled() ) {
			return;
		}

		$this->save_extra_register_fields( $user_id );

		if ( ! $this->is_initial_sync_queued() ) {
			return;
		}

		if ( ! is_super_admin( $user_id ) ) {
			Logger::logInfo( 'User change event detected. User id: ' . $user_id, 'Integration' );
			Task_Queue::enqueue( new RecipientSyncTask( array( Recipient_Service::USER_ID_PREFIX . $user_id ) ) );
		}
	}

	/**
	 * Hook handler for update user
	 *
	 * @param int   $user_id       User id.
	 * @param array $old_user_data Old user data.
	 *
	 * @throws QueueStorageUnavailableException Queue storage unavailable exception.
	 * @throws InvalidConfigurationException Invalid configuration exception.
	 * @throws HttpAuthenticationException Http authentication exception.
	 * @throws HttpCommunicationException Http communication exception.
	 * @throws RefreshTokenExpiredException Refresh token expired exception
	 * @throws RepositoryClassException
	 */
	public function cleverreach_update_user( $user_id, $old_user_data ) {
		Initializer::register();
		if ( ! Helper::is_plugin_enabled() ) {
			return;
		}

		$this->save_extra_register_fields( $user_id );

		if ( ! $this->is_initial_sync_queued() ) {
			return;
		}

		if ( is_super_admin( $user_id ) ) {
			if ( ! is_multisite() ) {
				$this->cleverreach_add_super_admin( $user_id );
			}

			return;
		}

		$new_user_data = $this->get_recipient_repository()->get_user_data_by_id( $user_id );
		if ( ! empty( $old_user_data->user_email ) && $old_user_data->user_email !== $new_user_data->user_email ) {
			Task_Queue::enqueue( new RecipientDeactivateSyncTask( array( $old_user_data->user_email ) ) );
		}

		Logger::logInfo( 'User change event detected. User id: ' . $user_id, 'Integration' );
		Task_Queue::enqueue( new RecipientSyncTask( array( Recipient_Service::USER_ID_PREFIX . $user_id ) ) );
	}

	/**
	 * Hook handler for update user on multisite
	 *
	 * @param int       $user_id User id.
	 * @param /stdClass $old_user_data Old user data.
	 */
	public function cleverreach_update_multisite_user( $user_id, $old_user_data ) {
		$this->handle_multisite_users( 'cleverreach_update_user', $user_id, $old_user_data );
	}

	/**
	 * Hook handler for deleting user
	 *
	 * @param int $user_id User id.
	 *
	 * @throws QueueStorageUnavailableException Queue storage unavailable exception.
	 * @throws RepositoryClassException
	 */
	public function cleverreach_delete_user( $user_id ) {
		Initializer::register();
		if ( ! Helper::is_plugin_enabled() || ! $this->is_initial_sync_queued() || is_super_admin( $user_id ) ) {
			return;
		}

		$user = $this->get_recipient_repository()->get_user_data_by_id( $user_id );

		if ( ! empty( $user->user_email ) ) {
			Logger::logInfo( 'User role change event detected. User id: ' . $user_id, 'Integration' );
			Task_Queue::enqueue( new RecipientDeactivateSyncTask( array( $user->user_email ) ) );
		}
	}

	/**
	 * Hook handler for adding new role on single site user
	 *
	 * @param int $user_id User id.
	 * @throws QueueStorageUnavailableException Queue storage unavailable exception.
	 */
	public function cleverreach_add_user_role( $user_id ) {
		if ( ! Helper::is_plugin_enabled() || ! $this->is_initial_sync_queued() || is_super_admin( $user_id ) ) {
			return;
		}

		Logger::logInfo( 'Customer role changed event detected.' );
		Task_Queue::enqueue( new RecipientSyncTask( array( Recipient_Service::USER_ID_PREFIX . $user_id ) ) );
	}

	/**
	 * Hook handler for granting user super admin
	 *
	 * @param int $user_id User id.
	 * @throws QueueStorageUnavailableException Queue storage unavailable exception.
	 * @throws InvalidConfigurationException Invalid configuration exception.
	 * @throws HttpAuthenticationException Http authentication exception.
	 * @throws HttpCommunicationException Http communication exception.
	 * @throws RefreshTokenExpiredException Refresh token expired exception.
	 */
	public function cleverreach_add_super_admin( $user_id ) {
		if ( ! Helper::is_plugin_enabled() || ! $this->is_initial_sync_queued() ) {
			return;
		}

		$user = $this->get_recipient_repository()->get_user_data_by_id( $user_id );

		try {
			$recipient = $this->get_proxy()->getRecipient( $this->get_config_service()->getIntegrationId(), $user->user_email );
		} catch ( HttpRequestException $ex ) {
			$recipient = null;
		}

		if ( ! empty( $recipient ) ) {
			Logger::logInfo( 'User change to super admin. User id: ' . $user_id, 'Integration' );
			Task_Queue::enqueue( new RecipientDeactivateSyncTask( array( $user->user_email ) ) );
		}
	}

	/**
	 * Hook handler for granting user super admin on multisite user
	 *
	 * @param int $user_id User id.
	 */
	public function cleverreach_add_super_admin_multisite( $user_id ) {
		$this->handle_multisite_users( 'cleverreach_add_super_admin', $user_id );
	}

	/**
	 * Hook handler for revoking user super admin
	 *
	 * @param int $user_id User id.
	 * @throws QueueStorageUnavailableException Queue storage unavailable exception.
	 */
	public function cleverreach_revoke_super_admin( $user_id ) {
		if ( ! Helper::is_plugin_enabled() || ! $this->is_initial_sync_queued() ) {
			return;
		}

		Logger::logInfo( 'User change revoke from super admin. User id: ' . $user_id, 'Integration' );
		Task_Queue::enqueue( new RecipientSyncTask( array( Recipient_Service::USER_ID_PREFIX . $user_id ) ) );
	}

	/**
	 * Hook handler for revoking user super admine on multisite user
	 *
	 * @param int $user_id User id.
	 */
	public function cleverreach_revoke_super_admin_multisite( $user_id ) {
		$this->handle_multisite_users( 'cleverreach_revoke_super_admin', $user_id );
	}

	/**
	 * Hook handler for deleting site
	 *
	 * @param int $blog_id Site id.
	 */
	public function cleverreach_delete_site( $blog_id ) {
		$site_for_uninstall = get_site( $blog_id );
		$this->cleverreach_uninstall( array( $site_for_uninstall ) );
	}

	/**
	 * Plugin uninstall method
	 *
	 * @param array $sites List of sites for uninstall.
	 */
	public function cleverreach_uninstall( $sites = array() ) {
		global $wpdb;
		$installer = new Database( $wpdb );
		delete_site_option(Plugin::UPDATE_OPTION_CHECKED_KEY);
		if ( is_multisite() ) {
			if ( empty( $sites ) ) {
				$sites = get_sites();
			}

			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				$this->uninstall_handler();
				$installer->uninstall();
				restore_current_blog();
			}
		} else {
			$this->uninstall_handler();
			$installer->uninstall();
		}
	}

	/**
	 * Hook handler for update site and role names
	 *
	 * @param array|string $option    Option name.
	 * @param array|string $old_value Option old value.
	 * @param array|string $new_value Option new value.
	 *
	 * @throws QueueStorageUnavailableException Queue storage unavailable exception.
	 * @throws RepositoryClassException
	 */
	public function cleverreach_options_hook( $option, $old_value, $new_value ) {
		Initializer::register();
		if ( ! Helper::is_plugin_enabled() || ! $this->is_initial_sync_queued() || ! is_string( $option ) ) {
			return;
		}

		$site_name_option_name = 'blogname';
		$role_option_name      = Database::table( Database::ROLES_TABLE );

		if ( $site_name_option_name === $option ) {
			$this->handle_site_hook( $old_value, $new_value );
		} elseif ( $role_option_name === $option ) {
			$this->handle_role_hook( $old_value, $new_value );
		}
	}

	/**
	 * Saves newsletter subscriber field value for user
	 *
	 * @param int $user_id User id.
	 */
	private function save_extra_register_fields( $user_id ) {
		$value = filter_input( INPUT_POST, 'cr_newsletter_status' );
		$this->get_recipient_repository()->update_users_newsletter_field( array( $user_id ), (int) $value );
	}

	/**
	 * Handle site hook
	 *
	 * @param string $old_site_name Old site name.
	 * @param string $new_site_name New site name.
	 *
	 * @throws QueueStorageUnavailableException Queue storage unavailable exception.
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	private function handle_site_hook( $old_site_name, $new_site_name ) {
		if ( $old_site_name !== $new_site_name ) {
			/** @var Recipient_Service $recipient_service */
			$recipient_service = ServiceRegister::getService( Recipients::CLASS_NAME );
			$user_ids = $recipient_service->getAllRecipientsIds();

			$delete_tag_collection = new TagCollection();
			$tag                   = new Tag( $old_site_name, Recipient_Service::TAG_TYPE_SITE );
			$delete_tag_collection->addTag( $tag );

			Logger::logInfo( "Site name change event detected. Name changed from $old_site_name to $new_site_name.", 'Integration' );
			Task_Queue::enqueue( new FilterSyncTask() );
			Task_Queue::enqueue( new RecipientSyncTask( $user_ids, $delete_tag_collection ) );
		}
	}

	/**
	 * Handle role hook
	 *
	 * @param array $old_roles Array of old roles.
	 * @param array $new_roles Array of new roles.
	 * @throws QueueStorageUnavailableException Queue storage unavailable exception.
	 */
	private function handle_role_hook( $old_roles, $new_roles ) {
		Logger::logInfo( 'Role changed event detected.' );
		$old_role_keys = array_keys( $old_roles );
		$new_role_keys = array_keys( $new_roles );

		$changed_role_keys     = array();
		$delete_tag_collection = new TagCollection();

		// Check if there are new roles added.
		$added_roles = array_diff_assoc( $new_role_keys, $old_role_keys );

		// Check if there are roles deleted.
		$deleted_roles = array_diff_assoc( $old_role_keys, $new_role_keys );
		if ( ! empty( $deleted_roles ) ) {
			$changed_role_keys = array_merge( array_values( $deleted_roles ) );

			foreach ( $deleted_roles as $deleted_role ) {
				$tag = new Tag( $old_roles[ $deleted_role ]['name'], Recipient_Service::TAG_TYPE_ROLE );
				$delete_tag_collection->addTag( $tag );
			}
		}

		// Check if there are roles updated by name.
		foreach ( $old_roles as $old_role_key => $old_role_details ) {
			if ( ! empty( $new_roles[ $old_role_key ] ) && $old_role_details['name'] !== $new_roles[ $old_role_key ]['name'] ) {
				$changed_role_keys[] = $old_role_key;
				$tag                 = new Tag( $old_role_details['name'], Recipient_Service::TAG_TYPE_ROLE );
				$delete_tag_collection->addTag( $tag );
			}
		}

		if ( ! empty( $added_roles ) || ! empty( $changed_role_keys ) ) {
			Task_Queue::enqueue( new FilterSyncTask() );
		}

		if ( ! empty( $changed_role_keys ) ) {
			$users_ids_for_update = $this->get_recipient_repository()->get_user_ids_for_roles( $changed_role_keys );
			foreach ( $users_ids_for_update as $key => $value ) {
				$users_ids_for_update[ $key ] = Recipient_Service::USER_ID_PREFIX . $value;
			}

			if ( ! empty( $users_ids_for_update ) ) {
				Task_Queue::enqueue( new RecipientSyncTask( $users_ids_for_update, $delete_tag_collection ) );
			}
		}
	}

	/**
	 * Handle multisite users
	 *
	 * @param string $function Function to call.
	 * @param int    $user_id User id.
	 * @param array  $user_data User data.
	 */
	private function handle_multisite_users( $function, $user_id, $user_data = array() ) {
		$sites = get_sites();
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			if ( is_user_member_of_blog( $user_id ) ) {
				if ( empty( $user_data ) ) {
					$this->{$function}( $user_id );
				} else {
					$this->{$function}( $user_id, $user_data );
				}
			}

			restore_current_blog();
		}
	}

	/**
	 * Check whether initial sync is queued
	 *
	 * @return bool
	 */
	private function is_initial_sync_queued() {
		if ( ! Helper::is_plugin_initialized() ) {
			return false;
		}

		$task_queue_storage = ServiceRegister::getService( TaskQueueStorage::CLASS_NAME );
		$initial_sync_task  = $task_queue_storage->findLatestByType( 'Initial_Sync_Task' );

		return ! empty( $initial_sync_task );
	}

	/**
	 * Handles app uninstall
	 */
	private function uninstall_handler() {
		try {
			$this->get_proxy()->deleteProductSearchEndpoint( $this->get_config_service()->getProductSearchContentId() );
		} catch ( \Exception $e ) {
			Logger::logError( 'Could not delete article search endpoint because: ' . $e->getMessage(), 'Integration' );
		}

		try {
			$this->get_proxy()->deleteReceiverEvent();
		} catch ( \Exception $e ) {
			Logger::logError( 'Could not delete receiver event because: ' . $e->getMessage(), 'Integration' );
		}

		try {
			$this->get_proxy()->deleteFormEvent();
		} catch ( \Exception $e ) {
			Logger::logError( 'Could not delete form event because: ' . $e->getMessage(), 'Integration' );
		}

		/** @var AuthProxy $auth_proxy */
		$auth_proxy = ServiceRegister::getService( AuthProxy::CLASS_NAME );
		$auth_proxy->revokeOAuth();
	}

	/**
	 * Gets proxy
	 *
	 * @return Proxy
	 */
	private function get_proxy() {
		if ( null === $this->proxy ) {
			$this->proxy = ServiceRegister::getService( ProxyInterface::CLASS_NAME );
		}

		return $this->proxy;
	}

	/**
	 * Gets config service
	 *
	 * @return Config_Service
	 */
	private function get_config_service() {
		if ( null === $this->config_service ) {
			$this->config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
		}

		return $this->config_service;
	}

	/**
	 * Gets recipient repository
	 *
	 * @return Recipient_Repository
	 */
	private function get_recipient_repository() {
		if ( null === $this->recipient_repository ) {
			$this->recipient_repository = new Recipient_Repository();
		}

		return $this->recipient_repository;
	}
}
