<?php
/**
 * Abstract document
 *
 * @package Vendidero/StoreaBill
 * @version 1.0.0
 */
namespace Vendidero\StoreaBill\Document;
use Vendidero\StoreaBill\Data;

use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * DocumentNotice Class.
 */
class Notice extends Data {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'document_notice';

	/**
	 * Contains a reference to the data store for this class.
	 *
	 * @since 1.0.0
	 * @var object
	 */
	protected $data_store_name = 'document_notice';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $cache_group = 'document-notices';

	protected $document = null;

	protected $key = '';

	/**
	 * Stores document data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'          => null,
		'type'                  => '',
		'document_id'           => '',
		'text'                  => '',
	);

	/**
	 * Get the document if ID is passed, otherwise the document is new and empty.
	 * This class should NOT be instantiated, but the `` function should be used.
	 *
	 * @param int|object|Document $document Document to read.
	 */
	public function __construct( $data = 0 ) {
		$this->object_type = $this->get_type();

		parent::__construct( $data );

		if ( $data instanceof Notice ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		}

		$this->data_store = sab_load_data_store( $this->data_store_name );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			try {
				$this->data_store->read( $this );
			} catch ( Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		} else {
			$this->set_object_read( true );
		}
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return "storeabill_document_notice_get_";
	}

	public function get_key() {
		return ( $this->get_id() > 0 ) ? $this->get_id() : $this->key;
	}

	public function set_key( $key ) {
		$this->key = $key;
	}

	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	public function get_text( $context = 'view' ) {
		return $this->get_prop( 'text', $context );
	}

	public function get_document_id( $context = 'view' ) {
		return $this->get_prop( 'document_id', $context );
	}

	/**
	 * Get parent document object.
	 *
	 * @return Document|boolean
	 */
	public function get_document() {
		if ( is_null( $this->document ) && 0 < $this->get_document_id() ) {
			$this->document = sab_get_document( $this->get_document_id() );
		}

		$document = ( $this->document ) ? $this->document : false;

		return $document;
	}

	/**
	 * Return the date this document was created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Set the date this document was created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	public function set_text( $text ) {
		$this->set_prop( 'text', $text );
	}

	/**
	 * Set document id.
	 *
	 * @param int $value document id.
	 */
	public function set_document_id( $value ) {
		$this->document = null;

		$this->set_prop( 'document_id', absint( $value ) );
	}

	/**
	 * @param Document $document
	 */
	public function set_document( $document ) {
		$this->set_document_id( $document->get_id() );

		$this->document = $document;
	}
}
