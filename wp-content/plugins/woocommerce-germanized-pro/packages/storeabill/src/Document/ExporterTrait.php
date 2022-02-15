<?php

namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\REST\DocumentController;
use Vendidero\StoreaBill\REST\Server;

defined( 'ABSPATH' ) || exit;

trait ExporterTrait {

	/**
	 * @var null|\WC_DateTime
	 */
	protected $start_date = null;

	/**
	 * @var null|\WC_DateTime
	 */
	protected $end_date = null;

	/**
	 * @var array
	 */
	protected $filters = array();

	protected $document_type_object = null;

	protected $errors = null;

	abstract protected function get_query_args();

	abstract public function get_document_type();

	public function __construct( $document_type = '' ) {}

	public function get_nonce_action() {
		return "sab-export-{$this->get_document_type()}-{$this->get_type()}";
	}

	/**
	 * @return \WP_Error
	 */
	public function get_errors() {
		if ( is_null( $this->errors ) ) {
			$this->errors = new \WP_Error();
		}

		return $this->errors;
	}

	public function has_errors() {
		return sab_wp_error_has_errors( $this->errors );
	}

	public function add_error( $msg ) {
		$errors = $this->get_errors();

		if ( is_a( $msg, 'WP_Error' ) ) {
			$errors->add( $msg->get_error_code(), $msg->get_error_message(), $msg->get_error_data() );
		} else {
			$errors->add( 'export-error', $msg );
		}
	}

	protected function get_api_endpoint() {
		return $this->get_document_type_object()->api_endpoint;
	}

	protected function get_document_type_object() {
		if ( is_null( $this->document_type_object ) ) {
			$this->document_type_object = sab_get_document_type( $this->get_document_type() );
		}

		return $this->document_type_object;
	}

	public function get_nonce_download_action() {
		return $this->get_nonce_action() . '-download';
	}

	/**
	 * Returns export start date
	 *
	 * @since 3.9.0
	 * @return \DateTime|null
	 */
	public function get_start_date() {
		return apply_filters( "{$this->get_hook_prefix()}start_date", $this->start_date );
	}

	/**
	 * @param \WC_DateTime|\DateTime $datetime
	 *
	 * @return string
	 */
	protected function get_gm_date( $datetime, $format = 'Y-m-d' ) {
		return is_a( $datetime, 'WC_DateTime' ) ? $datetime->date( $format ) : $datetime->format( $format );
	}

	/**
	 * Returns export start date
	 *
	 * @since 3.9.0
	 */
	public function set_start_date( $datetime ) {
		if ( ! is_a( $datetime, 'DateTime' ) ) {
			$datetime = wc_string_to_datetime( $datetime );
		}

		$this->start_date = $datetime;
	}

	/**
	 * Returns export start date
	 *
	 * @since 3.9.0
	 * @return \DateTime|null
	 */
	public function get_end_date() {
		return apply_filters( "{$this->get_hook_prefix()}end_date", $this->end_date );
	}

	/**
	 * Returns export start date
	 *
	 * @since 3.9.0
	 */
	public function set_end_date( $datetime ) {
		if ( ! is_a( $datetime, 'DateTime' ) ) {
			$datetime = wc_string_to_datetime( $datetime );
		}

		$this->end_date = $datetime;
	}

	public function set_filters( $args ) {
		$this->filters = $args;
	}

	public function get_filters() {
		return apply_filters( "{$this->get_hook_prefix()}filters", $this->filters );
	}

	public function get_filter( $prop ) {
		$filters = $this->get_filters();
		$filter  = null;

		if ( array_key_exists( $prop, $filters ) ) {
			$filter = $filters[ $prop ];
		}

		return apply_filters( "{$this->get_hook_prefix()}filter", $filter, $prop );
	}

	protected function get_default_settings_name() {
		return $this->get_hook_prefix() . 'default_settings';
	}

	public function get_default_setting( $key, $default = false ) {
		$settings = $this->get_default_settings();

		if ( array_key_exists( $key, $settings ) ) {
			return $settings[ $key ];
		} else {
			return $default;
		}
	}

	public function get_default_filter_setting( $key, $default = false ) {
		$settings = $this->get_default_settings();
		$filters  = isset( $settings['filters'] ) ? (array) $settings['filters'] : array();

		if ( array_key_exists( $key, $filters ) ) {
			return $filters[ $key ];
		} else {
			return $default;
		}
	}

	protected function update_default_settings() {
		/**
		 * Store current options as default options for the next export.
		 */
		$option_name = $this->get_hook_prefix() . 'default_settings';
		$new_options = apply_filters( "{$this->get_hook_prefix()}new_default_settings", array(
			'filters'    => $this->get_filters(),
			'start_date' => $this->get_gm_date( $this->get_start_date() ),
			'end_date'   => $this->get_gm_date( $this->get_end_date() ),
		), $this );

		update_option( $option_name, $new_options );
	}

	public function get_default_settings() {
		$settings = get_option( $this->get_default_settings_name(), array() );

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$settings = wp_parse_args( $settings, array(
			'filters'    => array(),
			'start_date' => '',
			'end_date'   => ''
		) );

		return apply_filters( "{$this->get_hook_prefix()}default_settings", $settings, $this );
	}

	protected function get_endpoint() {
		if ( $controller = $this->get_controller() ) {
			return '/' . $controller->get_endpoint();
		}

		return false;
	}

	protected function get_documents() {
		$result = array(
			'documents' => array(),
			'total'     => 0,
		);

		if ( $controller = $this->get_controller() ) {
			$request = new \WP_REST_Request( 'GET', $this->get_endpoint() );
			$request->set_query_params( $this->get_query_args() );

			$response = $controller->get_items( $request );

			if ( 200 === $response->get_status() ) {
				$server  = rest_get_server();
				$data    = $server->response_to_data( $response, false );
				$headers = $response->get_headers();

				$result['total']     = sizeof( $data );
				$result['documents'] = $data;

				if ( isset( $headers['X-WP-Total'] ) ) {
					$result['total'] = absint( $headers['X-WP-Total'] );
				}
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}documents", $result, $this->get_query_args(), $this );
	}

	protected function get_additional_query_args() {
		return array();
	}

	public function render_filters() {}

	protected function get_hook_prefix() {
		return "storeabill_{$this->get_document_type()}_{$this->get_type()}_export_";
	}

	/**
	 * @return DocumentController
	 */
	protected function get_controller() {
		return Server::instance()->get_controller( $this->get_api_endpoint() );
	}

	public function get_admin_url() {
		return add_query_arg( array( 'export_type' => $this->get_type(), 'document_type' => $this->get_document_type() ), admin_url( 'admin.php?page=sab-accounting-export' ) );
	}
}
