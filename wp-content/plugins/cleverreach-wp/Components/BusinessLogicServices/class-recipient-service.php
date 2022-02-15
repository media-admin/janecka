<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\BusinessLogicServices;

use CleverReach\WordPress\Components\Entities\Contact;
use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Repositories\Base_Repository;
use CleverReach\WordPress\Components\Repositories\Recipient_Repository;
use CleverReach\WordPress\Components\Repositories\Role_Repository;
use CleverReach\WordPress\Components\Utility\Database;
use CleverReach\WordPress\Components\Utility\Helper;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Recipient;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTag;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTagCollection;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Tag;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Recipients;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\RepositoryRegistry;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\Operators;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\QueryFilter\QueryFilter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\TimeProvider;

/**
 * Class Recipient_Service
 *
 * @package CleverReach\WordPress\Components\BusinessLogicServices
 */
class Recipient_Service implements Recipients {

	const TAG_TYPE_ROLE = 'Role';
	const TAG_TYPE_CF7  = 'CF7';
	const TAG_TYPE_SITE = 'Site';

	const NEWSLETTER_STATUS_ROLE_SUBSCRIBERS_ONLY = 'role_subscriber_only';
	const NEWSLETTER_STATUS_ALL                   = 'all';

	const USER_ID_PREFIX    = 'U-';
	const CONTACT_ID_PREFIX = 'C-';

	/**
	 * Configuration service
	 *
	 * @var Config_Service
	 */
	private $config_service;

	/**
	 * Recipient repository
	 *
	 * @var Recipient_Repository
	 */
	private $recipient_repository;

	/**
	 * Contact repository.
	 *
	 * @var Base_Repository
	 */
	private $contact_repository;

	/**
	 * Role repository
	 *
	 * @var Role_Repository
	 */
	private $role_repository;

	/**
	 * Gets all tags as a collection.
	 *
	 * @return TagCollection
	 */
	public function getAllTags() {
		$tag = $this->get_formatted_tags(
			$this->get_roles(),
			self::TAG_TYPE_ROLE
		);

		$tag->add(
			$this->get_formatted_tags(
				$this->get_sites(),
				self::TAG_TYPE_SITE
			)
		);

		return $tag;
	}

	/**
	 * Gets all special tags as a collection
	 *
	 * @return SpecialTagCollection
	 */
	public function getAllSpecialTags() {
		return new SpecialTagCollection(
			array(
				SpecialTag::contact(),
				SpecialTag::subscriber(),
			)
		);
	}

	/**
	 * Gets CleverReach recipients with tags, properly formatted.
	 *
	 * @param array $batch_recipient_ids Batch of WP_User ids prefixed with C- (regular registered customers) and
	 *                                   WC_Order ids prefixed with G- (this represents guest customers).
	 *
	 * @param bool  $include_orders      Specifies whether to include orders in result or not.
	 *
	 * @return array
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	public function getRecipientsWithTags( array $batch_recipient_ids, $include_orders ) {
		$formatted_ids = $this->get_formatted_recipient_ids( $batch_recipient_ids );

		$users = ! empty( $formatted_ids[ 'users' ] )
			? $this->get_formatted_user_recipients( $formatted_ids[ 'users' ] )
			: array();

		$contacts = ! empty( $formatted_ids[ 'contacts' ] )
			? $this->get_formatted_contact_recipients( $formatted_ids[ 'contacts' ] )
			: array();

		return array_merge( $users, $contacts );
	}

	/**
	 * Gets all recipients IDs from source system.
	 *
	 * @return array of strings
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 */
	public function getAllRecipientsIds() {
		$ids = array();

		if ( $this->get_config_service()->is_recipient_sync_enabled() ) {
			$user_ids = $this->get_recipient_repository()->get_all_user_ids();
			$ids = $this->add_prefix_to_recipient_ids( $user_ids, self::USER_ID_PREFIX );
		}

		if ( $this->get_config_service()->is_integration_recipient_sync_enabled( 'CF7' ) ) {
			$contacts = $this->get_contact_repository()->select();

			$contact_ids = array_map(
				function ( $contact ) {
					/** @var Contact $contact */
					return (int) $contact->getId();
				},
				$contacts
			);

			$ids = array_merge(
				$ids,
				$this->add_prefix_to_recipient_ids( $contact_ids, self::CONTACT_ID_PREFIX )
			);
		}

		return $ids;
	}

	/**
	 * Executes when recipients were synchronized
	 *
	 * @param array $recipient_ids Array of recipient IDs.
	 */
	public function recipientSyncCompleted( array $recipient_ids ) {
		// Intentionally left empty. We do not need this functionality.
	}

	/**
	 * Adds a specified prefix to all IDs in the array.
	 *
	 * @param array  $recipient_ids
	 * @param string $prefix
	 *
	 * @return array
	 */
	private function add_prefix_to_recipient_ids( $recipient_ids, $prefix ) {
		$formatted_recipient_ids = array();

		foreach ( $recipient_ids as $id ) {
			$formatted_recipient_ids[] = $prefix . $id;
		}

		return $formatted_recipient_ids;
	}

	/**
	 * Removes prefix from ids and adds recipient to corresponding array depending on prefix.
	 *
	 * @param array $batch_recipient_ids
	 *
	 * @return array
	 */
	private function get_formatted_recipient_ids( $batch_recipient_ids ) {
		$formatted_ids = array(
			'users'    => array(),
			'contacts' => array(),
		);

		foreach ( $batch_recipient_ids as $recipient_id ) {
			$prefix                      = substr( $recipient_id, 0, 2 );
			$recipient_id_without_prefix = (int) substr( $recipient_id, 2, strlen( $recipient_id ) );

			if ( ! in_array( $recipient_id_without_prefix, $formatted_ids[ 'users' ], true )
			     && strtolower( self::USER_ID_PREFIX ) === strtolower( $prefix )
			) {
				$formatted_ids[ 'users' ][] = (int) substr( $recipient_id, 2, strlen( $recipient_id ) );
			}

			if ( ! in_array( $recipient_id_without_prefix, $formatted_ids[ 'contacts' ], true )
			     && strtolower( $prefix ) === strtolower( self::CONTACT_ID_PREFIX )
			) {
				$formatted_ids[ 'contacts' ][] = (int) substr( $recipient_id, 2, strlen( $recipient_id ) );
			}
		}

		return $formatted_ids;
	}

	/**
	 * Returns recipients created out of WP users.
	 *
	 * @param array $batch_user_ids
	 *
	 * @return array
	 */
	private function get_formatted_user_recipients( $batch_user_ids ) {
		$result = array();
		$users  = $this->get_recipient_repository()->get_users( $batch_user_ids );

		foreach ( $users as $user ) {
			$result[] = $this->create_user_recipient( $user );
		}

		return $result;
	}

	/**
	 * Returns recipients created out of CF7 contacts.
	 *
	 * @param array $batch_contact_ids
	 *
	 * @return array
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	private function get_formatted_contact_recipients( $batch_contact_ids ) {
		$result = array();
		$filter = new QueryFilter();
		$filter->where( 'id', Operators::IN, $batch_contact_ids );
		$contacts = $this->get_contact_repository()->select( $filter );

		/** @var Contact $contact */
		foreach ( $contacts as $contact ) {
			$result[] = $this->create_contact_recipient( $contact );
		}

		return $result;
	}

	/**
	 * Returns formatted tag collection.
	 *
	 * @param array  $source_tags Array of tags.
	 * @param string $type Tag type.
	 *
	 * @return TagCollection
	 */
	private function get_formatted_tags( array $source_tags, $type ) {
		$tag_collection = new TagCollection();
		foreach ( $source_tags as $source_tag ) {
			$tag = new Tag( $source_tag, $type );
			$tag_collection->addTag( $tag );
		}

		return $tag_collection;
	}

	/**
	 * Gets all user prefixed roles/groups
	 *
	 * @return array
	 *   Array of role names as value and role id as key.
	 */
	private function get_roles() {
		return $this->get_role_repository()->get_all_roles();
	}

	/**
	 * Gets array of website tags
	 *
	 * @return array Array of site names.
	 */
	private function get_sites() {
		return array( Helper::get_site_name() ?: Helper::get_site_url() );
	}

	/**
	 * Creates Recipient out of user array data
	 *
	 * @param \stdClass $user User entity.
	 *
	 * @return Recipient
	 */
	private function create_user_recipient( $user ) {
		$first_login         = $this->get_date( $user->user_registered );
		$first_and_last_name = $this->get_first_and_last_name( $user );
		$is_newsletter       = $this->is_recipient_newsletter( $user->ID, $user->roles );
		$user_language       = $this->get_user_language( $user->ID );

		$recipient = new Recipient( $user->user_email );
		$recipient->setActive( $is_newsletter );
		$recipient->setRegistered( $first_login );
		$recipient->setActivated( $first_login );
		$recipient->setFirstName( $first_and_last_name[ 'firstname' ] );
		$recipient->setLastName( $first_and_last_name[ 'lastname' ] );
		$recipient->setCustomerNumber( $user->ID );
		$recipient->setLanguage( $user_language );
		$recipient->setNewsletterSubscription( $is_newsletter );
		$recipient->setSource( Helper::get_site_url() );
		$recipient->setShop( Helper::get_site_name() );
		$recipient->setTags( $this->get_recipient_tags( $user ) );
		$recipient->setSpecialTags( $this->get_user_special_tags( $is_newsletter ) );
		$recipient->setInternalId( self::USER_ID_PREFIX . $user->ID );

		return $recipient;
	}

	/**
	 * Creates Recipient out of user array data
	 *
	 * @param Contact $contact User entity.
	 *
	 * @return Recipient
	 */
	private function create_contact_recipient( $contact ) {
		/** @var TimeProvider $time_provider */
		$time_provider = ServiceRegister::getService( TimeProvider::CLASS_NAME );

		$recipient = new Recipient( $contact->get_email() );
		$recipient->setActive( $contact->is_active() );
		$recipient->setNewsletterSubscription( $contact->is_subscribed() );
		$recipient->setTags( $this->get_formatted_tags( $contact->get_tags(), self::TAG_TYPE_CF7 ) );
		$recipient->setSpecialTags( $this->get_contact_special_tags( $contact->get_special_tags() ) );
		$recipient->setLanguage( $this->format_contact_language( $contact->get_language() ) );
		$recipient->setSource( get_site_url() );
		$recipient->setRegistered( $time_provider->getCurrentLocalTime() );
		$recipient->setInternalId( self::CONTACT_ID_PREFIX . $contact->getId() );

		foreach ( $contact->get_attributes() as $key => $value ) {
			$setter_method = "set$key";
			if ( method_exists( $recipient, $setter_method ) ) {
				if ( in_array( $key, array( 'birthday', 'lastorderdate' ) ) ) {
					$value = \DateTime::createFromFormat( 'Y-m-d', $value ) ?: null;
				}

				$recipient->$setter_method( $value );
			}
		}

		return $recipient;
	}

	/**
	 * Gets date in proper format
	 *
	 * @param string $date_string Date in string.
	 *
	 * @return \DateTime|null
	 */
	private function get_date( $date_string ) {
		if ( empty( $date_string ) ) {
			return null;
		}

		if ( '0000-00-00 00:00:00' === $date_string ) {
			$date_string = date( Helper::DATE_FORMAT );
		}

		$date = \DateTime::createFromFormat( Helper::DATE_FORMAT, $date_string );

		return false !== $date ? $date : null;
	}

	/**
	 * Gets first and last name from user data
	 *
	 * @param \stdClass $user User entity.
	 *
	 * @return array
	 */
	private function get_first_and_last_name( $user ) {
		$first_and_last_name = array(
			'firstname' => '',
			'lastname'  => '',
		);

		if ( ! empty( $user->user_firstname ) ) {
			$first_and_last_name['firstname'] = $user->user_firstname;
			$first_and_last_name['lastname']  = $user->user_lastname;
		} elseif ( ! empty( $user->user_nicename ) ) {
			$first_and_last_name['firstname'] = $user->user_nicename;
		} elseif ( ! empty( $user->user_login ) ) {
			$first_and_last_name['firstname'] = $user->user_login;
		}

		return $first_and_last_name;
	}

	/**
	 *  Checks if recipient should be synced as newsletter subscriber
	 *
	 * @param int   $user_id User id.
	 * @param array $user_roles Array of user roles.
	 *
	 * @return bool
	 */
	private function is_recipient_newsletter( $user_id, $user_roles ) {
		$newsletter_column      = Database::get_newsletter_column();
		$user_newsletter_status = get_user_meta( $user_id, $newsletter_column, true );

		if ( null !== $user_newsletter_status && '' !== $user_newsletter_status ) {
			return '1' === $user_newsletter_status;
		}

		$default_newsletter_status_handling = $this->get_config_service()->get_default_newsletter_status();

		if ( self::NEWSLETTER_STATUS_ROLE_SUBSCRIBERS_ONLY === $default_newsletter_status_handling ) {
			return in_array( 'subscriber', $user_roles, true );
		}

		return self::NEWSLETTER_STATUS_ALL === $default_newsletter_status_handling;
	}

	/**
	 * User language in format en-GB, de-DE etc.
	 *
	 * @param int $user_id User id.
	 *
	 * @return string
	 */
	private function get_user_language( $user_id ) {
		$locale = get_user_locale( $user_id );

		if ( empty( $locale ) ) {
			$locale = get_option( 'WPLANG' );
		}

		require_once ABSPATH . 'wp-admin/includes/translation-install.php';
		$translations = wp_get_available_translations();

		$display_language = function_exists( 'locale_get_display_language' )
			? locale_get_display_language( $locale )
			: '';

		return isset( $translations[ $locale ] ) ? $translations[ $locale ][ 'native_name' ] : $display_language;
	}

	/**
	 * Gets tags for recipient
	 *
	 * @param /stdCLass $user User entity.
	 *
	 * @return TagCollection
	 */
	private function get_recipient_tags( $user ) {
		$user_roles = array_intersect_key( $this->get_roles(), array_flip( $user->roles ) );

		$tags = $this->get_formatted_tags(
			$user_roles,
			self::TAG_TYPE_ROLE
		);

		$tags->add(
			$this->get_formatted_tags(
				$this->get_sites(),
				self::TAG_TYPE_SITE
			)
		);

		return $tags;
	}

	/**
	 * Gets special tags for user recipient.
	 *
	 * @param bool $is_newsletter If user is newsletter subscriber.
	 *
	 * @return SpecialTagCollection
	 */
	private function get_user_special_tags( $is_newsletter ) {
		$special_tags = new SpecialTagCollection();
		$special_tags->addTag( SpecialTag::contact() );

		if ( $is_newsletter ) {
			$special_tags->addTag( SpecialTag::subscriber() );
		}

		return $special_tags;
	}

	/**
	 * Gets special tags for contact.
	 *
	 * @param array $source_tags
	 *
	 * @return SpecialTagCollection
	 */
	private function get_contact_special_tags( $source_tags ) {
		$special_tags = new SpecialTagCollection();

		foreach ($source_tags as $tag) {
			$special_tag = SpecialTag::$tag();
			$special_tags->addTag( $special_tag );
		}

		return $special_tags;
	}

	/**
	 * Converts user language to a format supported by CleverReach.
	 *
	 * @param string $lang
	 *
	 * @return mixed
	 */
	private function format_contact_language( $lang ) {
		$lang_components = explode( '_', $lang );

		return $lang_components[ 0 ];
	}

	/**
	 * Gets config service
	 *
	 * @return Config_Service
	 */
	private function get_config_service() {
		if ( empty( $this->config_service ) ) {
			$this->config_service = ServiceRegister::getService( Configuration::CLASS_NAME );
		}

		return $this->config_service;
	}

	/**
	 * Gets recipient repository
	 *
	 * @return Recipient_Repository
	 */
	private function get_recipient_repository() {
		if ( null === $this->recipient_repository ) {
			$this->recipient_repository = new Recipient_Repository();
		}

		return $this->recipient_repository;
	}

	/**
	 * Returns an instance of contact repository.
	 *
	 * @return Base_Repository
	 *
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException
	 */
	private function get_contact_repository() {
		if ( null === $this->contact_repository ) {
			$this->contact_repository = RepositoryRegistry::getRepository( Contact::CLASS_NAME );
		}

		return $this->contact_repository;
	}

	/**
	 * Gets role repository
	 *
	 * @return Role_Repository
	 */
	private function get_role_repository() {
		if ( null === $this->role_repository ) {
			$this->role_repository = new Role_Repository();
		}

		return $this->role_repository;
	}
}
