<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\GuidProvider;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class TaskRunnerWakeup
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution
 */
class TaskRunnerWakeup implements TaskRunnerWakeupInterface
{
    /**
     * Instance of async process starter.
     *
     * @var AsyncProcessStarter
     */
    private $asyncProcessStarter;
    /**
     * Instance of task runner status storage.
     *
     * @var TaskRunnerStatusStorage
     */
    private $runnerStatusStorage;
    /**
     * Instance of time provider.
     *
     * @var TimeProvider
     */
    private $timeProvider;
    /**
     * Instance of GUID provider.
     *
     * @var GuidProvider
     */
    private $guidProvider;

    /**
     * Wakes up TaskRunner instance asynchronously.
     *
     * If active instance is already running do nothing.
     */
    public function wakeup()
    {
        try {
            $this->doWakeup();
        } catch (TaskRunnerStatusChangeException $ex) {
            Logger::logDebug(
                json_encode(array(
                    'Message' => 'Fail to wakeup task runner. Runner status storage failed to set new active state.',
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                ))
            );
        } catch (TaskRunnerStatusStorageUnavailableException $ex) {
            Logger::logDebug(
                json_encode(array(
                    'Message' => 'Fail to wakeup task runner. Runner status storage unavailable.',
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                ))
            );
        } catch (\Exception $ex) {
            Logger::logDebug(
                json_encode(array(
                    'Message' => 'Fail to wakeup task runner. Unexpected error occurred.',
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                ))
            );
        }
    }

    /**
     * Starts new async process if not already running.
     *
     * @throws Exceptions\ProcessStarterSaveException
     * @throws Exceptions\TaskRunnerStatusChangeException
     * @throws Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    private function doWakeup()
    {
        $runnerStatus = $this->getRunnerStorage()->getStatus();
        $currentGuid = $runnerStatus->getGuid();
        if (!empty($currentGuid) && !$runnerStatus->isExpired()) {
            return;
        }

        if ($runnerStatus->isExpired()) {
            $this->runnerStatusStorage->setStatus(TaskRunnerStatus::createNullStatus());
            Logger::logDebug('Expired task runner detected, wakeup component will start new instance.');
        }

        $guid = $this->getGuidProvider()->generateGuid();

        $this->runnerStatusStorage->setStatus(new TaskRunnerStatus(
            $guid,
            $this->getTimeProvider()->getCurrentLocalTime()->getTimestamp()
        ));

        $this->getAsyncProcessStarter()->start(new TaskRunnerStarter($guid));
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
     * Gets guid provider instance.
     *
     * @return GuidProvider
     *   Instance of guid provider.
     */
    private function getGuidProvider()
    {
        if ($this->guidProvider === null) {
            $this->guidProvider = ServiceRegister::getService(GuidProvider::CLASS_NAME);
        }

        return $this->guidProvider;
    }

    /**
     * Gets time provider instance.
     *
     * @return TimeProvider
     *   Instance of time provider.
     */
    private function getTimeProvider()
    {
        if ($this->timeProvider === null) {
            $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        }

        return $this->timeProvider;
    }

    /**
     * Gets instance of async process starter.
     *
     * @return AsyncProcessStarter
     *   Instance of async process starter.
     */
    private function getAsyncProcessStarter()
    {
        if ($this->asyncProcessStarter === null) {
            $this->asyncProcessStarter = ServiceRegister::getService(AsyncProcessStarter::CLASS_NAME);
        }

        return $this->asyncProcessStarter;
    }
}
