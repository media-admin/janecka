<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\InfrastructureServices;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Notification;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration as ConfigInterface;
use CleverReach\WordPress\Components\Utility\Helper;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class Config_Service
 *
 * @package CleverReach\WordPress\Components\InfrastructureServices
 */
class Config_Service extends ConfigInterface {

	const MODULE_NAME      = 'cleverreach';
	const INTEGRATION_NAME = 'WordPress';
	const CLIENT_ID        = 'uadDU0wHla';
	const CLIENT_SECRET    = 'QrUrHfpkKkfgFCcFKREHJ9RXMMYjtjtj';

	/**
	 * Retrieves integration name
	 *
	 * @return string
	 */
	public function getIntegrationName() {
		return self::INTEGRATION_NAME;
	}

	/**
	 * Returns integration list name
	 *
	 * @return string
	 */
	public function getIntegrationListName() {
		$site_name = Helper::get_site_name() ?: Helper::get_site_url();

		return "{$this->getIntegrationName()} - {$site_name}";
	}

	/**
	 * Returns queue name
	 *
	 * @return string
	 */
	public function getQueueName() {
		return 'WordPressDefault';
	}

	/**
	 * Return whether product search is enabled or not.
	 *
	 * @return bool
	 *   If search is enabled returns true, otherwise false.
	 */
	public function isProductSearchEnabled() {
		return true;
	}

	/**
	 * Retrieves parameters needed for product search registrations.
	 *
	 * @return array
	 *   Associative array with keys name, url, password.
	 */
	public function getProductSearchParameters() {
		return array(
			'name'     => self::MODULE_NAME . ' ' . $this->getIntegrationListName(),
			'url'      => Helper::get_article_search_url(),
			'password' => $this->getProductSearchEndpointPassword(),
		);
	}

	/**
	 * Returns client id
	 *
	 * @return string
	 */
	public function getClientId() {
		return self::CLIENT_ID;
	}

	/**
	 * Returns client secret
	 *
	 * @return string
	 */
	public function getClientSecret() {
		return self::CLIENT_SECRET;
	}

	/**
	 * Retrieves URL of a controller that will handle webhook calls (GET for verification, POST for handling)
	 *
	 * @return string URL of webhook handler controller.
	 */
	public function getCrEventHandlerURL() {
		return preg_replace( '/^http:/i', 'https:', Helper::get_event_handler_url() );
	}

	/**
	 * Sets if user is online.
	 *
	 * @param bool $user_online
	 *
	 * @throws \Exception
	 */
	public function setUserOnline($user_online)
	{
		$user_was_online = $this->isUserOnline();

		parent::setUserOnline( $user_online );

		if ( $user_was_online && ! $user_online ) {
			Helper::push_user_offline_notification();
		}
	}

	/**
	 * Gets language for sync
	 *
	 * @return string
	 */
	public function get_language() {
		return $this->getConfigRepository()->get( 'CLEVERREACH_SYNC_LANGUAGE' );
	}

	/**
	 * Sets language for sync
	 *
	 * @param string $language Language code.
	 */
	public function set_language( $language ) {
		$this->getConfigRepository()->set( 'CLEVERREACH_SYNC_LANGUAGE', $language );
	}

	/**
	 * Gets default handling of newsletter field
	 *
	 * @return string Possible values 'all', 'none', 'role_subscriber_only'.
	 */
	public function get_default_newsletter_status() {
		return $this->getConfigRepository()->get( 'CLEVERREACH_NEWSLETTER_STATUS' );
	}

	/**
	 * Sets default handling of newsletter field
	 *
	 * @param string $newsletter_status Newsletter default status handling.
	 */
	public function set_default_newsletter_status( $newsletter_status ) {
		$this->getConfigRepository()->set( 'CLEVERREACH_NEWSLETTER_STATUS', $newsletter_status );
	}

	/**
	 * Gets database version
	 *
	 * @return string
	 */
	public function get_database_version() {
		return $this->getConfigRepository()->get( 'CLEVERREACH_DATABASE_VERSION' );
	}

	/**
	 * Sets database version for migration scripts
	 *
	 * @param string $database_version Database version.
	 */
	public function set_database_version( $database_version ) {
		$this->getConfigRepository()->set( 'CLEVERREACH_DATABASE_VERSION', $database_version );
	}

	/**
	 * Checks flag that indicates whether current version of the plugin supports entities
	 *
	 * @return bool
	 */
	public function is_entity_table_created() {
		return (bool)$this->getConfigRepository()->get( 'CLEVERREACH_ENTITY_TABLE_CREATED' );
	}

	/**
	 * Sets flag which indicates that current version of the plugin supports entities
	 */
	public function set_entity_table_created() {
		$this->getConfigRepository()->set( 'CLEVERREACH_ENTITY_TABLE_CREATED', 1 );
	}

	/**
	 * Return notification message that will be shown user as system notification
	 *
	 * @return string
	 */
	public function getNotificationMessage() {
		return '';
	}

	/**
	 * Returns flag that indicates that the OAuth connection with CleverReach has been lost.
	 *
	 * @return bool
	 */
	public function show_oauth_connection_lost_notice() {
		return (bool) $this->getConfigRepository()->get( 'SHOW_OAUTH_CONNECTION_LOST_NOTICE' );
	}

	/**
	 * Sets flag that indicates that the OAuth connection with CleverReach is lost.
	 *
	 * @param bool $show_notice
	 */
	public function set_show_oauth_connection_lost_notice( $show_notice ) {
		$this->getConfigRepository()->set( 'SHOW_OAUTH_CONNECTION_LOST_NOTICE', $show_notice );
	}

	/**
	 * Returns notification parameters as array
	 *
	 * @return array notification ['description' => $description, 'url' => $url]
	 */
	public function get_admin_notification_data() {
		$json_notification =  $this->getConfigRepository()->get( 'ADMIN_NOTIFICATION' );

		return json_decode($json_notification, true);
	}

	/**
	 * Stores html formatted message that will be shown to admin
	 *
	 * @param Notification $notification
	 */
	public function save_admin_notification_data( Notification $notification ) {
		$json_notification = json_encode( array(
			'description' => $notification->getDescription(),
			'url' => $notification->getUrl()
		) );

		$this->getConfigRepository()->set( 'ADMIN_NOTIFICATION', $json_notification );
	}

	/**
	 * Returns whether plugin has been opened for the first time.
	 *
	 * @return bool
	 */
	public function is_plugin_opened_for_the_first_time() {
		return (bool) $this->getConfigRepository()->get( 'IS_PLUGIN_OPENED_FOR_THE_FIRST_TIME' );
	}

	/**
	 * Sets if plugin has been opened for the first time.
	 *
	 * @param bool $value
	 */
	public function set_plugin_opened_for_the_first_time( $value ) {
		$this->getConfigRepository()->set( 'IS_PLUGIN_OPENED_FOR_THE_FIRST_TIME', $value );
	}

	/**
	 * Returns whether recipient synchronization is enabled.
	 *
	 * @return bool
	 */
	public function is_recipient_sync_enabled() {
		return false;
	}

	/**
	 * Returns whether recipient synchronization for a particular integration is enabled.
	 *
	 * @param string $integration_name
	 *
	 * @return bool|null
	 */
	public function is_integration_recipient_sync_enabled( $integration_name ) {
		return (bool)$this->getConfigRepository()->get( 'IS_' . strtoupper( $integration_name )  . '_RECIPIENT_SYNC_ENABLED' );
	}

	/**
	 * Sets whether recipient synchronization is enabled for a particular integration.
	 *
	 * @param string $integration_name
	 * @param bool   $enabled
	 */
	public function set_integration_recipient_sync_enabled( $integration_name, $enabled ) {
		$this->getConfigRepository()->set(
			'IS_' . strtoupper( $integration_name )  . '_RECIPIENT_SYNC_ENABLED',
			(bool)$enabled
		);
	}

	/**
	 * Return url of plugin with CleverReach poll popup
	 *
	 * @return string
	 */
	public function getPluginUrl() {
		return get_admin_url( null, 'admin.php?page=wp-cleverreach' );
	}

	/**
	 * Return whether integration supports integration with CleverReach or not.
	 *
	 * @return bool
	 *   If forms are enabled returns true, otherwise false.
	 */
	public function isFormSyncEnabled() {
		return true;
	}

	/**
	 * Retrieves color code of authentication iframe background.
	 *
	 * @return string Color code.
	 */
	public function getAuthIframeColor() {
		return 'f1f1f1';
	}

	/**
	 * Retrieves new field for product search registration endpoint.
	 *
	 * @return array Associative array with keys type, cors, icon.
	 */
	public function getAdditionalProductSearchParameters() {
		$params = parent::getAdditionalProductSearchParameters();

		$params[ 'type' ] = 'content';

		return $params;
	}

	/**
	 * Gets instance on configuration repository service.
	 *
	 * @return ConfigRepositoryInterface
	 *   Instance of configuration service.
	 */
	protected function getConfigRepository() {
		return ServiceRegister::getService( ConfigRepositoryInterface::CLASS_NAME );
	}
}
