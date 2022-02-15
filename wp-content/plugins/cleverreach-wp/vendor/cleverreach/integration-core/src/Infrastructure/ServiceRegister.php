<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure;

/**
 * Class ServiceRegister
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure
 */
class ServiceRegister
{
    /**
     * Service register instance.
     *
     * @var ServiceRegister
     */
    private static $instance;

    /**
     * Array of registered services.
     *
     * @var array
     */
    private $services;

    /**
     * ServiceRegister constructor.
     *
     * @param array $services Associative array where key is type and value is class instance.
     */
    public function __construct(array $services = array())
    {
        if (!empty($services)) {
            foreach ($services as $type => $service) {
                $this->register($type, $service);
            }
        }

        self::$instance = $this;
    }

    /**
     * Getting service register instance.
     *
     * @return ServiceRegister
     *   Instance of service register.
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new ServiceRegister();
        }

        return self::$instance;
    }

    /**
     * Gets service instance by type.
     *
     * @param string $type Class name.
     * @return mixed
     *   Instance of required class.
     * @throws \InvalidArgumentException
     *   When class is not defined.
     */
    public static function getService($type)
    {
        return static::getInstance()->get($type);
    }

    /**
     * Registers service with delegate as second parameter which represents function for creating new service instance.
     *
     * @param string $type Class name.
     * @param callable|null $delegate Function for creating new service instance.
     */
    public static function registerService($type, $delegate)
    {
        static::getInstance()->register($type, $delegate);
    }

    /**
     * Register service class.
     *
     * @param string $type Class name.
     * @param callable|null $delegate Function for creating new service instance.
     */
    private function register($type, $delegate)
    {
        if (!empty($this->services[$type])) {
            throw new \InvalidArgumentException("$type is already defined.");
        }

        if (!is_callable($delegate)) {
            throw new \InvalidArgumentException("$type delegate is not callable.");
        }

        $this->services[$type] = $delegate;
    }

    /**
     * Getting service instance by type.
     *
     * @param string $type Class name.
     * @return mixed
     *   Instance of required class.
     * @throws \InvalidArgumentException
     *   When class is not defined.
     */
    private function get($type)
    {
        if (empty($this->services[$type])) {
            throw new \InvalidArgumentException("$type is not defined.");
        }

        return call_user_func($this->services[$type]);
    }
}
