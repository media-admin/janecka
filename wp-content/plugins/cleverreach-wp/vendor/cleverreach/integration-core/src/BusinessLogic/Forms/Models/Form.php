<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models;

use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Configuration\EntityConfiguration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Configuration\IndexMap;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Entity;

/**
 * Class Form
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Forms\Models
 */
class Form extends Entity
{
    const CLASS_NAME = __CLASS__;
    /**
     * CleverReach form id
     *
     * @var int
     */
    protected $formId;
    /**
     * Form name.
     *
     * @var string
     */
    protected $name;
    /**
     * Form context.
     *
     * @var string
     */
    protected $context;
    /**
     * Form html
     *
     * @var string
     */
    protected $html;
    /**
     * @var int
     */
    protected $lastUpdateTimestamp;
    /**
     * Form html hash value
     *
     * @var string
     */
    protected $hash;
    /**
     * Group ID.
     *
     * @var int
     */
    protected $groupId;
    /**
     * Group name.
     *
     * @var string
     */
    protected $groupName;
    /**
     * Array of field names.
     *
     * @var array
     */
    protected $fields = array(
        'id',
        'formId',
        'name',
        'lastUpdateTimestamp',
        'context',
        'html',
        'hash',
        'groupId',
        'groupName'
    );

    /**
     * Form constructor.
     *
     * @param int $formId Form Identifier
     * @param string $name Form name
     * @param string $context
     */
    public function __construct($formId = null, $name = null, $context = '')
    {
        $this->formId = $formId;
        $this->name = $name;
        $this->context = $context;
    }

    /**
     * Gets form identifier.
     *
     * @return int CleverReach form identifier.
     */
    public function getFormId()
    {
        return $this->formId;
    }

    /**
     * Sets entity identifier.
     *
     * @param int $formId CleverReach form identifier.
     */
    public function setFormId($formId)
    {
        $this->formId = $formId;
    }

    /**
     * Return Form name.
     *
     * @return string
     *   Form name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set form name.
     *
     * @param string $name form name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Return form context.
     *
     * @return string
     *   form context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set form context.
     *
     * @param string $context form context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param string $html
     */
    public function setHtmlAndCreateHash($html)
    {
        $this->html = $html;
        $this->hash = md5($html);
    }

    /**
     * @return int
     */
    public function getLastUpdateTimestamp()
    {
        return $this->lastUpdateTimestamp;
    }

    /**
     * @param int $lastUpdateTimestamp
     */
    public function setLastUpdateTimestamp($lastUpdateTimestamp)
    {
        $this->lastUpdateTimestamp = $lastUpdateTimestamp;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return md5($this->getHtml());
    }

    /**
     * @return string
     */
    public function getGroupName()
    {
        return $this->groupName;
    }

    /**
     * @param string $groupName
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param int $groupId
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;
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
            ->addIntegerIndex('lastUpdateTimestamp')
            ->addStringIndex('hash')
            ->addStringIndex('context')
            ->addIntegerIndex('groupId')
            ->addStringIndex('groupName');

        return new EntityConfiguration($map, 'Form');
    }
}
