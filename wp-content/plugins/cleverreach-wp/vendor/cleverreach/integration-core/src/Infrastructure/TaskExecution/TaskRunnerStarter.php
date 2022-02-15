<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\Runnable;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerRunException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Events\EventBus;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class TaskRunnerStarter
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution
 */
class TaskRunnerStarter implements Runnable
{
    /**
     * Unique runner guid.
     *
     * @var string
     */
    private $guid;
    /**
     * Instance of task runner status storage.
     *
     * @var TaskRunnerStatusStorageInterface
     */
    private $runnerStatusStorage;
    /**
     * Instance of task runner.
     *
     * @var TaskRunner
     */
    private $taskRunner;
    /**
     * Instance of task runner wakeup service.
     *
     * @var TaskRunnerWakeupInterface
     */
    private $taskWakeup;
    /**
     * Instance of event bus
     *
     * @var EventBus
     */
    private $eventBus;

    /**
     * TaskRunnerStarter constructor.
     *
     * @param string $guid Unique runner guid.
     */
    public function __construct($guid)
    {
        $this->guid = $guid;
    }

    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array)
    {
        return new self($array['guid']);
    }

    /**
     * String representation of object.
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(array($this->guid));
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->guid) = Serializer::unserialize($serialized);
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array('guid' => $this->guid);
    }

    /**
     * Get unique runner guid.
     *
     * @return string
     *   Unique runner string.
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * Starts synchronously currently active task runner instance
     */
    public function run()
    {
        try {
            $this->doRun();
        } catch (TaskRunnerStatusStorageUnavailableException $ex) {
            Logger::logError(
                json_encode(array(
                    'Message' => 'Failed to run task runner. Runner status storage unavailable.',
                    'ExceptionMessage' => $ex->getMessage(),
                ))
            );
            Logger::logDebug(
                json_encode(array(
                    'Message' => 'Failed to run task runner. Runner status storage unavailable.',
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                ))
            );
        } catch (TaskRunnerRunException $ex) {
            Logger::logInfo(
                json_encode(array(
                    'Message' => $ex->getMessage(),
                    'ExceptionMessage' => $ex->getMessage(),
                ))
            );
            Logger::logDebug(
                json_encode(array(
                    'Message' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                ))
            );
        } catch (\Exception $ex) {
            Logger::logError(
                json_encode(array(
                    'Message' => 'Failed to run task runner. Unexpected error occurred.',
                    'ExceptionMessage' => $ex->getMessage(),
                ))
            );
            Logger::logDebug(
                json_encode(array(
                    'Message' => 'Failed to run task runner. Unexpected error occurred.',
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                ))
            );
        }
    }

    /**
     * Run task runner.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerRunException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    private function doRun()
    {
        $runnerStatus = $this->getRunnerStorage()->getStatus();
        if ($this->guid !== $runnerStatus->getGuid()) {
            throw new TaskRunnerRunException('Failed to run task runner. Runner guid is not set as active.');
        }

        if ($runnerStatus->isExpired()) {
            $this->getTaskWakeup()->wakeup();
            throw new TaskRunnerRunException('Failed to run task runner. Runner is expired.');
        }

        $this->getTaskRunner()->setGuid($this->guid);
        $this->getTaskRunner()->run();

        $this->getEventBus()->fire(new TickEvent());
    }

    /**
     * Gets task runner status storage instance.
     *
     * @return TaskRunnerStatusStorageInterface
     *   Instance of runner status storage service.
     */
    private function getRunnerStorage()
    {
        if ($this->runnerStatusStorage === null) {
            $this->runnerStatusStorage = ServiceRegister::getService(TaskRunnerStatusStorageInterface::CLASS_NAME);
        }

        return $this->runnerStatusStorage;
    }

    /**
     * Gets task runner instance.
     *
     * @return TaskRunner
     *   Instance of runner service.
     */
    private function getTaskRunner()
    {
        if ($this->taskRunner === null) {
            $this->taskRunner = ServiceRegister::getService(TaskRunner::CLASS_NAME);
        }

        return $this->taskRunner;
    }

    /**
     * Gets task runner wakeup instance.
     *
     * @return TaskRunnerWakeupInterface
     *   Instance of runner wakeup service.
     */
    private function getTaskWakeup()
    {
        if ($this->taskWakeup === null) {
            $this->taskWakeup = ServiceRegister::getService(TaskRunnerWakeupInterface::CLASS_NAME);
        }

        return $this->taskWakeup;
    }

    /**
     * Gets event bus instance
     *
     * @return EventBus
     *   Instance of event bus
     */
    private function getEventBus()
    {
        if ($this->eventBus === null) {
            $this->eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);
        }

        return $this->eventBus;
    }
}
