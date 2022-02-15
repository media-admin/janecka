<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity;

/**
 * ONLY FOR MIGRATION TASKS.
 *
 * This class is intended only for migrating tasks from old prefixed format
 * to new format with origin and should not be used elsewhere.
 */
class TagInOldFormat extends Tag
{
    /**
     * TagInOldFormat constructor.
     *
     * @param string $name Tag name.
     *
     * @throws \InvalidArgumentException
     *   Name cannot be empty.
     */
    public function __construct($name)
    {
        parent::__construct($name, '');
    }

    /**
     * Validates "Name" and "Type" for tag.
     *
     * @inheritdoc
     */
    protected function validate()
    {
        if (empty($this->name)) {
            throw new \InvalidArgumentException('Name and Type parameters cannot be empty!');
        }
    }
}
