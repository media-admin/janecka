<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Utility;

/**
 * Class TimeProvider
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Utility
 */
class TimeProvider
{
    const CLASS_NAME = __CLASS__;

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Gets current time in default server timezone.
     *
     * @return \DateTime
     *   Current time.
     */
    public function getCurrentLocalTime()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new \DateTime();
    }

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * Returns @see \DateTime object from timestamp.
     *
     * @param int $timestamp Timestamp in seconds.
     *
     * @return \DateTime
     *  Object from timestamp.
     */
    public function getDateTime($timestamp)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new \DateTime("@{$timestamp}");
    }

    /**
     * Returns current timestamp with microseconds (float value with microsecond precision)
     *
     * @return float Current timestamp as float value with microseconds.
     */
    public function getMicroTimestamp()
    {
        return microtime(true);
    }

    /**
     * Delays execution for sleep time seconds
     *
     * @param int $sleepTime Sleep time in seconds.
     */
    public function sleep($sleepTime)
    {
        sleep($sleepTime);
    }
}
