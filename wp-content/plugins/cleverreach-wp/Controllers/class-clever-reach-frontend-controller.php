<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Sync\Default_Newsletter_Status_Sync_Task;
use CleverReach\WordPress\Components\Sync\Initial_Sync_Task;
use CleverReach\WordPress\Components\Utility\Helper;
use CleverReach\WordPress\Components\Utility\Task_Queue;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Recipients;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\AuthProxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Surveys\SurveyType;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\SingleSignOn\SingleSignOnProvider;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Queue;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;

/**
 * Class Clever_Reach_Frontend_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Frontend_Controller extends Clever_Reach_Base_Controller {

	const CURL_NOT_ENABLED_STATE_CODE      = 'curl-not-enabled';
	const WELCOME_STATE_CODE               = 'welcome';
	const INITIAL_SYNC_STATE_CODE          = 'initial-sync';
	const INITIAL_SYNC_SETTINGS_STATE_CODE = 'initial-sync-settings';
	const TOKEN_EXPIRED_STATE_CODE         = 'token-expired';
	const DASHBOARD_STATE_CODE             = 'dashboard';

	const CLEVERREACH_HELP_URL        = 'https://support.cleverreach.de/hc/en-us';
	const CLEVERREACH_DASHBOARD_URL   = '/admin';
	const CLEVERREACH_GDPR_URL        = 'https://www.cleverreach.com/en/features/privacy-security/eu-general-data-protection-regulation-gdpr/';
	const SCRIPT_VERSION              = 9;

	/**
	 * Configuration service
	 *
	 * @var Config_Service
	 */
	private $config_service;

	/**
	 * Queue service
	 *
	 * @var Queue
	 */
	private $queue_service;

	/**
	 * Proxy service
	 *
	 * @var AuthProxy
	 */
	private $proxy;

	/**
	 * Plugin page
	 *
	 * @var string
	 */
	private $page;

	/**
	 * CleverReachFrontendController constructor
	 */
	public function __construct() {
		$this->load_global_css();
		$this->load_global_js();
	}

	/**
	 * Renders appropriate view
	 */
	public function render() {
		$this->set_page();
		$this->load_css();
		$this->load_js();

		Task_Queue::wakeup();

		if ( self::WELCOME_STATE_CODE !== $this->page ) {
			include dirname( __DIR__ ) . '/resources/views/wrapper-start.php';
		}

		include dirname( __DIR__ ) . '/resources/views/' . $this->page . '.php';
		include dirname( __DIR__ ) . '/resources/views/wrapper-end.php';
	}

	/**
	 * Marks that first email is built
	 */
	public function build_first_email() {
		$this->get_config_service()->setIsFirstEmailBuilt( '1' );
		$this->die_json( array( 'status' => 'success' ) );
	}

	/**
	 * Saves initial sync settings
	 */
	public function set_initial_sync_settings() {
		$newsletter_status = $this->get_param( 'newsletterStatus' );
		$this->get_config_service()->set_default_newsletter_status( $newsletter_status );

		try {
			Task_Queue::enqueue( new Default_Newsletter_Status_Sync_Task(), true );
		} catch ( QueueStorageUnavailableException $e ) {
			$this->get_config_service()->set_default_newsletter_status( '' );
			$error_message = 'Error starting default newsletter task: ' . $e->getMessage();
			Logger::logError( $error_message );
			$this->die_json(
				array(
					'status'  => 'failed',
					'message' => $error_message,
				)
			);
		}

		$this->die_json( array( 'status' => 'success' ) );
	}

	/**
	 * Retry initial synchronization
	 */
	public function retry_sync() {
		try {
			$this->get_queue_service()->enqueue(
				$this->get_config_service()->getQueueName(),
				new Initial_Sync_Task()
			);
		} catch ( QueueStorageUnavailableException $e ) {
			Logger::logError( 'Error restarting sync: ' . $e->getMessage() );
			$this->die_json( array( 'status' => 'failed' ) );
		}

		$this->die_json( array( 'status' => 'success' ) );
	}

	/**
	 * Gets configuration for welcome page
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function get_welcome_config() {
		$redirect_url  = Helper::get_auth_redirect_url();
		$register_data = base64_encode( wp_json_encode( Helper::get_register_data() ) );
		$user_language = explode( '_', Helper::get_site_language() );
		$trigger_type  = $this->get_trigger_type();

		if ( ! $this->get_config_service()->is_plugin_opened_for_the_first_time() ) {
			$this->get_config_service()->set_plugin_opened_for_the_first_time( true );
		}

		return array(
			'checkStatusUrl'      => Helper::get_controller_url( 'Check_Status', array( 'action' => 'refresh_user_info' ) ),
			'authUrl'             => $this->get_proxy_service()->getAuthUrl( $redirect_url, $register_data, array( 'lang' => $user_language[ 0 ] ) ),
			'triggerType'         => $trigger_type,
			'surveyUrl'           => Helper::get_controller_url( 'Survey', array( 'action' => 'handle' ) ),
			'ignoreSurveyUrl'     => Helper::get_controller_url( 'Survey', array( 'action' => 'ignore' ) ),
		);
	}

	/**
	 * Gets configuration for dashboard page
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function get_dashboard_config() {
		$user_info                   = $this->get_config_service()->getUserInfo();
		$failure_parameters          = $this->get_initial_sync_failure_parameters();
		$import_statistics_displayed = $this->get_config_service()->isImportStatisticsDisplayed();
		$trigger_type                = $this->get_trigger_type( $import_statistics_displayed );
		$recipient_sync_enabled      = $this->get_config_service()->is_recipient_sync_enabled();
		$tags                        = new TagCollection();

		if ( ! $import_statistics_displayed ) {
			if ( $recipient_sync_enabled ) {
				$recipient_service = ServiceRegister::getService( Recipients::CLASS_NAME );
				$tags              = $recipient_service->getAllTags();
			}

			$this->get_config_service()->setImportStatisticsDisplayed( true );
		}

		return array(
			'logoUrl'                       => Helper::get_clever_reach_base_url( '/resources/images/icon_quickstartmailing.svg' ),
			'buildFirstEmailUrl'            => Helper::get_controller_url( 'Frontend', array( 'action' => 'build_first_email' ) ),
			'retrySyncUrl'                  => Helper::get_controller_url( 'Frontend', array( 'action' => 'retry_sync' ) ),
			'importStatisticsDisplayed'     => $import_statistics_displayed,
			'numberOfSyncedRecipients'      => ! $import_statistics_displayed ? $this->get_config_service()->getNumberOfSyncedRecipients() : 0,
			'tags'                          => $tags,
			'recipientId'                   => $user_info[ 'id' ],
			'helpUrl'                       => self::CLEVERREACH_HELP_URL,
			'gdprUrl'                       => self::CLEVERREACH_GDPR_URL,
			'buildEmailUrl'                 => SingleSignOnProvider::getUrl( self::CLEVERREACH_DASHBOARD_URL ),
			'isFirstEmailBuilt'             => $this->get_config_service()->isFirstEmailBuilt(),
			'integrationName'               => $this->get_config_service()->getIntegrationListName(),
			'isInitialSyncTaskFailed'       => $failure_parameters[ 'isFailed' ],
			'initialSyncTaskFailureMessage' => $failure_parameters[ 'description' ],
			'triggerType'                   => $trigger_type,
			'surveyUrl'                     => Helper::get_controller_url( 'Survey', array( 'action' => 'handle' ) ),
			'ignoreSurveyUrl'               => Helper::get_controller_url( 'Survey', array( 'action' => 'ignore' ) ),
			'recipientSyncEnabled'          => $recipient_sync_enabled,
		);
	}

	/**
	 * Gets configuration for token expired page
	 *
	 * @return array
	 */
	public function get_token_expired_config() {
		$user_info     = $this->get_config_service()->getUserInfo();
		$redirect_url  = Helper::get_refresh_auth_redirect_url();
		$register_data = base64_encode( wp_json_encode( Helper::get_register_data() ) );
		$user_language = explode( '_', Helper::get_site_language() );
		$message = '';
		$refresh_token = $this->get_config_service()->getRefreshToken();
		if ( ! empty( $refresh_token ) ) {
			$connection_status_response = $this->get_proxy_service()->checkConnectionStatus();
			$message                    = $connection_status_response->getMessage();
		}

		return array(
			'logoUrl'        => Helper::get_clever_reach_base_url( '/resources/images/icon_quickstartmailing.svg' ),
			'checkStatusUrl' => Helper::get_controller_url( 'Check_Status', array( 'action' => 'refresh_user_info' ) ),
			'authUrl'        => $this->get_proxy_service()->getAuthUrl(
					$redirect_url,
					$register_data,
					array( 'lang' => $user_language[ 0 ] )
				) . '#login',
			'clientId'       => $user_info[ 'id' ],
			'apiMessage'     => $message,
		);
	}

	/**
	 * Gets configuration for initial sync settings page
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function get_initial_sync_settings_config() {
		$user_info = $this->get_config_service()->getUserInfo();

		$config = array(
			'helloUrl'          => Helper::get_clever_reach_base_url( '/resources/images/icon_' . __( 'hello', 'cleverreach-wp' ) . '.png' ),
			'helpUrl'           => self::CLEVERREACH_HELP_URL,
			'recipientId'       => $user_info[ 'id' ],
			'configurationUrl'  => Helper::get_controller_url( 'Frontend', array( 'action' => 'set_initial_sync_settings' ) ),
			'hasSubscriberRole' => ! is_null( get_role( 'subscriber' ) ),
		);

		return $config;
	}

	/**
	 * Gets configuration for initial sync page
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function get_initial_sync_config() {
		$recipient_sync_enabled = $this->get_config_service()->is_recipient_sync_enabled();

		return array(
			'statusCheckUrl'       => Helper::get_controller_url( 'Check_Status', array( 'action' => 'initial_sync' ) ),
			'integrationName'      => $this->get_config_service()->getIntegrationName(),
			'recipientSyncEnabled' => $recipient_sync_enabled,
		);
	}

	/**
	 * Wraps tags into segments and prepares them for rendering.
	 *
	 * @param TagCollection $tags All tags in the system.
	 *
	 * @return string HTML prepared segment list.
	 */
	public function build_segments( $tags ) {
		$segment_list = '';
		foreach ( $tags as $index => $tag ) {
			if ( 3 === $index ) {
				$segment_list .= '<div class="value">...</div>';
				break;
			}

			$first         = ( $index + 1 ) . ') ' . substr( $tag->getTitle(), 0, - 3 );
			$last          = substr( $tag->getTitle(), strlen( $tag->getTitle() ) - 3 );
			$segment_list .= '<div class="value" title="' . $tag->getTitle() . '">'
				. '<span class="cr-first-part">' . $first . '</span>'
				. '<span class="cr-last-part">' . $last . '</span>'
				. '</div>';
		}

		return $segment_list;
	}

	/**
	 * Set current page code
	 */
	private function set_page() {
		if ( ! Helper::is_curl_enabled() ) {
			$this->page = self::CURL_NOT_ENABLED_STATE_CODE;
		} elseif ( ! $this->is_auth_token_valid() ) {
			$this->page = self::WELCOME_STATE_CODE;
		} elseif ( ! $this->is_initial_sync_config_set() && $this->get_config_service()->is_recipient_sync_enabled() ) {
			$this->page = self::INITIAL_SYNC_SETTINGS_STATE_CODE;
		} elseif ( $this->is_initial_sync_in_progress() ) {
			$this->page = self::INITIAL_SYNC_STATE_CODE;
		} elseif ( ! $this->check_if_refresh_token_exists() || ! $this->is_token_alive() ) {
			$this->page = self::TOKEN_EXPIRED_STATE_CODE;
		} else {
			$this->page = self::DASHBOARD_STATE_CODE;
		}

		if ( ( ! empty( $_REQUEST[ 'notification_type' ] ) )
		     && 'user_offline' === $_REQUEST[ 'notification_type' ]
		     && ! in_array( $this->page, array(
				self::INITIAL_SYNC_SETTINGS_STATE_CODE,
				self::INITIAL_SYNC_STATE_CODE
			), true ) ) {
			$this->get_config_service()->set_show_oauth_connection_lost_notice( false );
		}
	}

	/**
	 * Loads css that is used outside CleverReach pages.
	 */
	public function load_global_css() {
		$base_url = Helper::get_clever_reach_base_url( '/resources/' );
		wp_enqueue_style(
			'wp-cleverreach-global-admin-styles',
			$base_url . 'css/cleverreach.css',
			array(),
			self::SCRIPT_VERSION
		);
	}

	/**
	 * Loads all css files needed for plugin
	 */
	public function load_css() {
		$base_url = Helper::get_clever_reach_base_url( '/resources/' );
		wp_enqueue_style(
			'wp-cleverreach-global-admin-icofont',
			$base_url . 'css/cleverreach-icofont.css',
			array(),
			self::SCRIPT_VERSION
		);
		wp_enqueue_style(
			'wp-cleverreach-survey-styles',
			$base_url . 'css/cr-survey.css',
			array(),
			self::SCRIPT_VERSION
		);
		wp_enqueue_style(
			'font-awesome',
			'https://use.fontawesome.com/releases/v5.5.0/css/all.css',
			array(),
			self::SCRIPT_VERSION
		);

		if ( in_array( $this->page, array( self::WELCOME_STATE_CODE, self::TOKEN_EXPIRED_STATE_CODE ), true ) ) {
			wp_enqueue_style(
				'wp-cleverreach-auth-iframe-style',
				$base_url . 'css/cleverreach-auth-iframe.css',
				array(),
				self::SCRIPT_VERSION
			);
		}
	}

	/**
	 * Loads JS that is used outside CleverReach pages.
	 */
	private function load_global_js() {
		$base_url = Helper::get_clever_reach_base_url( '/resources/' );
		wp_enqueue_script(
			'wp_cr_ajax',
			esc_url( $base_url . 'js/cleverreach.ajax.js' ),
			array(),
			self::SCRIPT_VERSION,
			true
		);
	}

	/**
	 * Loads JS script for the current page
	 */
	private function load_js() {
		$base_url = Helper::get_clever_reach_base_url( '/resources/' );
		wp_enqueue_script(
			'wp_cr_authorization',
			esc_url( $base_url . 'js/cleverreach.authorization.js' ),
			array(),
			self::SCRIPT_VERSION,
			true
		);
		wp_enqueue_script(
			'wp_cr_status_checker',
			esc_url( $base_url . 'js/cleverreach.status-checker.js' ),
			array(),
			self::SCRIPT_VERSION,
			true
		);
		wp_enqueue_script(
			'wp_cr_survey',
			esc_url( $base_url . 'js/cleverreach.survey.js' ),
			array(),
			self::SCRIPT_VERSION,
			true
		);

		if ( self::WELCOME_STATE_CODE !== $this->page ) {
			wp_enqueue_script(
				"wp_cr_$this->page",
				esc_url( $base_url . "js/cleverreach.$this->page.js" ),
				array(),
				self::SCRIPT_VERSION,
				true
			);
		}

		if ( self::DASHBOARD_STATE_CODE === $this->page ) {
			wp_enqueue_script(
				'wp_cr_forms',
				esc_url( $base_url . 'js/cleverreach.forms.js' ),
				array(),
				self::SCRIPT_VERSION,
				true
			);
			wp_enqueue_script(
				'wp_cr_integrations',
				esc_url( $base_url . 'js/cleverreach.integrations.js' ),
				array(),
				self::SCRIPT_VERSION,
				true
			);
			wp_localize_script( 'wp_cr_forms', 'cleverreachForms', array(
				'forms_endpoint_url'       => Helper::get_controller_url( 'Forms', array( 'action' => 'get_all_forms' ) ),
				'integration_name'         => $this->get_config_service()->getIntegrationName(),
				'edit_in_cleverreach_text' => __( 'Edit in CleverReach®', 'cleverreach-wp' ),
			) );
			wp_localize_script( 'wp_cr_integrations', 'cleverreachIntegrations', array(
				'integrations_endpoint_url' => Helper::get_controller_url( 'Integrations', array( 'action' => 'get_all_integrations' ) ),
				'integration_manual_text'   => __( 'How to use?', 'cleverreach-wp' ),
			) );
		}
	}

	/**
	 * Gets fail parameters for initial sync task if it failed
	 *
	 * @return array
	 */
	private function get_initial_sync_failure_parameters() {
		$params = array(
			'isFailed'    => false,
			'description' => '',
		);
		/**
		 * Initial sync task
		 *
		 * @var QueueItem $initial_sync_task
		 */
		$initial_sync_task = $this->get_queue_service()->findLatestByType( 'Initial_Sync_Task' );
		if ( $initial_sync_task && $initial_sync_task->getStatus() === QueueItem::FAILED ) {
			$params = array(
				'isFailed'    => true,
				'description' => $initial_sync_task->getFailureDescription(),
			);
		}

		return $params;
	}

	/**
	 * Checks if auth token is valid
	 *
	 * @return bool
	 */
	private function is_auth_token_valid() {
		$access_token = $this->get_config_service()->getAccessToken();

		return ! empty( $access_token );
	}

	/**
	 * Checks if initial sync configuration is set
	 *
	 * @return bool
	 */
	private function is_initial_sync_config_set() {
		$default_newsletter_status = $this->get_config_service()->get_default_newsletter_status();

		return null !== $default_newsletter_status && '' !== $default_newsletter_status;
	}

	/**
	 * Checks if initial sync is in progress
	 *
	 * @return bool
	 */
	private function is_initial_sync_in_progress() {
		/**
		 * Initial sync task
		 *
		 * @var QueueItem $initial_sync_task_item
		 */
		$initial_sync_task_item = $this->get_queue_service()->findLatestByType( 'Initial_Sync_Task' );
		if ( ! $initial_sync_task_item ) {
			try {
				$this->get_queue_service()->enqueue(
					$this->get_config_service()->getQueueName(),
					new Initial_Sync_Task()
				);
			} catch ( QueueStorageUnavailableException $e ) {
				return true;
			}

			return true;
		}

		return $initial_sync_task_item->getStatus() !== QueueItem::COMPLETED
			&& $initial_sync_task_item->getStatus() !== QueueItem::FAILED;
	}

	/**
	 * Returns survey form trigger type.
	 *
	 * @param bool $import_statistics_displayed Whether import statistics are displayed or not.
	 *
	 * @return string
	 */
	private function get_trigger_type( $import_statistics_displayed = false ) {
		if ( self::WELCOME_STATE_CODE === $this->page
		     && ! $this->get_config_service()->is_plugin_opened_for_the_first_time()
		) {
			return SurveyType::PLUGIN_INSTALLED;
		}

		if ( self::DASHBOARD_STATE_CODE === $this->page && ! $import_statistics_displayed ) {
			return SurveyType::INITIAL_SYNC_FINISHED;
		}

		return SurveyType::PERIODIC;
	}

	/**
	 * Checks if refresh token exists in the database
	 *
	 * @return bool
	 */
	private function check_if_refresh_token_exists() {
		return null !== $this->get_config_service()->getRefreshToken();
	}

	/**
	 * Checks whether auth token is alive and accessible.
	 *
	 * @return bool
	 */
	private function is_token_alive() {
		return $this->get_proxy_service()->isConnected();
	}

	/**
	 * Gets config service
	 *
	 * @return Config_Service
	 */
	private function get_config_service() {
		if ( empty( $this->config_service ) ) {
			$this->config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
		}

		return $this->config_service;
	}

	/**
	 * Gets queue service
	 *
	 * @return Queue
	 */
	private function get_queue_service() {
		if ( empty( $this->queue_service ) ) {
			$this->queue_service = ServiceRegister::getService( Queue::CLASS_NAME );
		}

		return $this->queue_service;
	}

	/**
	 * Gets proxy service
	 *
	 * @return AuthProxy
	 */
	private function get_proxy_service() {
		if ( empty( $this->proxy ) ) {
			$this->proxy = ServiceRegister::getService( AuthProxy::CLASS_NAME );
		}

		return $this->proxy;
	}
}
