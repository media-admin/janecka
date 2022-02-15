<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Utility;

use CleverReach\WordPress\Components\BusinessLogicServices\Notification_Service;
use CleverReach\WordPress\Components\Entities\Contact;
use CleverReach\WordPress\Components\Repositories\Base_Repository;
use CleverReach\WordPress\Components\Repositories\Schedule_Repository;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models\Form;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Attributes;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Notifications;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Recipients;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\AuthProxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\FormProxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\SurveyProxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\Models\Schedule;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Scheduler\ScheduleTickHandler;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeUpInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\ConfigRepositoryInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\HttpClient;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\TaskQueueStorage;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\DefaultLogger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryClassException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Queue;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskRunner;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskRunnerStatusStorage;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskRunnerWakeup;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Events\EventBus;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\GuidProvider;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\NativeSerializer;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;
use CleverReach\WordPress\Components\BusinessLogicServices\Attributes_Service;
use CleverReach\WordPress\Components\BusinessLogicServices\Recipient_Service;
use CleverReach\WordPress\Components\InfrastructureServices\Async_Process_Starter_Service;
use CleverReach\WordPress\Components\InfrastructureServices\Http_Client_Service;
use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\InfrastructureServices\Logger_Service;
use CleverReach\WordPress\Components\InfrastructureServices\Task_Queue_Storage_Service;
use CleverReach\WordPress\Components\Repositories\Config_Repository;

/**
 * Class Initializer
 *
 * @package CleverReach\WordPress\Components\Utility
 */
class Initializer {

	/**
	 * Registers all services, repositories and events
	 *
	 * @throws RepositoryClassException
	 */
	public static function register() {
		try {
			self::register_services();
			self::init_events();
			self::register_repositories();

		} catch ( \InvalidArgumentException $exception ) {
			// Don't do nothing if service is already registered.
			return;
		}
	}

	/**
	 * Register all services
	 */
	private static function register_services() {
		self::register_infrastructure_services();
		self::register_business_services();
	}

	/**
	 * Hook on events
	 */
	private static function init_events() {
		/** @var EventBus $eventBus */
		$eventBus = ServiceRegister::getService( EventBus::CLASS_NAME );
		$eventBus->when( TickEvent::CLASS_NAME,
			function () {
				$handler = new ScheduleTickHandler();
				$handler->handle();
			}
		);
	}

	/**
	 * Register ORM repositories
	 *
	 * @throws RepositoryClassException
	 */
	private static function register_repositories() {
		RepositoryRegistry::registerRepository( Form::getClassName(), Base_Repository::getClassName() );
		RepositoryRegistry::registerRepository( Schedule::getClassName(), Schedule_Repository::getClassName() );
		RepositoryRegistry::registerRepository( Contact::getClassName(), Base_Repository::getClassName() );
	}

	/**
	 * Register infrastructure services
	 */
	private static function register_infrastructure_services() {
		ServiceRegister::registerService(
			TimeProvider::CLASS_NAME,
			function () {
				return new TimeProvider();
			}
		);
		ServiceRegister::registerService(
			Queue::CLASS_NAME,
			function () {
				return new Queue();
			}
		);
		ServiceRegister::registerService(
			TaskRunnerWakeUpInterface::CLASS_NAME,
			function () {
				return new TaskRunnerWakeup();
			}
		);
		ServiceRegister::registerService(
			TaskRunner::CLASS_NAME,
			function () {
				return new TaskRunner();
			}
		);
		ServiceRegister::registerService(
			GuidProvider::CLASS_NAME,
			function () {
				return new GuidProvider();
			}
		);
		ServiceRegister::registerService(
			DefaultLoggerAdapter::CLASS_NAME,
			function () {
				return new DefaultLogger();
			}
		);
		ServiceRegister::registerService(
			TaskRunnerStatusStorageInterface::CLASS_NAME,
			function () {
				return new TaskRunnerStatusStorage();
			}
		);
		ServiceRegister::registerService(
			EventBus::CLASS_NAME,
			function () {
				return EventBus::getInstance();
			}
		);
		ServiceRegister::registerService(
			Serializer::CLASS_NAME,
			function () {
				return new NativeSerializer();
			}
		);
	}

	/**
	 * Register business logic services
	 */
	private static function register_business_services() {
		ServiceRegister::registerService(
			ShopLoggerAdapter::CLASS_NAME,
			function () {
				return new Logger_Service();
			}
		);
		ServiceRegister::registerService(
			Configuration::CLASS_NAME,
			function () {
				return new Config_Service();
			}
		);
		ServiceRegister::registerService(
			ConfigRepositoryInterface::CLASS_NAME,
			function () {
				return new Config_Repository();
			}
		);
		ServiceRegister::registerService(
			HttpClient::CLASS_NAME,
			function () {
				return new Http_Client_Service();
			}
		);
		ServiceRegister::registerService(
			AsyncProcessStarter::CLASS_NAME,
			function () {
				return new Async_Process_Starter_Service();
			}
		);
		ServiceRegister::registerService(
			TaskQueueStorage::CLASS_NAME,
			function () {
				return new Task_Queue_Storage_Service();
			}
		);
		ServiceRegister::registerService(
			Attributes::CLASS_NAME,
			function () {
				return new Attributes_Service();
			}
		);
		ServiceRegister::registerService(
			Recipients::CLASS_NAME,
			function () {
				return new Recipient_Service();
			}
		);
		ServiceRegister::registerService(
			ProxyInterface::CLASS_NAME,
			function () {
				return new Proxy();
			}
		);
		ServiceRegister::registerService(
			AuthProxy::CLASS_NAME,
			function () {
				return new AuthProxy();
			}
		);
		ServiceRegister::registerService(
			FormProxy::CLASS_NAME,
			function () {
				return new FormProxy();
			}
		);
		ServiceRegister::registerService(
			SurveyProxy::CLASS_NAME,
			function () {
				return new SurveyProxy();
			}
		);
		ServiceRegister::registerService(
			Notifications::CLASS_NAME,
			function () {
				return new Notification_Service();
			}
		);
	}
}
