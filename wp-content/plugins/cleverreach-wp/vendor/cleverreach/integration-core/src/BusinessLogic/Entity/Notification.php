<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity;

/**
 * Class Notification
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity
 */
class Notification
{
    /**
     * Unique notification identifier
     *
     * @var string
     */
    private $id;
    /**
     * Notification name
     *
     * @var string
     */
    private $name;
    /**
     * Notification create time
     *
     * @var \DateTime
     */
    private $date;
    /**
     * Notification message that will be shown to user
     *
     * @var string
     */
    private $description;
    /**
     * Plugin url with
     *
     * @var string
     */
    private $url;

    /**
     * Notification constructor.
     *
     * @param string $id notification id
     * @param string $name notification name
     */
    public function __construct($id, $name = 'periodic')
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * Return notification id
     *
     * @return string
     *    Notification id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return notification name
     *
     * @return string
     *   Notification name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set notification name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Return notification created datetime
     *
     * @return \DateTime
     *   Null if not set, DateTime object otherwise
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set notification created datetime
     *
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * Return notification description
     *
     * @return string
     *   Notification description that will be shown as system notification
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set notification description
     *
     * @param string $description that will be shown as system notification
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Return plugin url
     *
     * @return string
     *   Url of plugin with notification popup
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set plugin url
     *
     * @param string $url of plugin with notification popup
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }
}
