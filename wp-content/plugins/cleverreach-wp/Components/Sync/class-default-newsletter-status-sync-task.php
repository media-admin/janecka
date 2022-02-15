<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Sync;

use CleverReach\WordPress\Components\Repositories\Recipient_Repository;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\BaseSyncTask;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException;

/**
 * Class Default_Newsletter_Status_Sync_Task
 *
 * @package CleverReach\WordPress\Components\Sync
 */
class Default_Newsletter_Status_Sync_Task extends BaseSyncTask {

	/**
	 * Runs task logic
	 *
	 * @throws InvalidConfigurationException Invalid configuration exception.
	 */
	public function execute() {
		$default_newsletter_status_handling = $this->getConfigService()->get_default_newsletter_status();

		$this->reportAlive();
		$this->validate_default_newsletter_status_handling( $default_newsletter_status_handling );
		$this->reportProgress( 50 );

		if ( 'none' !== $default_newsletter_status_handling ) {
			$recipient_repository = new Recipient_Repository();

			if ( 'all' === $default_newsletter_status_handling ) {
				$user_ids = $recipient_repository->get_all_user_ids();
			} else {
				$user_ids = $recipient_repository->get_user_ids_for_roles( array( 'subscriber' ) );
			}

			$this->reportProgress( 75 );
			$recipient_repository->update_users_newsletter_field( $user_ids, 1 );
		}

		$this->reportProgress( 100 );
	}

	/**
	 * Validates if $default_newsletter_status_handling parameter is set
	 *
	 * @param string $default_newsletter_status_handling How default newsletter status handling should be done.
	 *
	 * @throws InvalidConfigurationException Invalid configuration exception.
	 */
	private function validate_default_newsletter_status_handling( $default_newsletter_status_handling ) {
		if ( empty( $default_newsletter_status_handling ) ) {
			throw new InvalidConfigurationException( 'Default newsletter status not set in Configuration Service' );
		}
	}
}
