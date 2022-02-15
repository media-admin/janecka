<?php

include __DIR__ . '/../vendor/autoload.php';

use CleverReach\WordPress\Components\Repositories\Base_Repository;
use CleverReach\WordPress\Components\Utility\Database;
use CleverReach\WordPress\IntegrationCore\Tests\Infrastructure\ORM\AbstractGenericStudentRepositoryTest;

/**
 * Class BaseRepositoryTest
 *
 * @package CleverReach
 */
class BaseRepositoryTest extends AbstractGenericStudentRepositoryTest {

	/**
	 * @inheritdoc
	 */
	public function setUp() {

		parent::setUp();
		$this->createTestTable();

		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . $wpdb->prefix . Database::ENTITY_TABLE );
	}

	/**
	 * @inheritdoc
	 */
	public static function tearDownAfterClass() {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . Database::ENTITY_TABLE );
	}

	/**
	 * @return string
	 */
	public function getStudentEntityRepositoryClass() {
		return Base_Repository::getClassName();
	}

	/**
	 * Cleans up all storage services used by repositories
	 */
	public function cleanUpStorage() {
		return null;
	}

	/**
	 * Creates a table for testing purposes.
	 */
	private function createTestTable() {
		global $wpdb;

		$table = $wpdb->prefix . Database::ENTITY_TABLE;

		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$cleverreachTestTableInstallScript = 'CREATE TABLE IF NOT EXISTS `' . $table . '` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(255),
            `index_1` VARCHAR(100),
            `index_2` VARCHAR(100),
            `index_3` VARCHAR(100),
            `index_4` VARCHAR(100),
            `index_5` VARCHAR(100),
            `index_6` VARCHAR(100),
            `index_7` VARCHAR(100),
            `data` LONGTEXT,
            PRIMARY KEY (`id`),
            INDEX (index_1, index_2, index_3, index_4, index_5, index_6, index_7)
        ) ' . $collate;

		$wpdb->query( $cleverreachTestTableInstallScript );
	}
}
