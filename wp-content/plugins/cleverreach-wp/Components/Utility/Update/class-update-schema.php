<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Utility\Update;

use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Queue;

/**
 * Class Update_Schema
 *
 * @package CleverReach\WordPress\Components\Utility
 */
abstract class Update_Schema {

	protected $db;
	/**
	 * @var Queue
	 */
	protected $queue_service;
	/**
	 * @var Config_Service
	 */
	protected $config_service;
	/**
	 * @var TaskQueueStorage
	 */
	protected $task_queue_storage;

	/**
	 * Update_Schema constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->config_service = ServiceRegister::getService(Configuration::CLASS_NAME);
		$this->queue_service = ServiceRegister::getService( Queue::CLASS_NAME );
		$this->task_queue_storage = ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);
	}

	/**
	 * Run update logic for current migration
	 */
	abstract public function update();
}