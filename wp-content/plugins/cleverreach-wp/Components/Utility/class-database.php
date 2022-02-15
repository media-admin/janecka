<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Utility;

use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;

/**
 * Class Database
 *
 * @package CleverReach\WordPress\Components\Utility
 */
class Database {

	const CONFIG_TABLE     = 'cleverreach_config';
	const PROCESS_TABLE    = 'cleverreach_process';
	const QUEUE_TABLE      = 'cleverreach_queue';
	const ROLES_TABLE      = 'user_roles';
	const POSTS_TABLE      = 'posts';
	const POSTS_META_TABLE = 'postmeta';
	const ENTITY_TABLE     = 'cleverreach_entity';

	/**
	 * Database session
	 *
	 * @var \wpdb
	 */
	private $db;

	/**
	 * Database constructor
	 *
	 * @param /wpdb $db Database session.
	 */
	public function __construct( $db ) {
		$this->db = $db;
	}

	/**
	 * Get full table name
	 *
	 * @param string $table_name Table name.
	 *
	 * @return string
	 */
	public static function table( $table_name ) {
		global $wpdb;

		return $wpdb->prefix . $table_name;
	}

	/**
	 * Return newsletter column for added newsletter field
	 *
	 * @return string
	 */
	public static function get_newsletter_column() {
		global $wpdb;

		return $wpdb->prefix . 'cr_newsletter_status';
	}

	/**
	 * Checks if plugin was already installed and initialized.
	 *
	 * @return bool
	 */
	public function plugin_already_initialized() {
		return $this->db->get_var( "SHOW TABLES LIKE '" . self::table( self::QUEUE_TABLE ) . "'" ) === self::table( self::QUEUE_TABLE );
	}

	/**
	 * Executes installation scripts
	 */
	public function install() {
		$queries = $this->get_queries_for_install();
		foreach ( $queries as $query ) {
			$this->db->query( $query );
		}
	}

	/**
	 * Executes uninstallation scripts
	 */
	public function uninstall() {
		$queries = $this->get_queries_for_uninstall();
		foreach ( $queries as $query ) {
			$this->db->query( $query );
		}
	}

	/**
	 * Return queries for plugin install
	 *
	 * @return array
	 */
	private function get_queries_for_install() {
		$queries = array();

		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . self::table( self::CONFIG_TABLE ) . '` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(255),
            `value` TEXT,
            PRIMARY KEY (`id`)
        ) DEFAULT CHARACTER SET utf8';

		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . self::table( self::PROCESS_TABLE ) . '` (
            `id` VARCHAR(50) NOT NULL,
            `runner` VARCHAR(500) NOT NULL,
            PRIMARY KEY (`id`)
        ) DEFAULT CHARACTER SET utf8';

		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . self::table( self::QUEUE_TABLE ) . '` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `status` VARCHAR(30) NOT NULL,
            `priority` INT(11) NOT NULL DEFAULT ' . QueueItem::PRIORITY_MEDIUM . ',
            `type` VARCHAR(100) NOT NULL,
            `queueName` VARCHAR(50) NOT NULL,
            `progress` INT(11) NOT NULL DEFAULT 0,
			`lastExecutionProgress` INT(11) DEFAULT 0,
            `retries` INT(11) NOT NULL DEFAULT 0,
            `failureDescription` VARCHAR(255),
            `serializedTask` LONGTEXT NOT NULL,
            `createTimestamp` INT(11),
            `queueTimestamp` INT(11),
            `lastUpdateTimestamp` INT(11),
            `startTimestamp` INT(11),
            `finishTimestamp` INT(11),
            `failTimestamp` INT(11),
            PRIMARY KEY (`id`)
        ) DEFAULT CHARACTER SET utf8';

		$queries[] = 'CREATE TABLE IF NOT EXISTS `' . self::table( self::ENTITY_TABLE ) . '` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `type` VARCHAR(127),
            `index_1` VARCHAR(127),
            `index_2` VARCHAR(127),
            `index_3` VARCHAR(127),
            `index_4` VARCHAR(127),
            `index_5` VARCHAR(127),
            `index_6` VARCHAR(127),
            `index_7` VARCHAR(127),
            `data` LONGTEXT,
            PRIMARY KEY (`id`)
        )';

		return $queries;
	}

	/**
	 * Return queries for plugin uninstall
	 *
	 * @return array
	 */
	private function get_queries_for_uninstall() {
		$queries = array();

		$queries[] = 'DROP TABLE IF EXISTS ' . self::table( self::CONFIG_TABLE );
		$queries[] = 'DROP TABLE IF EXISTS ' . self::table( self::PROCESS_TABLE );
		$queries[] = 'DROP TABLE IF EXISTS ' . self::table( self::QUEUE_TABLE );
		$queries[] = 'DROP TABLE IF EXISTS ' . self::table( self::ENTITY_TABLE );
		$queries[] = 'DELETE FROM ' . self::table( self::POSTS_META_TABLE ) . '  WHERE `meta_key` = "_cleverreach-wp"';

		return $queries;
	}
}
