<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity;

/**
 * Class OrderItem
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity
 */
class OrderItem
{
    /**
     * Order Item unique number.
     *
     * @var string
     */
    private $orderItemId;
    /**
     * @var string
     */
    private $orderId;
    /**
     * Product Id for an order item.
     *
     * @var string
     */
    private $productId;
    /**
     * Product name for an order item.
     *
     * @var string
     */
    private $product;
    /**
     * Timestamp.
     *
     * @var \DateTime
     */
    private $stamp;
    /**
     * Order item selling price including tax.
     *
     * @var float
     */
    private $price = 0;
    /**
     * Order currency.
     *
     * @var string
     */
    private $currency = '';
    /**
     * Number of bought items.
     *
     * @var int
     */
    private $amount = 1;
    /**
     * Store name or a host where an order is placed.
     *
     * @var string
     */
    private $productSource = '';
    /**
     * Item brand.
     *
     * @var string
     */
    private $brand = '';
    /**
     * List of categories where an item belong to.
     *
     * @var array
     */
    private $productCategory = array();
    /**
     * List of attributes.
     *
     * @var array
     */
    private $attributes = array();
    /**
     * CleverReach mailing ID.
     *
     * Used to track whether order is placed thought email campaign or not.
     *
     * @var string
     */
    private $mailingId;
    /**
     * An email of buyer.
     *
     * @var string
     */
    private $recipientEmail;

    /**
     * Order constructor.
     *
     * @param string $orderItemId Order Item unique number.
     * @param string $product Product name for an order item.
     * @param string $orderId Order unique number
     */
    public function __construct($orderItemId, $product, $orderId)
    {
        $this->orderItemId = $orderItemId;
        $this->product = $product;
        $this->orderId = $orderId;
    }

    /**
     * Get order unique number.
     *
     * @return string
     *   Empty string when order ID is not set, otherwise order unique number.
     */
    public function getOrderItemId()
    {
        return $this->orderItemId;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * Get product Id for an order item.
     *
     * @return string
     *   Null when not set, otherwise product unique identifier.
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Product Id for an order item.
     *
     * @param string $productId Product unique identifier.
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    /**
     * Get product name.
     *
     * @return string
     *   Product name.
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Get datetime when an order is placed.
     *
     * @return \DateTime
     *   Datetime object.
     */
    public function getStamp()
    {
        return $this->stamp;
    }

    /**
     * Set datetime when an order is placed.
     *
     * @param \DateTime $stamp Datetime when an order is placed.
     */
    public function setStamp(\DateTime $stamp = null)
    {
        $this->stamp = $stamp;
    }

    /**
     * Get order item selling price including tax.
     *
     * @return float
     *   If price not set returns 0, otherwise set selling price.
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set order item selling price including tax.
     *
     * @param float $price Selling price including tax.
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * Get order currency.
     *
     * @return string
     *   If not set returns empty string, otherwise set currency.
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set order currency.
     *
     * @param string $currency Order currency in iso3 format.
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * Get number of bought items.
     *
     * @return int
     *   Number of bought items, default is 0.
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set number of bought items.
     *
     * @param int $amount Number of bought items.
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Get store name or a host where an order is placed.
     *
     * @return string
     *   If not set returns empty string, otherwise set product source.
     */
    public function getProductSource()
    {
        return $this->productSource;
    }

    /**
     * Set store name or a host where an order is placed.
     *
     * @param string $productSource Store name or a host where an order is placed.
     */
    public function setProductSource($productSource)
    {
        $this->productSource = $productSource;
    }

    /**
     * Get item brand.
     *
     * @return string
     *   If not set returns empty string, otherwise set item brand.
     */
    public function getBrand()
    {
        return $this->brand;
    }

    /**
     * Set item brand.
     *
     * @param string $brand Item brand.
     */
    public function setBrand($brand)
    {
        $this->brand = $brand;
    }

    /**
     * Get list of categories where an item belong to.
     *
     * @return array
     *   If not set empty array is returned, otherwise set list of categories.
     */
    public function getProductCategory()
    {
        return $this->productCategory;
    }

    /**
     * Set list of categories where an item belong to.
     *
     * @param array $productCategory List of categories.
     */
    public function setProductCategory(array $productCategory)
    {
        $this->productCategory = $productCategory;
    }

    /**
     * Get list of custom attributes for an order item.
     *
     * @return array
     *   Array of custom attributes [key: attribute name, value: attribute value].
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set list of custom attributes for an order item.
     *
     * @param array $attributes Array of custom attributes (key: attribute name, value: attribute value).
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Gets CleverReach campaign mailing ID.
     *
     * @return string
     *   Unique campaign mailing ID generated by CleverReach.
     */
    public function getMailingId()
    {
        return $this->mailingId;
    }

    /**
     * Sets CleverReach campaign mailing ID.
     *
     * @param string $mailingsId Unique campaign mailing ID generated by CleverReach.
     */
    public function setMailingId($mailingsId)
    {
        $this->mailingId = $mailingsId;
    }

    /**
     * Get an email of buyer.
     *
     * @return string
     *   E-mail address of buyer.
     */
    public function getRecipientEmail()
    {
        return $this->recipientEmail;
    }

    /**
     * Set an email of buyer.
     *
     * @param string $recipientEmail E-mail address of buyer.
     */
    public function setRecipientEmail($recipientEmail)
    {
        $this->recipientEmail = $recipientEmail;
    }
}
