<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\BusinessLogicServices;

use CleverReach\WordPress\Components\Entities\Contact;
use CleverReach\WordPress\Components\Repositories\Base_Repository;
use CleverReach\WordPress\Components\Utility\Helper;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Recipient;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\RecipientAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Attributes;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;

/**
 * Class Attributes_Service
 *
 * @package CleverReach\WordPress\Components\BusinessLogicServices
 */
class Attributes_Service implements Attributes {

	/**
	 * List of global attributes
	 *
	 * @var array
	 */
	private static $attributes = array(
		'salutation'     => 'Salutation',
		'title'          => 'Title',
		'firstname'      => 'User First Name',
		'lastname'       => 'User Last Name',
		'street'         => 'Street',
		'zip'            => 'ZIP',
		'city'           => 'City',
		'company'        => 'Company',
		'state'          => 'State',
		'country'        => 'Country',
		'birthday'       => 'Birthday',
		'phone'          => 'Phone',
		'shop'           => 'Site Title',
		'customernumber' => 'User ID',
		'language'       => 'Language',
		'newsletter'     => 'Newsletter',
		'email'          => 'Email'
	);

	/**
	 * @var Base_Repository
	 */
	private $contact_repository;

	/**
	 * Get attributes from integration with translated params in system language.
	 *
	 * It should set name, description, preview_value and default_value for each attribute available in system.
	 *
	 * @return RecipientAttribute[]
	 *   List of available attributes in the system.
	 */
	public function getAttributes() {
		$recipient_attributes = array();
		$language_code = Helper::get_sync_language();
		load_textdomain( $language_code, WP_LANG_DIR . "/$language_code.mo" );
		foreach ( self::$attributes as $attribute_name => $attribute_label ) {
			$recipient_attribute = new RecipientAttribute( $attribute_name );
			$domain = $attribute_label === 'Newsletter' ? 'cleverreach-wp' : $language_code;
			$recipient_attribute->setDescription( __( $attribute_label, $domain ) );

			$recipient_attributes[] = $recipient_attribute;
		}

		return $recipient_attributes;
	}

	/**
	 * Get recipient specific attributes from integration with translated params in system language.
	 *
	 * It should set name, description, preview_value and default_value for each attribute available in system for a
	 * given Recipient entity instance.
	 *
	 * @param \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Recipient $recipient
	 *
	 * @return RecipientAttribute[]
	 *   List of available attributes in the system for a given Recipient.
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	public function getRecipientAttributes( Recipient $recipient ) {
		$prefix = substr( $recipient->getInternalId(), 0, 2 );
		if ( Recipient_Service::USER_ID_PREFIX === $prefix ) {
			return $this->getAttributes();
		}

		$contact_id = (int) substr( $recipient->getInternalId(), 2, strlen( $recipient->getInternalId() ) );
		$filter = new QueryFilter();
		$filter->where( 'id', Operators::EQUALS, $contact_id );
		/** @var Contact $contact */
		$contact = $this->get_contact_repository()->selectOne( $filter );

		if ( null === $contact ) {
			return array();
		}

		$contact_attributes   = array_merge( array_keys( $contact->get_attributes() ), array( 'language', 'newsletter' ) );
		$recipient_attributes = array();
		foreach ( $contact_attributes as $contact_attribute ) {
			$attribute       = new RecipientAttribute( $contact_attribute );
			$attribute_label = self::$attributes[ $contact_attribute ];
			$domain          = $attribute_label === 'Newsletter' ? 'cleverreach-wp' : Helper::get_sync_language();
			$attribute->setDescription( __( $attribute_label, $domain ) );
			$recipient_attributes[] = $attribute;
		}

		return $recipient_attributes;
	}

	/**
	 * Returns an instance of contact repository.
	 *
	 * @return Base_Repository|\CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Interfaces\RepositoryInterface
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	private function get_contact_repository() {
		if ( null === $this->contact_repository ) {
			$this->contact_repository = RepositoryRegistry::getRepository( Contact::CLASS_NAME );
		}

		return $this->contact_repository;
	}
}
