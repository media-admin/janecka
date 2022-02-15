<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\BusinessLogicServices;

use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Notification;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Notifications;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class Notification_Service
 *
 * @package CleverReach\WordPress\Components\BusinessLogicServices
 */
class Notification_Service implements Notifications {

	/**
	 * Creates a new notification in system integration.
	 *
	 * @param Notification $notification Notification object that contains info such as
	 *                                   identifier, name, date, description, url.
	 *
	 * @return boolean
	 */
	public function push( Notification $notification ) {
		/** @var Config_Service $config_service */
		$config_service = ServiceRegister::getService( Configuration::CLASS_NAME );

		if ( 'user_offline' === $notification->getName() ) {
			$config_service->set_show_oauth_connection_lost_notice( true );
			$config_service->save_admin_notification_data( $notification );
		}

		return true;
	}
}
