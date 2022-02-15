<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Utility;

use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Entity;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\EntityClassException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\IntermediateObject;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class EntityTranslator
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Utility
 */
class EntityTranslator
{
    /**
     * @var string
     */
    private $entityClass;

    /**
     * @param string $entityClass
     *
     * @throws EntityClassException
     */
    public function init($entityClass)
    {
        if (!is_subclass_of($entityClass, Entity::getClassName())) {
            throw new EntityClassException("Class $entityClass is not implementation of Entity");
        }

        $this->entityClass = $entityClass;
    }

    /**
     * Translate intermediate objects to concrete entities
     *
     * @param IntermediateObject[] $intermediateObjects
     *
     * @return Entity[]
     * @throws EntityClassException
     */
    public function translate(array $intermediateObjects)
    {
        if ($this->entityClass === null) {
            throw new EntityClassException('Entity translator must be initialized with entity class.');
        }

        $result = array();
        foreach ($intermediateObjects as $intermediateObject) {
            $result[] = $this->translateOne($intermediateObject);
        }

        return $result;
    }

    /**
     * Translates one intermediate object to concrete object
     *
     * @param IntermediateObject $intermediateObject
     *
     * @return Entity
     * @throws EntityClassException
     */
    private function translateOne(IntermediateObject $intermediateObject)
    {
        /** @var Entity $entity */
        $entity = Serializer::unserialize($intermediateObject->getData());
        if (!($entity instanceof $this->entityClass)) {
            throw new EntityClassException("Unserialized entity is not of class {$this->entityClass}");
        }

        return $entity;
    }
}
