<?php

namespace Vendidero\StoreaBill;

defined( 'ABSPATH' ) || exit;

/**
 * WC_Abstract_Order class.
 */
class Data extends \WC_Data {

	protected $data_store_name = '';

	public function __construct( $read = 0 ) {
		$this->data_store_name = substr(  $this->data_store_name, 0, 4 ) !== 'sab_' ? 'sab_' . $this->data_store_name : $this->data_store_name;

		parent::__construct( $read );
	}

	public function has_changed() {
		$changes     = $this->get_changes();
		$has_changed = ! empty( $changes ) ? true : false;

		return $has_changed;
	}

	/**
	 * Fix array casting bug in Woo core. Seems like array casting WC_Meta_Data does not work as expected.
	 * Call get_data instead.
	 *
	 * @param array $data Key/Value pairs.
	 */
	public function set_meta_data( $data ) {
		if ( ! empty( $data ) && is_array( $data ) ) {
			$this->maybe_read_meta_data();
			foreach ( $data as $meta ) {
				$meta = $meta->get_data();
				if ( isset( $meta['key'], $meta['value'], $meta['id'] ) ) {
					$this->meta_data[] = new \WC_Meta_Data(
						array(
							'id'    => $meta['id'],
							'key'   => $meta['key'],
							'value' => $meta['value'],
						)
					);
				}
			}
		}
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete orders from the database.
	| Written in abstract fashion so that the way orders are stored can be
	| changed more easily in the future.
	|
	| A save method is included for convenience (chooses update or create based
	| on if the order exists yet).
	|
	*/

	/**
	 * Save data to the database.
	 *
	 * @since 3.0.0
	 * @return int order ID
	 */
	public function save() {
		if ( ! $this->data_store ) {
			return $this->get_id();
		}

		try {
			/**
			 * Trigger action before saving to the DB. Allows you to adjust object props before save.
			 *
			 * @param Data              $this The object being saved.
			 * @param \WC_Data_Store_WP $data_store THe data store persisting the data.
			 */
			do_action( 'storeabill_before_' . $this->object_type . '_object_save', $this, $this->data_store );

			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}

			/**
			 * Trigger action after saving to the DB.
			 *
			 * @param Data              $this The object being saved.
			 * @param \WC_Data_Store_WP $data_store THe data store persisting the data.
			 */
			do_action( 'storeabill_after_' . $this->object_type . '_object_save', $this, $this->data_store );

		} catch( \Exception $e ) {
			$this->handle_exception( $e );
		}

		return $this->get_id();
	}

	/**
	 * Log an error about this data if exception is encountered.
	 *
	 * @param \Exception $e Exception object.
	 * @param string     $message Message regarding exception thrown.
	 * @since 3.7.0
	 */
	protected function handle_exception( $e, $message = 'Error' ) {
		wc_get_logger()->error(
			$message,
			array(
				$this->object_type => $this,
				'error'            => $e,
			)
		);
	}
}
