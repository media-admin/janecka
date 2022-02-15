<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Logger;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\DefaultLoggerAdapter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class Logger
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Logger
 */
class Logger
{
    const ERROR = 0;
    const WARNING = 1;
    const INFO = 2;
    const DEBUG = 3;

    /**
     * Singleton instance.
     *
     * @var Logger
     */
    private static $instance;

    /**
     * Integration logger
     *
     * @var ShopLoggerAdapter
     */
    private $shopLogger;

    /**
     * Default logger
     *
     * @var DefaultLogger
     */
    private $defaultLogger;

    /**
     * Gets logger component instance.
     *
     * @return self
     *   Instance of logger.
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Logger();
        }

        return self::$instance;
    }

    /**
     * Logger constructor.
     */
    public function __construct()
    {
        $this->defaultLogger = ServiceRegister::getService(DefaultLoggerAdapter::CLASS_NAME);
        $this->shopLogger = ServiceRegister::getService(ShopLoggerAdapter::CLASS_NAME);

        self::$instance = $this;
    }

    /**
     * Logging error message.
     *
     * @param string $message Message to log.
     * @param string $component Component that called log.
     */
    public static function logError($message, $component = 'Core')
    {
        static::getInstance()->logMessage(self::ERROR, $message, $component);
    }

    /**
     * Logging warning message.
     *
     * @param string $message Message to log.
     * @param string $component Component that called log.
     */
    public static function logWarning($message, $component = 'Core')
    {
        static::getInstance()->logMessage(self::WARNING, $message, $component);
    }

    /**
     * Logging info message.
     *
     * @param string $message Message to log.
     * @param string $component Component that called log.
     */
    public static function logInfo($message, $component = 'Core')
    {
        static::getInstance()->logMessage(static::INFO, $message, $component);
    }

    /**
     * Logging debug message.
     *
     * @param string $message Message to log.
     * @param string $component Component that called log.
     */
    public static function logDebug($message, $component = 'Core')
    {
        static::getInstance()->logMessage(self::DEBUG, $message, $component);
    }

    /**
     * Logging message.
     *
     * @param int $level Log level (error, warning, info or debug).
     * @param string $message Message to log.
     * @param string $component Component that called log.
     */
    private function logMessage($level, $message, $component)
    {
        $config = Configuration::getInstance();
        $logData = new LogData(
            $config->getIntegrationName(),
            $config->getUserAccountId(),
            $level,
            date('Y-m-d H:i:s'),
            $component,
            $message
        );

        // If default logger is turned on and
        // message level is lower or equal than
        // set in configuration
        if ($config->isDefaultLoggerEnabled() && $level <= $config->getMinLogLevel()) {
            $this->defaultLogger->logMessage($logData);
        }
        
        $this->shopLogger->logMessage($logData);
    }
}
