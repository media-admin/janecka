<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskEvents;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Events\Event;

/**
 * Class ProgressedTaskEvent
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskEvents
 */
class ProgressedTaskEvent extends Event
{
    const CLASS_NAME = __CLASS__;

    /**
     * Integer representation of progress percentage in base points.
     *
     * @var int
     */
    private $progressPercentBasePoints;

    /**
     * ProgressedTaskEvent constructor.
     *
     * @param int $progressPercentBasePoints Integer representation of progress
     *      percentage in base points, as value between 0 and 10000. One base
     *      point is equal to 0.01%. For example 23.58% is equal to 2358 base points.
     *
     * @throws \InvalidArgumentException
     *   In case when progress percent is outside of 0 - 100 boundaries or not an integer.
     */
    public function __construct($progressPercentBasePoints)
    {
        if (!is_int($progressPercentBasePoints) || $progressPercentBasePoints < 0 || 10000 < $progressPercentBasePoints) {
            throw new \InvalidArgumentException('Progress percentage must be value between 0 and 100.');
        }

        $this->progressPercentBasePoints = $progressPercentBasePoints;
    }

    /**
     * Gets progress base points in form of integer value between 0 and 10000.
     *
     * @return int
     *  Progress percentage.
     */
    public function getProgressBasePoints()
    {
        return $this->progressPercentBasePoints;
    }

    /**
     * Gets progress in percentage rounded to 2 decimal value.
     *
     * @return float
     *   QueueItem progress in percentage rounded to 2 decimal value.
     */
    public function getProgressFormatted()
    {
        return round($this->progressPercentBasePoints / 100, 2);
    }
}
