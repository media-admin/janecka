<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\Runnable;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class QueueItemStarter
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution
 */
class QueueItemStarter implements Runnable
{
    /**
     * ID of queue item to start.
     *
     * @var int
     */
    private $queueItemId;
    /**
     * Queue instance.
     *
     * @var Queue
     */
    private $queue;
    /**
     * Configuration service instance.
     *
     * @var Configuration
     */
    private $configService;

    /**
     * QueueItemStarter constructor.
     *
     * @param int $queueItemId ID of queue item to start.
     */
    public function __construct($queueItemId)
    {
        $this->queueItemId = $queueItemId;
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
        return new self($array['queueItemId']);
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(array($this->queueItemId));
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->queueItemId) = Serializer::unserialize($serialized);
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array('queueItemId' => $this->queueItemId);
    }

    /**
     * Starts runnable run logic.
     *
     * @throws Exceptions\QueueStorageUnavailableException
     */
    public function run()
    {
        /** @var \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem $queueItem */
        $queueItem = $this->fetchItem();

        if ($queueItem === null || ($queueItem->getStatus() !== QueueItem::QUEUED)) {
            Logger::logDebug(
                json_encode(array(
                    'Message' => 'Fail to start task execution because task no longer exists or it is not in queued state anymore.',
                    'TaskId' => $this->getQueueItemId(),
                    'Status' => $queueItem !== null ? $queueItem->getStatus() : 'unknown'
                ))
            );

            return;
        }

        try {
            $this->getConfigService()->setContext($queueItem->getContext());
            $this->getQueueService()->start($queueItem);
            $this->getQueueService()->finish($queueItem);
        } catch (\Exception $ex) {
            if (QueueItem::IN_PROGRESS === $queueItem->getStatus()) {
                $this->getQueueService()->fail($queueItem, $ex->getMessage());
            }

            Logger::logError(
                json_encode(array(
                    'Message' => 'Fail to start task execution.',
                    'TaskId' => $this->getQueueItemId(),
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                ))
            );
        }
    }

    /**
     * Gets ID of a queue item that will be run.
     *
     * @return int
     *   Queue item ID.
     */
    public function getQueueItemId()
    {
        return $this->queueItemId;
    }

    /**
     * Gets queue item by ID.
     *
     * @return QueueItem|null
     *   If not found null is returned.
     */
    private function fetchItem()
    {
        $queueItem = null;

        try {
            $queueItem = $this->getQueueService()->find($this->queueItemId);
        } catch (\Exception $ex) {
            Logger::logError(json_encode(array(
                'Message' => 'Fail to start task execution.',
                'TaskId' => $this->getQueueItemId(),
                'ExceptionMessage' => $ex->getMessage(),
                'ExceptionTrace' => $ex->getTraceAsString()
            )));
        }

        return $queueItem;
    }

    /**
     * Gets queue service instance.
     *
     * @return Queue
     *   Instance of queue service.
     */
    private function getQueueService()
    {
        if ($this->queue === null) {
            $this->queue = ServiceRegister::getService(Queue::CLASS_NAME);
        }

        return $this->queue;
    }

    /**
     * Gets configuration service instance.
     *
     * @return Configuration
     *   Instance of configuration service.
     */
    private function getConfigService()
    {
        if ($this->configService === null) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
