<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity;

/**
 * Class ConnectionStatusResponse
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity
 */
class ConnectionStatusResponse
{
    /**
     * @var bool
     */
    protected $status;
    /**
     * @var string
     */
    protected $message;

    /**
     * ConnectionStatusResponse constructor.
     *
     * @param bool $status
     * @param string $message
     */
    public function __construct($status, $message = '')
    {
        $this->status = $status;
        $this->message = $message;
    }

    /**
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }
}
