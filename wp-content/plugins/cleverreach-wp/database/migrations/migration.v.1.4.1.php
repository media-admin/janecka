<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Database\Migrations;

use CleverReach\WordPress\Components\Utility\Database;
use CleverReach\WordPress\Components\Utility\Update\Update_Schema;

/**
 * Class Migration_1_4_1
*
* @package CleverReach\WordPress\Database\Migrations
*/
class Migration_1_4_1 extends Update_Schema {
	/**
	 * @inheritDoc
	 */
	public function update() {
		$this->rename_initial_sync_queue_items();
	}

	/**
	 * Removes old initial sync queue items.
	 */
	private function rename_initial_sync_queue_items() {
		$this->db->query( 'UPDATE `' . Database::table( Database::QUEUE_TABLE ) . '` SET `type` = "Initial_Sync_Task" WHERE `type` = "InitialSyncTask"' );
	}
}
