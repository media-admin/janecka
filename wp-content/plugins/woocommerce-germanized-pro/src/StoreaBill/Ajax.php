<?php

namespace Vendidero\Germanized\Pro\StoreaBill;

defined( 'ABSPATH' ) || exit;

class Ajax {

	/**
	 * Constructor.
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax).
	 */
	protected static function add_ajax_events() {

		$ajax_events = array(
			'refresh_packing_slip',
			'create_packing_slip',
			'remove_packing_slip',
		);

		foreach ( $ajax_events as $ajax_event ) {
			add_action( 'wp_ajax_woocommerce_gzdp_' . $ajax_event, array( __CLASS__, 'suppress_errors' ), 5 );
			add_action( 'wp_ajax_woocommerce_gzdp_' . $ajax_event, array( __CLASS__, $ajax_event ) );
		}
	}

	public static function suppress_errors() {
		if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
			@ini_set( 'display_errors', 0 ); // Turn off display_errors during AJAX events to prevent malformed JSON.
		}

		$GLOBALS['wpdb']->hide_errors();
	}

	public static function create_packing_slip() {
		check_ajax_referer( 'wc-gzdp-create-packing-slip', 'security' );

		if ( ! isset( $_GET['shipment_id'] ) ) {
			wp_die();
		}

		if ( ! current_user_can( 'create_packing_slips' ) ) {
			wp_die( -1 );
		}

		$shipment_id = absint( $_GET['shipment_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_die( -1 );
		}

		$result = self::maybe_create_packing_slip( $shipment );

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=wc-gzd-shipments' ) );
		exit;
	}

	protected static function maybe_create_packing_slip( $shipment ) {
		$result = new \WP_Error( 'packing-slip-error', __( 'Error while generating packing slip.', 'woocommerce-germanized-pro' ) );

		try {
			$result = PackingSlips::sync_packing_slip( $shipment, true, true );
		} catch( \Exception $e ) {}

		return $result;
	}

	public static function refresh_packing_slip() {
		check_ajax_referer( 'wc-gzdp-refresh-packing-slip', 'security' );

		if ( ! current_user_can( 'create_packing_slips' ) ) {
			wp_die( -1 );
		}

		if ( ! isset( $_POST['shipment_id'] ) ) {
			wp_die( -1 );
		}

		$response       = array();
		$response_error = array(
			'success'  => false,
			'messages' => array(
				__( 'There was an error processing the packing slip.', 'woocommerce-germanized-pro' )
			),
		);

		$shipment_id = absint( $_POST['shipment_id'] );

		if ( ! $shipment = wc_gzd_get_shipment( $shipment_id ) ) {
			wp_send_json( $response_error );
		}

		$result = self::maybe_create_packing_slip( $shipment );

		if ( ! is_wp_error( $result ) && ( $packing_slip = wc_gzdp_get_packing_slip_by_shipment( $shipment ) ) ) {
			$response = array(
				'success'      => true,
				'packing_slip' => $packing_slip->get_id(),
				'fragments'    => array(
					'#shipment-' . $shipment_id . ' .wc-gzd-shipment-packing-slip' => self::refresh_packing_slip_html( $shipment, $packing_slip ),
				),
			);

			wp_send_json( $response );
		} else {
			if ( is_wp_error( $response ) ) {
				wp_send_json( array(
					'success'  => false,
					'messages' => $result->get_error_messages(),
				) );
			} else {
				wp_send_json( $response_error );
			}
		}
	}

	public static function remove_packing_slip() {
		check_ajax_referer( 'wc-gzdp-remove-packing-slip', 'security' );

		if ( ! isset( $_POST['packing_slip'] ) ) {
			wp_die( -1 );
		}

		$packing_slip_id = absint( $_POST['packing_slip'] );

		if ( ! current_user_can( 'delete_packing_slip', $packing_slip_id ) ) {
			wp_die( -1 );
		}

		$response_error = array(
			'success'  => false,
			'messages' => array(
				__( 'There was an error processing the packing slip.', 'woocommerce-germanized-pro' )
			),
		);

		if ( ! $packing_slip = wc_gzdp_get_packing_slip( $packing_slip_id ) ) {
			wp_send_json( $response_error );
		}

		try {
			$shipment    = $packing_slip->get_shipment();
			$shipment_id = $shipment->get_id();

			$packing_slip->delete( true );

			$response = array(
				'success'      => true,
				'fragments'    => array(
					'#shipment-' . $shipment_id . ' .wc-gzd-shipment-packing-slip' => self::refresh_packing_slip_html( $shipment ),
				),
			);

			wp_send_json( $response );
		} catch( \Exception $e ) {}

		wp_send_json( $response_error );
	}

	protected static function refresh_packing_slip_html( $p_shipment, $p_packing_slip = false ) {
		$shipment = $p_shipment;

		if ( $p_packing_slip ) {
			$packing_slip = $p_packing_slip;
		}

		ob_start();
		include_once( WC_Germanized_pro()->plugin_path() . '/includes/admin/views/html-shipment-packing-slip.php' );
		$html = ob_get_clean();

		return $html;
	}
}