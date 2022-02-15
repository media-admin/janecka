<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\Utility;

use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;

/**
 * Class Serializer
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\Utility
 */
abstract class Serializer
{
    const CLASS_NAME = __CLASS__;

    /**
     * Singleton instance.
     *
     * @var Serializer
     */
    protected static $instance;

    /**
     * Gets serializer instance.
     *
     * @return static Instance of serializer.
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = ServiceRegister::getService(static::CLASS_NAME);
        }

        return static::$instance;
    }

    /**
     * Serializes data to a string.
     *
     * @param mixed $data
     *
     * @return string
     */
    public static function serialize($data)
    {
        return static::getInstance()->doSerialize($data);
    }

    /**
     * Unserializes data string.
     *
     * @param string $data
     *
     * @return mixed
     */
    public static function unserialize($data)
    {
        return static::getInstance()->doUnserialize($data);
    }

    /**
     * Performs concrete serialization.
     *
     * @param mixed $data
     *
     * @return string
     */
    abstract protected function doSerialize($data);

    /**
     * Performs concrete unserialization.
     *
     * @param string $data
     *
     * @return mixed
     */
    abstract protected function doUnserialize($data);
}
