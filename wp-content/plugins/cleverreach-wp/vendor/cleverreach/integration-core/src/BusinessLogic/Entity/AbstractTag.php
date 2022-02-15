<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class AbstractTag
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity
 */
abstract class AbstractTag implements Serializable
{
    const TAG_NAME_REGEX = "/[^a-zA-Z0-9_\\p{L}]+/u";
    const TAG_MAX_LENGTH = 49;
    /**
     * Integration tag type.
     *
     * @var string
     */
    protected $type;
    /**
     * Integration tag name.
     *
     * @var string
     */
    protected $name;
    /**
     * Tag prefix.
     *
     * @var string
     */
    protected $prefix;
    /**
     * Flag that indicates whether tag is deleted or not.
     *
     * @var bool
     */
    protected $isDeleted;

    /**
     * AbstractTag constructor.
     *
     * @param string $name Tag name.
     * @param string $type Tag type.
     */
    protected function __construct($name, $type)
    {
        $this->name = $name;
        $this->type = $type;
        $this->isDeleted = false;

        $this->validate();

        // Disclaimer:
        // Core needs integration prefix in order to properly distinguish tags on
        // CleverReach added by integration from the ones added by user. It is
        // general convention to use integration name as tag prefix. The only reason
        // why prefix is added only when type is not empty (and the reason type can
        // be empty) is backward compatibility when tag was a single string. Also,
        // because CORE requires prefix, it is added here, although accessing
        // service from entity in this way is not a good practice, but it could not
        // be done differently because of PHP language limitations.
        if (!empty($this->type)) {
            $this->prefix = ServiceRegister::getService(Configuration::CLASS_NAME)->getIntegrationName();
        }
    }

    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array)
    {
        /** @var static $entity */
        $entity = new static($array['name'], $array['type']);

        $entity->prefix = $array['prefix'];
        $entity->isDeleted = $array['isDeleted'];

        return $entity;
    }

    /**
     * Marks tag deleted so that it can be removed on remote API
     */
    public function markDeleted()
    {
        $this->isDeleted = true;
    }

    /**
     * Checks whether two tags are semantically equal.
     *
     * Does not compare object instances.
     *
     * @param \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Tag|string $tag Tag that needs to be compared.
     *
     * @return bool
     *   If passed tag is equal to this object returns true, otherwise false.
     */
    public function isEqual($tag)
    {
        return (string)$this === (string)$tag;
    }

    /**
     * Gets tag as readable string in format "Type: Name".
     */
    public function getTitle()
    {
        $result = $this->type ? $this->type . ': ' : '';
        $result .= $this->name;

        return $result;
    }

    /**
     * Gets tag as string in format "IntegrationName-Type.Name".
     *
     * @return string
     *   String representation of Tag object.
     */
    public function __toString()
    {
        $pattern = self::TAG_NAME_REGEX;
        $name = empty($this->type) ? $this->name : preg_replace($pattern, '_', $this->name);
        $type = preg_replace($pattern, '_', $this->type);
        $prefix = preg_replace($pattern, '_', $this->prefix);

        /** @var string $result */
        $result = $prefix ?: '';
        if ($prefix && $type) {
            // implode with - only if both exist
            $result .= '-';
        }

        $result .= $type ?: '';
        if ($prefix || $type) {
            $result .= '.';
        }

        $result .= $name;
        // cut if too long
        $result = mb_substr($result, 0, self::TAG_MAX_LENGTH);
        // prepend '-' if is deleted
        $result = $this->isDeleted ? '-' . $result : $result;

        return $result;
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(
            array(
                $this->name,
                $this->type,
                $this->prefix,
                $this->isDeleted,
            )
        );
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list($this->name, $this->type, $this->prefix, $this->isDeleted) = Serializer::unserialize($serialized);
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'name' => $this->name,
            'type' => $this->type,
            'prefix' => $this->prefix,
            'isDeleted' => $this->isDeleted,
        );
    }

    /**
     * Validates "Name" and "Type" for tag
     *
     * @throws \InvalidArgumentException
     *   Name and Type parameters cannot be empty!
     */
    protected function validate()
    {
        if (empty($this->name) || empty($this->type)) {
            throw new \InvalidArgumentException('Name and Type parameters cannot be empty!');
        }
    }
}