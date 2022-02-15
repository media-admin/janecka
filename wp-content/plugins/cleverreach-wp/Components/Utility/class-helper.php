<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Utility;

use CleverReach\WordPress\Components\BusinessLogicServices\Notification_Service;
use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Notification;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Notifications;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Queue;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;

/**
 * A utility class that utilises data related to user's CMS.
 */
class Helper {

	/** Database date format */
	const DATE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * Site url
	 *
	 * @var string
	 */
	private static $site_url;

	/**
	 * Site name
	 *
	 * @var string
	 */
	private static $site_name;

	/**
	 * Returns whether a specific plugin is enabled.
	 *
	 * @param string $plugin_name
	 *
	 * @return bool
	 */
	public static function is_plugin_enabled( $plugin_name = '' ) {
		if ( '' === $plugin_name ) {
			$plugin_name = self::get_plugin_name();
		}

		if ( self::is_plugin_active_for_network( $plugin_name ) ) {
			return true;
		}

		return self::is_plugin_active_for_current_site( $plugin_name );
	}

	/**
	 * Returns language code to be used in all synchronization
	 *
	 * @return string
	 */
	public static function get_sync_language() {
		$config_service = ServiceRegister::getService( Configuration::CLASS_NAME );

		return $config_service->get_language();
	}

	/**
	 * Gets interface language code for given site
	 *
	 * @return string
	 */
	public static function get_site_language() {
		return get_user_locale();
	}

	/**
	 * Gets integration name + site name (site domain)
	 *
	 * @return string
	 */
	public static function get_name() {
		if ( empty( self::$site_name ) ) {
			self::$site_name = self::get_system_name() . ': ' . self::get_site_name() . ' (' . self::get_site_url() . ')';
		}

		return self::$site_name;
	}

	/**
	 * Returns system name
	 *
	 * @return string
	 */
	public static function get_system_name() {
		return 'WordPress';
	}

	/**
	 * Gets the name of the site
	 *
	 * @return string
	 */
	public static function get_site_name() {
		$name = get_bloginfo( 'name' );

		return $name ?: '';
	}

	/**
	 * Gets the name of the plugin
	 *
	 * @return string
	 */
	public static function get_plugin_name() {
		return plugin_basename( dirname( dirname( dirname( __FILE__ ) ) ) . '/cleverreach-wp.php' );
	}

	/**
	 * Pushes notification that the connection to CleverReach has been lost.
	 *
	 * @throws \Exception
	 */
	public static function push_user_offline_notification() {
		/** @var Notification_Service $notification_service */
		$notification_service = ServiceRegister::getService( Notifications::CLASS_NAME );
		/** @var Config_Service $config_service */
		$config_service = ServiceRegister::getService( Configuration::CLASS_NAME );

		$notification = new Notification( null, 'user_offline' );
		$notification->setDate( new \DateTime( '@' . time() ) );
		$notification->setDescription( __( 'We have detected that the connection and synchronization with your CleverReach account has been interrupted.', 'cleverreach-wp' ) );
		$notification->setUrl( $config_service->getPluginUrl() . '&notification_type=user_offline' );

		$notification_service->push( $notification );
	}

	/**
	 * Gets URL for CleverReach controller
	 *
	 * @param string $name Name of the controller without "CleverReach" and "Controller".
	 * @param array  $params Associative array of parameters.
	 *
	 * @return string
	 */
	public static function get_controller_url( $name, $params = array() ) {
		$url = get_site_url() . "/?cleverreach_wp_controller={$name}";
		if ( ! empty( $params ) ) {
			$url .= '&' . http_build_query( $params );
		}

		return $url;
	}

	/**
	 * Returns authentication callback URL
	 *
	 * @return string
	 */
	public static function get_auth_redirect_url() {
		return self::get_controller_url( 'Callback', array( 'action' => 'run' ) );
	}

	/**
	 * Returns authentication callback URL for refreshing auth tokens
	 *
	 * @return string
	 */
	public static function get_refresh_auth_redirect_url() {
		return self::get_controller_url(
			'Callback',
			array(
				'action'  => 'run',
				'refresh' => true,
			)
		);
	}

	/**
	 * Returns event handler URL
	 *
	 * @return string
	 */
	public static function get_event_handler_url() {
		return self::get_controller_url( 'Event_Handler', array( 'action' => 'handle' ) );
	}

	/**
	 * Returns article search URL
	 *
	 * @return string
	 */
	public static function get_article_search_url() {
		return self::get_controller_url( 'Article_Search', array( 'action' => 'run' ) );
	}

	/**
	 * Returns log file download URL
	 *
	 * @return string
	 */
	public static function get_log_file_download_url() {
		return self::get_controller_url(
			'Log',
			array(
				'action'  => 'run',
				'logDate' => date( 'Y_m_d' ),
			)
		);
	}

	/**
	 * Gets base URL of default site's frontend
	 *
	 * @return string
	 */
	public static function get_site_url() {
		if ( empty( self::$site_url ) ) {
			self::$site_url = get_site_url();
			self::$site_url = str_replace( 'https://', '', self::$site_url );
			self::$site_url = str_replace( 'http://', '', self::$site_url );
		}

		return self::$site_url;
	}

	/**
	 * Gets URL to CleverReach plugin wanted file. Plugin root is returned if no parameter is passed.
	 *
	 * @param string $path Path to wanted file.
	 *
	 * @return string
	 */
	public static function get_clever_reach_base_url( $path = '' ) {
		return plugins_url( $path, dirname( dirname( __FILE__ ) ) );
	}

	/**
	 * Gets admin user registration data for CleverReach
	 *
	 * @return array
	 */
	public static function get_register_data() {
		$user_data = self::get_admin_user_data( get_current_user_id() );

		return array(
			'email'     => $user_data['email'] ?: '',
			'firstname' => $user_data['firstname'] ?: '',
			'lastname'  => $user_data['lastname'] ?: '',
			'company'   => '',
			'gender'    => '',
			'street'    => '',
			'zip'       => '',
			'city'      => '',
			'country'   => '',
			'phone'     => '',
		);
	}

	/**
	 * Checks if cURL library is installed and enabled on the system
	 *
	 * @return bool
	 */
	public static function is_curl_enabled() {
		return function_exists( 'curl_version' );
	}

	/**
	 * Gets plugin current version
	 *
	 * @return string
	 */
	public static function get_plugin_version() {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . self::get_plugin_name() );

		return $plugin_data['Version'];
	}

	/**
	 * Checks if plugin is initialized
	 *
	 * @return bool
	 */
	public static function is_plugin_initialized() {
		global $wpdb;
		$database = new Database( $wpdb );

		return $database->plugin_already_initialized();
	}

	/**
	 * Returns whether CleverReach initial synchronisation has been finished.
	 *
	 * @return bool
	 */
	public static function is_initial_sync_finished() {
		/** @var Queue $queue_service */
		$queue_service = ServiceRegister::getService( Queue::CLASS_NAME );
		$initial_sync_task = $queue_service->findLatestByType( 'Initial_Sync_Task' );

		return $initial_sync_task !== null && $initial_sync_task->getStatus() === QueueItem::COMPLETED;
	}

	/**
	 * Gets first name, last name and email for provided user ID
	 *
	 * @param int $user_id User id.
	 *
	 * @return array|bool
	 */
	private static function get_admin_user_data( $user_id ) {
		$user      = get_user_by( 'id', $user_id );
		$user_data = array(
			'firstname' => get_user_meta( $user_id, 'first_name', true ),
			'lastname'  => get_user_meta( $user_id, 'last_name', true ),
			'email'     => $user->user_email,
		);

		return $user_data;
	}

	/**
	 * Returns if CleverReach plugin is active through network
	 *
	 * @param string $plugin_name
	 *
	 * @return bool
	 */
	private static function is_plugin_active_for_network( $plugin_name ) {
		if ( ! is_multisite() ) {
			return false;
		}

		$plugins = get_site_option( 'active_sitewide_plugins' );

		return isset( $plugins[ $plugin_name ] );
	}

	/**
	 * Returns if CleverReach plugin is active for current site
	 *
	 * @param string $plugin_name
	 *
	 * @return bool
	 */
	private static function is_plugin_active_for_current_site( $plugin_name ) {
		return in_array( $plugin_name, apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true );
	}
}
