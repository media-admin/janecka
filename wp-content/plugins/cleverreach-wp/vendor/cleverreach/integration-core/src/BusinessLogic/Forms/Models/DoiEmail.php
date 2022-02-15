<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models;

use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Configuration\EntityConfiguration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Configuration\IndexMap;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Entity;

/**
 * Class DoiEmail
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models
 */
class DoiEmail extends Entity
{
    const CLASS_NAME = __CLASS__;
    /**
     * CleverReach form ID.
     *
     * @var int
     */
    protected $formId;
    /**
     * Email address.
     *
     * @var string
     */
    protected $email;
    /**
     * User IP address.
     *
     * @var string
     */
    protected $ip;
    /**
     * Referer.
     *
     * @var string
     */
    protected $referer;
    /**
     * User agent.
     *
     * @var string
     */
    protected $agent;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array('id', 'formId', 'email', 'ip', 'referer', 'agent');

    /**
     * DoiEmail constructor.
     *
     * @param int $formId
     * @param string $email
     * @param string $ip
     * @param string $referer
     * @param string $agent
     */
    public function __construct($formId, $email, $ip, $referer, $agent)
    {
        $this->formId = $formId;
        $this->email = $email;
        $this->ip = $ip;
        $this->referer = $referer;
        $this->agent = $agent;
    }

    /**
     * @return int
     */
    public function getFormId()
    {
        return $this->formId;
    }

    /**
     * @param int $formId
     */
    public function setFormId($formId)
    {
        $this->formId = $formId;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * @param string $referer
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;
    }

    /**
     * @return string
     */
    public function getAgent()
    {
        return $this->agent;
    }

    /**
     * @param string $agent
     */
    public function setAgent($agent)
    {
        $this->agent = $agent;
    }

    /**
     * Returns entity configuration object.
     *
     * @return EntityConfiguration Configuration object.
     */
    public function getConfig()
    {
        $map = new IndexMap();
        $map->addIntegerIndex('formId')
            ->addStringIndex('email');

        return new EntityConfiguration($map, 'DoiEmail');
    }
}
