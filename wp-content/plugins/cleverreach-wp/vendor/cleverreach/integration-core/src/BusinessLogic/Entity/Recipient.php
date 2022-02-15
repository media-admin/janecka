<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class Recipient
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity
 */
class Recipient
{
    /**
     * Recipient e-mail address.
     *
     * @var string
     */
    private $email;
    /**
     * Recipient status.
     *
     * @var bool
     */
    private $isActive = false;
    /**
     * Recipient activation datetime.
     *
     * @var \DateTime
     */
    private $activated;
    /**
     * Recipient registration datetime.
     *
     * @var \DateTime
     */
    private $registered;
    /**
     * Recipient deactivation datetime.
     *
     * @var \DateTime
     */
    private $deactivated;
    /**
     * Host where recipient belongs to (e.g. https://example.com).
     *
     * @var string
     */
    private $source = '';
    /**
     * Recipient salutation.
     *
     * @var string
     */
    private $salutation = '';
    /**
     * Recipient title.
     *
     * @var string
     */
    private $title = '';
    /**
     * Recipient first name.
     *
     * @var string
     */
    private $firstName = '';
    /**
     * Recipient last name.
     *
     * @var string
     */
    private $lastName = '';
    /**
     * Recipient street and house number (if available).
     *
     * @var string
     */
    private $street = '';
    /**
     * Recipient postal/zip code.
     *
     * @var string
     */
    private $zip = '';
    /**
     * Recipient city.
     *
     * @var string
     */
    private $city = '';
    /**
     * Recipient company.
     *
     * @var string
     */
    private $company = '';
    /**
     * Recipient country state (if available for country).
     *
     * @var string
     */
    private $state = '';
    /**
     * Recipient country.
     *
     * @var string
     */
    private $country = '';
    /**
     * Recipient birthday.
     *
     * @var \DateTime
     */
    private $birthday;
    /**
     * Recipient phone number.
     *
     * @var string
     */
    private $phone = '';
    /**
     * Recipient source name (e.g. store, site name).
     *
     * @var string
     */
    private $shop = '';
    /**
     * Recipient unique identifier.
     *
     * @var int
     */
    private $customerNumber = '';
    /**
     * Recipient preferred/chosen language.
     *
     * @var string
     */
    private $language = '';
    /**
     * Recipient newsletter subscription status.
     *
     * @var bool
     */
    private $newsletterSubscription = false;
    /**
     * Associative array in format:
     *
     * [
     *   'customAttributeName' => 'customAttributeValue'
     * ]
     *
     * @var array
     */
    private $attributes = array();
    /**
     * Recipient applied tags, both integration and special tags.
     *
     * @var TagCollection
     */
    private $tags;
    /**
     * Date of the recipient's last order.
     *
     * @var \DateTime
     */
    private $lastOrderDate;
    /**
     * ID of the recipient in the internal system.
     *
     * @var string
     */
    private $internalId;
    /**
     * Recipient order item list.
     *
     * @var OrderItem[]
     */
    private $orders = array();

    /**
     * Recipient constructor.
     *
     * @param string $email E-mail address of recipient.
     */
    public function __construct($email)
    {
        $this->email = $email;
        $this->tags = new TagCollection();
    }

    /**
     * Get recipient e-mail address.
     *
     * @return string
     *   Recipient e-mail address.
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set recipient status.
     *
     * @param bool $isActive If true, recipient will active, otherwise inactive.
     */
    public function setActive($isActive)
    {
        $this->isActive = (bool)$isActive;
    }

    /**
     * Get recipient status.
     *
     * @return bool
     *   If true recipient is active, otherwise inactive. Default is false.
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * Get recipient activation datetime.
     *
     * @return \DateTime
     *   If not set, returns current datetime.
     */
    public function getActivated()
    {
        $timeProvider = new TimeProvider();

        return $this->activated !== null ? $this->activated : $timeProvider->getCurrentLocalTime();
    }

    /**
     * Set recipient activation datetime.
     *
     * Disclaimer:
     * Activated timestamp is used for handling both activation and deactivation.
     * When activated timestamp is set to 0, recipient will be inactive in
     * CleverReach. Setting activated to value > 0 will reactivate recipient in
     * CleverReach but only if recipient was not deactivated withing CleverReach
     * system.
     *
     * @param \DateTime $activated
     *   Datetime when recipient is activated in the source system.
     */
    public function setActivated(\DateTime $activated = null)
    {
        $this->activated = $activated;
    }

    /**
     * Set recipient registered datetime.
     *
     * @return \DateTime
     *   If not set, returns null.
     */
    public function getRegistered()
    {
        return $this->registered;
    }

    /**
     * Set recipient registered datetime.
     *
     * @param \DateTime $registered Datetime when recipient is registered in the source system.
     */
    public function setRegistered(\DateTime $registered = null)
    {
        $this->registered = $registered;
    }

    /**
     * Get recipient deactivated datetime.
     *
     * @return \DateTime
     *   If not set, returns current datetime.
     */
    public function getDeactivated()
    {
        $timeProvider = new TimeProvider();

        return $this->deactivated !== null ? $this->deactivated : $timeProvider->getCurrentLocalTime();
    }

    /**
     * Set recipient deactivated datetime.
     *
     * Disclaimer:
     * Activated timestamp is used for handling both activation and deactivation.
     * When activated timestamp is set to 0, recipient will be inactive in
     * CleverReach. Setting activated to value > 0 will reactivate recipient in
     * CleverReach but only if recipient was not deactivated withing CleverReach
     * system.
     * This field should not be set by integration!
     *
     * @param \DateTime $deactivated Datetime when recipient is deactivated in the source system.
     */
    public function setDeactivated(\DateTime $deactivated = null)
    {
        $this->deactivated = $deactivated;
    }

    /**
     * Set host where recipient belongs to.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set host where recipient belongs to.
     *
     * @param string $source Host where recipient belongs to.
     */
    public function setSource($source)
    {
        $this->source = $source;
    }

    /**
     * Get recipient salutation.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getSalutation()
    {
        return $this->salutation;
    }

    /**
     * Set recipient salutation.
     *
     * @param string $salutation Recipient salutation.
     */
    public function setSalutation($salutation)
    {
        $this->salutation = $salutation;
    }

    /**
     * Get recipient title.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set recipient title.
     *
     * @param string $title Recipient title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get recipient first name.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set recipient first name.
     *
     * @param string $firstName Recipient first name.
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Get recipient last name.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set recipient last name.
     *
     * @param string $lastName Recipient last name.
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Get recipient street and house number.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Set recipient street and house number.
     *
     * @param string $street Recipient street and house number.
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * Get recipient postal / zip code.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set recipient postal / zip code.
     *
     * @param string $zip Recipient postal / zip code.
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * Get recipient city.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set recipient city.
     *
     * @param string $city Recipient city.
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * Get recipient company.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * Set recipient company.
     *
     * @param string $company Recipient company.
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * Get recipient country state.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set recipient country state.
     *
     * @param string $state Recipient country state.
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Get recipient country.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set recipient country.
     *
     * @param string $country Recipient country.
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get recipient birthday.
     *
     * @return \DateTime
     *   If not set, returns null.
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set recipient birthday.
     *
     * @param \DateTime $birthday Recipient birthday.
     */
    public function setBirthday(\DateTime $birthday = null)
    {
        $this->birthday = $birthday;
    }

    /**
     * Get recipient phone number.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set recipient phone number.
     *
     * @param string $phone Recipient phone number.
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * Get recipient source name.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * Set recipient source name.
     *
     * @param string $shop Recipient source name.
     */
    public function setShop($shop)
    {
        $this->shop = $shop;
    }

    /**
     * Set recipient unique identifier.
     *
     * @return int
     *   Recipient unique identifier.
     */
    public function getCustomerNumber()
    {
        return $this->customerNumber;
    }

    /**
     * Get recipient unique identifier.
     *
     * @param int $customerNumber Recipient unique identifier.
     */
    public function setCustomerNumber($customerNumber)
    {
        $this->customerNumber = $customerNumber;
    }

    /**
     * Get recipient language code.
     *
     * @return string
     *   If not set, returns an empty string.
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Get recipient language code.
     *
     * @param string $language Language code.
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * Get recipient newsletter subscription status.
     *
     * @return bool
     *   Subscription status. Default value is false.
     */
    public function getNewsletterSubscription()
    {
        return $this->newsletterSubscription;
    }

    /**
     * Set recipient newsletter subscription status.
     *
     * @param bool $newsletterSubscription Newsletter subscription status.
     */
    public function setNewsletterSubscription($newsletterSubscription)
    {
        $this->newsletterSubscription = $newsletterSubscription;
    }

    /**
     * Get custom recipient attributes.
     *
     * @return array
     *   Array in format ['customAttributeName' => 'customAttributeValue']
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set custom recipient attributes.
     *
     * @param array $attributes Array in format ['customAttributeName' => 'customAttributeValue']
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Get recipient integration tags.
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection
     *   Integration specific tags.
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set recipient integration tags.
     *
     * @param \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection|null $tags Integration specific tags.
     * @see \CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Recipients
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    /**
     * Set recipient special tags.
     *
     * @param \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTagCollection|null $tags Special tags applicable to recipient.
     * @see \CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Recipients
     */
    public function setSpecialTags($tags)
    {
        $this->tags->add($tags);
    }

    /**
     * Get recipient order item list.
     *
     * @return \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\OrderItem[]
     *   Order item list.
     */
    public function getOrders()
    {
        return $this->orders;
    }

    /**
     * Recipient order item list.
     *
     * @param \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\OrderItem[] $orders Order item list.
     */
    public function setOrders(array $orders)
    {
        $this->orders = $orders;
    }

    /**
     * Get recipient last order date.
     *
     * @return \DateTime
     */
    public function getLastOrderDate()
    {
        return $this->lastOrderDate;
    }

    /**
     * Set recipient last order date.
     *
     * @param \DateTime $lastOrderDate
     */
    public function setLastOrderDate($lastOrderDate)
    {
        $this->lastOrderDate = $lastOrderDate;
    }

    /**
     * Returns recipient's internal ID.
     *
     * @return string
     */
    public function getInternalId()
    {
        return $this->internalId;
    }

    /**
     * Sets recipient's internal ID.
     *
     * @param string $internalId
     */
    public function setInternalId($internalId)
    {
        $this->internalId = $internalId;
    }
}
