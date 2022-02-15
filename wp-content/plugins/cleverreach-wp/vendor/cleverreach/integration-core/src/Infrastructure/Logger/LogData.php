<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Logger;

/**
 * Class LogData
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Logger
 */
class LogData
{
    /**
     * Name of integration
     *
     * @var string
     */
    private $integration;
    /**
     * User account ID.
     *
     * @var string
     */
    private $userAccount;
    /**
     * Log level.
     *
     * @var int
     */
    private $logLevel;
    /**
     * Timestamp when log is called.
     *
     * @var int
     */
    private $timestamp;
    /**
     * Component that called log.
     *
     * @var string
     */
    private $component;
    /**
     * Log message.
     *
     * @var string
     */
    private $message;

    /**
     * LogData constructor.
     *
     * @param string $integration Name of integration
     * @param string $userAccount User account ID.
     * @param int $logLevel Log level.
     * @param int $timestamp Timestamp when log is called.
     * @param string $component Component that made log.
     * @param string $message Log message.
     */
    public function __construct($integration, $userAccount, $logLevel, $timestamp, $component, $message)
    {
        $this->integration = $integration;
        $this->userAccount = $userAccount;
        $this->logLevel = $logLevel;
        $this->component = $component;
        $this->timestamp = $timestamp;
        $this->message = $message;
    }

    /**
     * Get name of integration.
     *
     * @return string
     *   Integration name.
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * Get user account ID.
     *
     * @return string
     *   User account ID.
     */
    public function getUserAccount()
    {
        return $this->userAccount;
    }

    /**
     * Get log level (error, warning, info or debug).
     *
     * @return int
     *   Log level:
     *    - error => 0
     *    - warning => 1
     *    - info => 2
     *    - debug => 3
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    /**
     * Get log timestamp.
     *
     * @return int
     *   Timestamp when log is made.
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get component that made log.
     *
     * @return string
     *   Default is 'Core'.
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * Get log message.
     *
     * @return string
     *   Log message.
     */
    public function getMessage()
    {
        return $this->message;
    }
}
