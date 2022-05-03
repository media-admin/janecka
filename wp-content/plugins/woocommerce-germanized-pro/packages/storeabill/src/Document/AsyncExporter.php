<?php

namespace Vendidero\StoreaBill\Document;

defined( 'ABSPATH' ) || exit;

class AsyncExporter {

	/**
	 * @var \Vendidero\StoreaBill\Interfaces\Exporter|null
	 */
	protected $exporter = null;

	protected $args = array();

	protected $id = '';

	protected $status = 'running';

	public function __construct( $id, $args = array() ) {
		$this->id     = $id;
		$default_args = array(
			'document_type' => 'invoice',
			'type'          => 'csv',
			'step'          => 1,
			'filters'       => array(),
			'start_date'    => null,
			'end_date'      => null,
		);

		if ( $transient = get_transient( "sab_export_{$this->get_id()}" ) ) {
			$this->status = $transient['status'];
			$default_args = $transient['args'];
		}

		$this->args = wp_parse_args( $args, $default_args );

		$this->args['filters'] = (array) $this->args['filters'];

		if ( ! $this->exporter = sab_get_document_type_exporter( $this->args['document_type'], $this->args['type'] ) ) {
			throw new \Exception( _x( 'No applicable exporter found.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$filename = $id . '-export.' . $this->exporter->get_file_extension();

		if ( empty( $this->args['start_date'] ) || ! \DateTime::createFromFormat( 'Y-m-d', $this->args['start_date'] ) ) {
			throw new \Exception( _x( 'Please make sure to provide a valid start date.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		if ( empty( $this->args['end_date'] ) || ! \DateTime::createFromFormat( 'Y-m-d', $this->args['end_date'] ) ) {
			throw new \Exception( _x( 'Please make sure to provide a valid end date.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$start_date = ! empty( $this->args['start_date'] ) ? sab_string_to_datetime( $this->args['start_date'] ) : false;
		$end_date   = ! empty( $this->args['end_date'] ) ? sab_string_to_datetime( $this->args['end_date'] ) : false;
		$today      = sab_string_to_datetime( 'now' );

		if ( $start_date && $start_date > $today ) {
			throw new \Exception( _x( 'Please choose a start date from the past.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		if ( $start_date && $end_date && $start_date > $end_date ) {
			throw new \Exception( _x( 'The end date must be after the start date.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$this->exporter->set_filename( $filename );

		if ( $start_date ) {
			$this->exporter->set_start_date( $start_date );
		}

		if ( $end_date ) {
			$this->exporter->set_end_date( $end_date );
		}

		$filters = array_diff_key( $this->args['filters'], array_flip( array( 'start_date', 'end_date' ) ) );

		if ( ! empty( $filters ) ) {
			$this->exporter->set_filters( $filters );
		}

		$this->exporter->set_page( $this->args['step'] );
	}

	public function exists() {
		return get_transient( "sab_export_{$this->get_id()}" );
	}

	public function get_filters() {
		return $this->exporter->get_filters();
	}

	public function get_filename() {
		return $this->exporter->get_filename();
	}

	public function get_id() {
		return $this->id;
	}

	public function get_document_type() {
		return $this->exporter->get_document_type();
	}

	public function get_type() {
		return $this->exporter->get_type();
	}

	public function get_status() {
		return $this->status;
	}

	public function get_start_date() {
		return $this->exporter->get_start_date();
	}

	public function get_end_date() {
		return $this->exporter->get_end_date();
	}

	public function get_percent_complete() {
		return $this->exporter->get_percent_complete();
	}

	/**
	 * Returns true in case export has finished.
	 *
	 * @return array|bool|\WP_Error
	 */
	public function next() {
		$this->exporter->generate_file();

		if ( $this->exporter->has_errors() ) {
			return $this->exporter->get_errors();
		} else {
			if ( $this->exporter->get_percent_complete() >= 100 ) {
				$status = 'complete';
			} else {
				$status = 'running';
				$this->args['step'] = $this->args['step'] + 1;
			}

			$this->status = $status;

			set_transient( "sab_export_{$this->get_id()}", array(
				'status' => $status,
				'id'     => $this->get_id(),
				'args'   => $this->args,
			), DAY_IN_SECONDS );

			$exporters = array_filter( (array) get_option( 'storeabill_running_async_exporter', array() ) );

			if ( ! array_key_exists( $this->get_id(), $exporters ) ) {
				$exporters[ $this->get_id() ] = $this->get_filename();

				update_option( 'storeabill_running_async_exporter', $exporters, false );
			}

			if ( 'complete' === $status ) {
				return true;
			} else {
				return $this->args;
			}
		}
	}

	public function delete() {
		delete_transient( "sab_export_{$this->get_id()}" );
	}

	public function export() {
		if ( 'complete' === $this->get_status() ) {
			$exporters = array_filter( (array) get_option( 'storeabill_running_async_exporter', array() ) );

			if ( array_key_exists( $this->get_id(), $exporters ) ) {
				unset( $exporters[ $this->get_id() ] );

				update_option( 'storeabill_running_async_exporter', $exporters, false );
			}

			delete_transient( "sab_export_{$this->get_id()}" );

			$this->exporter->export();
		}

		return false;
	}
}