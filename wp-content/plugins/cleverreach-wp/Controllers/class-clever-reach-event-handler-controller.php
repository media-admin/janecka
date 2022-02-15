<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Controllers;

use CleverReach\WordPress\Components\BusinessLogicServices\Recipient_Service;
use CleverReach\WordPress\Components\Entities\Contact;
use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Repositories\Base_Repository;
use CleverReach\WordPress\Components\Repositories\Recipient_Repository;
use CleverReach\WordPress\Components\Utility\Task_Queue;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Recipient;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Proxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\FormCacheSyncTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\RecipientSyncTask;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;

/**
 * Class Clever_Reach_Event_Handler_Controller
 *
 * @package CleverReach\WooCommerce\Controllers
 */
class Clever_Reach_Event_Handler_Controller extends Clever_Reach_Base_Controller {

	/**
	 * List of allowed receiver events.
	 *
	 * @var array
	 */
	static private $allowed_receiver_events = array(
		'receiver.subscribed',
		'receiver.unsubscribed',
	);

	/**
	 * List of allowed form events.
	 *
	 * @var array
	 */
	static private $allowed_form_events = array(
		'form.created',
		'form.updated',
		'form.deleted',
	);

	/**
	 * Configuration service
	 *
	 * @var Config_Service
	 */
	private $config_service;

	/**
	 * Recipient repository
	 *
	 * @var Recipient_Repository */
	private $recipient_repository;

	/**
	 * Contact repository.
	 *
	 * @var Base_Repository */
	private $contact_repository;

	/**
	 * Clever_Reach_Event_Handler_Controller constructor
	 */
	public function __construct() {
		$this->is_internal = false;
	}

	/**
	 * Event handle endpoint
	 *
	 * @throws QueueStorageUnavailableException When we are unable to enqueue task.
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	public function handle() {
		if ( $this->is_post() ) {
			$this->handle_event();
		} else {
			$this->confirm_handler();
		}
	}

	/**
	 * Handles request for validating webhook registration
	 */
	public function confirm_handler() {
		$secret = $this->get_param( 'secret' );

		if ( null !== $secret ) {
			$verification_token = $this->get_config_service()->getCrEventHandlerVerificationToken();
			status_header( 200 );
			echo esc_html( $verification_token . ' ' . $secret );
		} else {
			status_header( 400 );
		}

		exit();
	}

	/**
	 * Handles event.
	 *
	 * @throws QueueStorageUnavailableException When we are unable to enqueue task.
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	private function handle_event() {
		$call_token   = isset( $_SERVER[ 'HTTP_X_CR_CALLTOKEN' ] ) ? $_SERVER[ 'HTTP_X_CR_CALLTOKEN' ] : null;
		$valid_tokens = array(
			$this->get_config_service()->getCrEventHandlerCallToken(),
			$this->get_config_service()->getCrFormEventHandlerCallToken(),
		);
		if ( null === $call_token || ! in_array( $call_token, $valid_tokens, true ) ) {
			$this->die_with_status( 401 );
		}

		$body = json_decode( file_get_contents( 'php://input' ), true );

		if ( $this->get_config_service()->getCrEventHandlerCallToken() === $call_token ) {
			$this->handle_receiver_event( $body );
		} else {
			$this->handle_form_event( $body );
		}

		$this->die_with_status( 200 );
	}

	/**
	 * Handles receiver event.
	 *
	 * @param array $body
	 *
	 * @throws QueueStorageUnavailableException
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	private function handle_receiver_event( $body ) {
		$is_event_type_valid    = isset( $body[ 'event' ] ) && in_array( $body[ 'event' ], self::$allowed_receiver_events, true );
		$is_event_payload_valid = isset( $body[ 'payload' ][ 'group_id' ], $body[ 'payload' ][ 'pool_id' ] );
		$should_handle_event    = $this->get_config_service()->is_recipient_sync_enabled()
		                          || $this->get_config_service()->is_integration_recipient_sync_enabled( 'CF7' );

		if ( ! $is_event_type_valid || ! $is_event_payload_valid ) {
			$this->die_with_status( 400 );
		}

		if ( ! $should_handle_event
		     || (int) $body[ 'payload' ][ 'group_id' ] !== $this->get_config_service()->getIntegrationId()
		) {
			$this->die_with_status( 200 );
		}

		$proxy        = ServiceRegister::getService( Proxy::CLASS_NAME );
		$cr_recipient = $proxy->getRecipient( $body[ 'payload' ][ 'group_id' ], $body[ 'payload' ][ 'pool_id' ] );

		if ( ! $cr_recipient ) {
			$this->die_with_status( 400 );
		}

		switch ( $body[ 'event' ] ) {
			case 'receiver.subscribed':
				$this->handle_recipient_event( $cr_recipient, true );
				break;
			case 'receiver.unsubscribed':
				$this->handle_recipient_event( $cr_recipient, false );
				break;
		}
	}

	/**
	 * Handles form event.
	 *
	 * @param array $body
	 *
	 * @throws QueueStorageUnavailableException
	 */
	private function handle_form_event( $body ) {
		$is_event_type_valid    = isset( $body[ 'event' ] ) && in_array( $body[ 'event' ], self::$allowed_form_events, true );
		$is_event_payload_valid = isset( $body[ 'payload' ][ 'form_id' ] );

		if ( ! $is_event_type_valid || ! $is_event_payload_valid ) {
			$this->die_with_status( 400 );
		}

		Task_Queue::enqueue( new FormCacheSyncTask() );
	}

	/**
	 * Handles recipient event.
	 *
	 * @param Recipient $cr_recipient
	 * @param bool      $is_subscribed
	 *
	 * @throws QueueStorageUnavailableException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	private function handle_recipient_event( $cr_recipient, $is_subscribed ) {
		if ( $this->get_config_service()->is_recipient_sync_enabled()
		     && $this->user_exists( $cr_recipient->getEmail() )
		) {
			return $this->handle_user_event( $cr_recipient, $is_subscribed );
		}

		return $this->handle_contact_event( $cr_recipient, $is_subscribed );
	}

	/**
	 * Returns whether user with provided email exists.
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	private function user_exists( $email ) {
		$user = $this->get_recipient_repository()->get_user_by_email( $email );

		return null !== $user;
	}

	/**
	 * Handles user subscribed event.
	 *
	 * @param Recipient $cr_recipient Subscribed recipient.
	 * @param bool      $is_subscribed Sets recipient subscription status.
	 *
	 * @throws QueueStorageUnavailableException When we are unable to enqueue task.
	 */
	private function handle_user_event( $cr_recipient, $is_subscribed ) {
		$user = $this->get_recipient_repository()->get_user_by_email( $cr_recipient->getEmail() );
		if ( null === $user ) {
			return;
		}

		$status = $is_subscribed ? 1 : 0;
		$this->get_recipient_repository()->update_users_newsletter_field( array( $user->ID ), $status );

		Task_Queue::enqueue( new RecipientSyncTask( array( Recipient_Service::USER_ID_PREFIX . $user->ID ) ) );
	}

	/**
	 * Handles contact subscribed event.
	 *
	 * @param Recipient $cr_recipient  Subscribed recipient.
	 * @param bool      $is_subscribed Sets recipient subscription status.
	 *
	 * @throws QueueStorageUnavailableException When we are unable to enqueue task.
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	private function handle_contact_event( $cr_recipient, $is_subscribed ) {
		$filter = new QueryFilter();
		$filter->where( 'email', Operators::EQUALS, $cr_recipient->getEmail() );

		/** @var Contact $contact */
		$contact = $this->get_contact_repository()->selectOne( $filter );
		if ( null === $contact ) {
			return;
		}

		$contact->set_active( $is_subscribed );
		$contact->set_subscribed( $is_subscribed );
		if ( $is_subscribed ) {
			$contact->remove_special_tag( 'contact' );
			$contact->add_special_tag( 'subscriber' );
		} else {
			$contact->remove_special_tag( 'subscriber' );
			$contact->add_special_tag( 'contact' );
		}

		$this->get_contact_repository()->update( $contact );

		Task_Queue::enqueue( new RecipientSyncTask( array( Recipient_Service::CONTACT_ID_PREFIX . $contact->getId() ) ) );
	}

	/**
	 * Gets configuration service
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

	/**
	 * Returns an instance of contact repository.
	 *
	 * @return Base_Repository
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	private function get_contact_repository() {
		if ( null === $this->contact_repository ) {
			$this->contact_repository = RepositoryRegistry::getRepository( Contact::CLASS_NAME );
		}

		return $this->contact_repository;
	}
}
