<?php
/**
 * Journal
 *
 * @package Vendidero/StoreaBill
 * @version 1.0.0
 */
namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Data;
use Vendidero\StoreaBill\Package;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;

class Journal extends Data {

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $object_type = 'journal';

	/**
	 * Stores journal data.
	 * @var array
	 */
	protected $data = array(
		'name'            => '',
		'number_format'   => '',
		'number_min_size' => '',
		'is_archived'     => '',
		'type'            => '',
		'date_last_reset' => null,
		'reset_interval'  => '',
	);

	/**
	 * Cache group.
	 * @var string
	 */
	protected $cache_group = 'journals';

	protected $data_store_name = 'journal';

	public function __construct( $journal = 0 ) {

		parent::__construct( $journal );

		if ( is_numeric( $journal ) && $journal > 0 ) {
			$this->set_id( $journal );
		} elseif ( $journal instanceof self ) {
			$this->set_id( absint( $journal->get_id() ) );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = sab_load_data_store( $this->data_store_name );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return "{$this->get_general_hook_prefix()}get_";
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_general_hook_prefix() {
		return "storeabill_journal_";
	}

	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * @param string $context
	 *
	 * @return \WC_DateTime|null
	 */
	public function get_date_last_reset( $context = 'view' ) {
		return $this->get_prop( 'date_last_reset', $context );
	}

	public function get_reset_interval( $context = 'view' ) {
		return $this->get_prop( 'reset_interval', $context );
	}

	public function get_number_format( $context = 'view' ) {
		return $this->get_prop( 'number_format', $context );
	}

	public function get_number_min_size( $context = 'view' ) {
		return $this->get_prop( 'number_min_size', $context );
	}

	public function get_is_archived( $context = 'view' ) {
		return $this->get_prop( 'is_archived', $context );
	}

	public function is_archived() {
		return true === $this->get_is_archived();
	}

	public function set_name( $value ) {
		$this->set_prop( 'name', $value );
	}

	public function set_date_last_reset( $value ) {
		$this->set_date_prop( 'date_last_reset', $value );
	}

	public function set_reset_interval( $value ) {
		$this->set_prop( 'reset_interval', $value );
	}

	public function needs_reset( $when = null ) {
		$reset_date     = $this->get_date_last_reset();
		$reset_interval = $this->get_reset_interval();
		$needs_reset    = false;
		$when           = ! is_null( $when ) ? $when : sab_get_current_datetime();

		if ( ! $reset_date ) {
			$reset_date = clone $when;
		}

		/**
		 * Compare local times and watch for changes.
		 */
		switch( $reset_interval ) {
			case "yearly":
				$needs_reset = ( $when->date_i18n( 'Y' ) != $reset_date->date_i18n( 'Y' ) );
				break;
			case "monthly":
				$needs_reset = ( $when->date_i18n( 'm' ) != $reset_date->date_i18n( 'm' ) );
				break;
			case "weekly":
				$needs_reset = ( $when->date_i18n( 'W' ) != $reset_date->date_i18n( 'W' ) );
				break;
			case "daily":
				$needs_reset = ( $when->date_i18n( 'd' ) != $reset_date->date_i18n( 'd' ) );
				break;
			default:
				$needs_reset = false;
				break;
		}

		return apply_filters( "{$this->get_general_hook_prefix()}needs_reset", $needs_reset, $this );
	}

	public function set_type( $value ) {
		$this->set_prop( 'type', $value );
	}

	public function set_number_format( $value ) {
		$this->set_prop( 'number_format', $value );
	}

	public function set_number_min_size( $value ) {
		$this->set_prop( 'number_min_size', absint( $value ) );
	}

	public function set_is_archived( $value ) {
		$this->set_prop( 'is_archived', sab_string_to_bool( $value ) );
	}

	public function reset() {
		$this->update_last_number( 0 );
	}

	/**
	 * @return int|\WP_Error
	 */
	public function get_last_number() {
		return $this->data_store->get_last_number( $this );
	}

	/**
	 * @param integer $last_number
	 *
	 * @return bool|\WP_Error
	 */
	public function update_last_number( $last_number ) {
		$result = $this->data_store->set_last_number( $this, absint( $last_number ) );

		if ( ! is_wp_error( $result ) ) {
			Package::log( sprintf( 'Journal %1$s last number forced update to %2$s', $this->get_type(), $last_number ) );

			$this->set_date_last_reset( time() );
			$this->save();
		}

		return $result;
	}

	/**
	 * @return int|\WP_Error
	 */
	public function next_number() {
		/**
		 * Check if a number reset is necessary before creating the next number.
		 */
		if ( $this->needs_reset() ) {
			$this->reset();
		}

		return $this->data_store->next_number( $this );
	}

	/**
	 * Journals do not support meta data handling.
	 *
	 * @param bool $force
	 */
	public function read_meta_data( $force = false ) {
		$this->meta_data = array();
	}
}