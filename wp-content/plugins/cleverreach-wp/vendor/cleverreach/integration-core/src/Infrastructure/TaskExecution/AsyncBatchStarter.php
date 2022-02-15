<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\Runnable;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

class AsyncBatchStarter implements Runnable
{
    /**
     * @var int
     */
    private $batchSize;

    /**
     * @var self[]
     */
    private $subBatches = array();

    /**
     * @var \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\Runnable[]
     */
    private $runners = array();

    /**
     * @var int
     */
    private $addIndex = 0;

    /**
     * Instance of async process started.
     *
     * @var AsyncProcessStarter
     */
    private $asyncProcessStarter;

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        return Serializer::serialize(array($this->batchSize, $this->subBatches, $this->runners, $this->addIndex));
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        list($this->batchSize, $this->subBatches, $this->runners, $this->addIndex) = Serializer::unserialize($serialized);
    }

    /**
     * @inheritDoc
     */
    public static function fromArray($data)
    {
        $runners = array();
        $subBatches = array();
        foreach ($data['runners'] as $runner) {
            $runners[] = Serializer::unserialize($runner);
        }

        foreach ($data['subBatches'] as $subBatch) {
            $subBatches[] = Serializer::unserialize($subBatch);
        }

        $instance = new self($data['batchSize'], $runners);
        $instance->subBatches = $subBatches;
        $instance->addIndex = $data['addIndex'];

        return $instance;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $runners = array();
        $subBatches = array();
        foreach ($this->runners as $runner) {
            $runners[] = Serializer::serialize($runner);
        }

        foreach ($this->subBatches as $subBatch) {
            $subBatches[] = Serializer::serialize($subBatch);
        }

        return array(
            'batchSize' => $this->batchSize,
            'subBatches' => $subBatches,
            'runners' => $runners,
            'addIndex' => $this->addIndex,
        );
    }

    /**
     * AsyncBatchStarter constructor.
     *
     * @param int $batchSize
     * @param \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\Runnable[] $runners
     */
    public function __construct($batchSize, array $runners = array())
    {
        $this->batchSize = $batchSize;
        foreach ($runners as $runner) {
            $this->addRunner($runner);
        }
    }

    /**
     * Add runnable to the batch
     *
     * @param \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Exposed\Runnable $runner
     */
    public function addRunner(Runnable $runner)
    {
        if ($this->isCapacityFull()) {
            $this->subBatches[$this->addIndex]->addRunner($runner);
            $this->addIndex = ($this->addIndex + 1) % $this->batchSize;
            return;
        }

        if ($this->isRunnersCapacityFull()) {
            $this->subBatches[] = new self($this->batchSize, $this->runners);
            $this->runners = array();
        }

        $this->runners[] = $runner;
    }

    /**
     * @inheritDoc
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException
     */
    public function run()
    {
        foreach ($this->subBatches as $subBatch) {
            $this->getAsyncProcessStarter()->start($subBatch);
        }

        foreach ($this->runners as $runner) {
            $this->getAsyncProcessStarter()->start($runner);
        }
    }

    /**
     * Returns max number of nested sub-batch levels. No sub-batches will return 0, one sub-batch 1, sub-batch with
     * sub-batch 2....
     *
     * @return int Max number of nested sub-batch levels
     */
    public function getMaxNestingLevels()
    {
        if (empty($this->subBatches)) {
            return 0;
        }

        $maxLevel = 0;
        foreach ($this->subBatches as $subBatch) {
            $subBatchMaxLevel = $subBatch->getMaxNestingLevels();
            if ($maxLevel < $subBatchMaxLevel) {
                $maxLevel = $subBatchMaxLevel;
            }
        }

        return $maxLevel + 1;
    }

    /**
     * Calculates time required for whole batch with its sub-batches to run. Wait time calculation si based on HTTP
     * request duration provided as method argument
     *
     * @param float $requestDuration Expected HTTP request duration in milliseconds
     *
     * @return float Wait period in milliseconds that is required for whole batch (with sub-batches) to run
     */
    public function getWaitTime($requestDuration)
    {
        // Without sub-batches all requests are started as soon as run method is done
        if (empty($this->subBatches)) {
            return 0;
        }

        $subBatchWaitTime = $this->batchSize * $this->getMaxNestingLevels() * $requestDuration;
        $runnersStartupTime = count($this->runners) * $requestDuration;
        return $subBatchWaitTime - $runnersStartupTime;
    }

    public function __toString()
    {
        $out = implode(', ', $this->subBatches);
        for ($i = 0; $i < count($this->runners); $i++) {
            $out .= empty($out) ? "R" : ", R";
        }

        return "B({$out})";
    }

    /**
     * @return bool
     *      True if current batch cant take any more runners nor create any more sub-batches itself; False otherwise
     */
    protected function isCapacityFull()
    {
        return $this->isRunnersCapacityFull() && $this->isSubBatchCapacityFull();
    }

    /**
     * @return bool
     *      True if current batch cant create any more sub-batches itself; False otherwise
     */
    protected function isSubBatchCapacityFull()
    {
        return count($this->subBatches) >= $this->batchSize;
    }

    /**
     * @return bool
     *      True if current batch cant take any more runners itself; False otherwise
     */
    protected function isRunnersCapacityFull()
    {
        return count($this->runners) >= $this->batchSize;
    }

    /**
     * Gets instance of async process starter.
     *
     * @return AsyncProcessStarter
     *   Instance of async process starter.
     */
    protected function getAsyncProcessStarter()
    {
        if ($this->asyncProcessStarter === null) {
            $this->asyncProcessStarter = ServiceRegister::getService(AsyncProcessStarter::CLASS_NAME);
        }

        return $this->asyncProcessStarter;
    }
}