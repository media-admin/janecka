<?php

namespace CleverReach\WordPress\Components\Entities;

use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Configuration\EntityConfiguration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Configuration\IndexMap;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Entity;

/**
 * Class Contact
 *
 * @package CleverReach\WordPress\Components\Entities
 */
class Contact extends Entity {
	const CLASS_NAME = __CLASS__;
	/**
	 * @var string
	 */
	protected $email;
	/**
	 * @var int
	 */
	protected $form_id;
	/**
	 * Recipient tags.
	 *
	 * @var array
	 */
	protected $tags;
	/**
	 * Recipient special tags.
	 *
	 * @var array
	 */
	protected $special_tags;
	/**
	 * Associative array of mapped attributes and their values.
	 *
	 * @var array
	 */
	protected $attributes;
	/**
	 * @var bool
	 */
	protected $is_active;
	/**
	 * @var bool
	 */
	protected $is_subscribed;
	/**
	 * @var string
	 */
	protected $language;
	/**
	 * Array of field names.
	 *
	 * @var array
	 */
	protected $fields = array(
		'id',
		'email',
		'form_id',
		'tags',
		'special_tags',
		'attributes',
		'is_active',
		'is_subscribed',
		'language'
	);

	/**
	 * Contact constructor.
	 *
	 * @param string $email
	 * @param int    $form_id
	 * @param array  $tags
	 * @param array  $special_tags
	 * @param array  $attributes
	 * @param bool   $is_active
	 * @param bool   $is_subscribed
	 * @param string $language
	 */
	public function __construct(
		$email = null,
		$form_id = null,
		$tags = array(),
		$special_tags = array(),
		$attributes = array(),
		$is_active = false,
		$is_subscribed = false,
		$language = ''
	) {
		$this->email         = $email;
		$this->form_id       = $form_id;
		$this->tags          = $tags;
		$this->special_tags  = $special_tags;
		$this->attributes    = $attributes;
		$this->is_active     = $is_active;
		$this->is_subscribed = $is_subscribed;
		$this->language      = $language;
	}

	/**
	 * Returns contact email.
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->email;
	}

	/**
	 * Sets contact email.
	 *
	 * @param string $email
	 */
	public function set_email( $email ) {
		$this->email = $email;
	}

	/**
	 * Returns form ID.
	 *
	 * @return int
	 */
	public function get_form_id() {
		return $this->form_id;
	}

	/**
	 * Sets form ID.
	 *
	 * @param int $form_id
	 */
	public function set_form_id( $form_id ) {
		$this->form_id = $form_id;
	}

	/**
	 * Returns recipient tags.
	 *
	 * @return array
	 */
	public function get_tags() {
		return $this->tags;
	}

	/**
	 * Sets recipient tags.
	 *
	 * @param array $tags
	 */
	public function set_tags( $tags ) {
		$this->tags = $tags;
	}

	/**
	 * Returns special tags.
	 *
	 * @return array
	 */
	public function get_special_tags() {
		return $this->special_tags;
	}

	/**
	 * Sets special tags.
	 *
	 * @param array $special_tags
	 */
	public function set_special_tags( $special_tags ) {
		$this->special_tags = $special_tags;
	}

	/**
	 * Add special tag to the list of special tags.
	 *
	 * @param string $special_tag
	 */
	public function add_special_tag( $special_tag ) {
		$this->special_tags[] = $special_tag;
	}

	/**
	 * Removes special tag from the list of special tags.
	 *
	 * @param string $special_tag
	 */
	public function remove_special_tag( $special_tag ) {
		$index = array_search( $special_tag, $this->special_tags, true );
		if ( $index !== false ) {
			unset( $this->special_tags[ $index ] );
		}
	}

	/**
	 * Returns contact attributes.
	 *
	 * @return array
	 */
	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Sets contact attributes.
	 *
	 * @param array $attributes
	 */
	public function set_attributes( $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * Returns whether contact is active.
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->is_active;
	}

	/**
	 * Sets whether contact is active.
	 *
	 * @param bool $is_active
	 */
	public function set_active( $is_active ) {
		$this->is_active = $is_active;
	}

	/**
	 * Returns whether contact is subscribed to newsletter.
	 *
	 * @return bool
	 */
	public function is_subscribed() {
		return $this->is_subscribed;
	}

	/**
	 * Sets whether contact is subscribed to newsletter.
	 *
	 * @param bool $is_subscribed
	 */
	public function set_subscribed( $is_subscribed ) {
		$this->is_subscribed = $is_subscribed;
	}

	/**
	 * Returns user language.
	 *
	 * @return string
	 */
	public function get_language() {
		return $this->language;
	}

	/**
	 * Sets user language.
	 *
	 * @param string $language
	 */
	public function set_language( $language ) {
		$this->language = $language;
	}

	/**
	 * Returns entity configuration object.
	 *
	 * @return EntityConfiguration Configuration object.
	 */
	public function getConfig() {
		$map = new IndexMap();
		$map->addStringIndex( 'email' )
			->addIntegerIndex( 'form_id' );

		return new EntityConfiguration( $map, 'Contact' );
	}
}
