<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity;

/**
 * Class RecipientAttribute
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity
 */
class RecipientAttribute
{
    /**
     * Attribute name
     *
     * @var string
     */
    private $name;
    /**
     * Attribute type
     *
     * @var string
     */
    private $type;
    /**
     * Attribute description.
     *
     * @var string
     */
    private $description;
    /**
     * Attribute preview value.
     *
     * @var string
     */
    private $previewValue;
    /**
     * Attribute default value.
     *
     * @var string
     */
    private $defaultValue;

    /**
     * RecipientAttribute constructor.
     *
     * @param string $name Attribute name. Required parameter.
     * @param string $type Attribute type. Default value is null
     */
    public function __construct($name, $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Get attribute name
     *
     * @return string
     *   Attribute name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get attribute type
     *
     * @return string
     *   Attribute type. If not set, 'text' type is set by default.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set attribute description.
     *
     * @param string $description Attribute description.
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Set attribute preview value.
     *
     * @param string $previewValue Attribute preview value.
     */
    public function setPreviewValue($previewValue)
    {
        $this->previewValue = $previewValue;
    }

    /**
     * Set attribute default value.
     *
     * @param string $defaultValue Attribute default value.
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Get attribute description.
     *
     * @return string
     *   If not set returns null, otherwise set attribute description.
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get attribute preview value.
     *
     * @return string
     *   If not set returns null, otherwise set preview value.
     */
    public function getPreviewValue()
    {
        return $this->previewValue;
    }

    /**
     * Get attribute default value.
     *
     * @return string
     *   If not set returns null, otherwise set default value.
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
