<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity;

/**
 * Class SpecialTag
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity
 */
class SpecialTag extends AbstractTag
{
    /**
     * SpecialTag constructor.
     *
     * @param string $name Valid special tag name. Use constants in this class for valid names.
     */
    protected function __construct($name)
    {
        parent::__construct($name, 'Special');
    }

    /**
     * Returns new special tag "Customer".
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTag
     *   Instance of special tag "Customer".
     */
    public static function customer()
    {
        return new SpecialTag('Customer');
    }

    /**
     * Returns new special tag "Subscriber".
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTag
     *   Instance of special tag "Subscriber".
     */
    public static function subscriber()
    {
        return new SpecialTag('Subscriber');
    }

    /**
     * Returns new special tag "Buyer".
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTag
     *   Instance of special tag "Buyer".
     */
    public static function buyer()
    {
        return new SpecialTag('Buyer');
    }

    /**
     * Returns new special tag "Contact".
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTag
     *   Instance of special tag "Contact".
     */
    public static function contact()
    {
        return new SpecialTag('Contact');
    }

    /**
     * Sets special tag from tag name.
     *
     * @param string $tagName
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTag
     *
     * @throws \InvalidArgumentException When tag name is not recognized as a special tag name.
     */
    public static function fromString($tagName)
    {
        switch ($tagName) {
            case 'Customer':
                return static::customer();
            case 'Buyer':
                return static::buyer();
            case 'Subscriber':
                return static::subscriber();
            case 'Contact':
                return static::contact();
        }

        throw new \InvalidArgumentException('Unknown special tag!');
    }

    /**
     * Gets collection of all valid special tags.
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTagCollection
     *   Collection of all supported special tags.
     */
    public static function all()
    {
        $result = new SpecialTagCollection();
        $result->addTag(static::customer())
            ->addTag(static::subscriber())
            ->addTag(static::buyer())
            ->addTag(static::contact());

        return $result;
    }
}
