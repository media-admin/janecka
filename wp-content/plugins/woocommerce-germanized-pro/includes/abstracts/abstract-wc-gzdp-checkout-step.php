<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

abstract class WC_GZDP_Checkout_Step {

	public $title;
	public $id;
	public $selector;
	public $next = null;
	public $prev = null;
	public $number = 1;
	public $active = false;

	public function __construct( $id, $title, $selector = '' ) {

		$this->id = $id;
		$this->title = $title;
		$this->selector = $selector;

		add_action( 'woocommerce_gzdp_checkout_step_' . $this->get_id() . '_submit', array( $this, 'submit' ), 0 );
		add_action( 'woocommerce_gzdp_checkout_step_' . $this->get_id() . '_refresh', array( $this, 'refresh' ), 0 );

	}

	public function get_title() {
		return apply_filters( 'woocommerce_gzdp_checkout_step_title', $this->title, $this );
	}

	public function get_id() {
		return $this->id;
	}

	public function get_number() {
		return $this->number;
	}

	public function is_active() {
		return $this->active;
	}

	public function is_activated() {
		return true;
	}

	public function get_selector() {
		return apply_filters( 'woocommerce_gzdp_checkout_step_selector', $this->selector, $this );
	}

	public function has_next() {
		return ( $this->next ? true : false );
	}

	public function has_prev() {
		return ( $this->prev ? true : false );
	}

	public function get_template( $tpl = '' ) {

		$template = '';

		switch( $tpl ) {

			case 'submit':
				$template = 'submit.php';
			break;

		}

		if ( ! $tpl )
			return false;

		ob_start();
		wc_get_template( 'checkout/multistep/' . $template, array( 'step' => $this ) );
		return ob_get_clean();

	}

	public function get_wrapper_classes() {
		$classes = array(
			'step-wrapper',
			'step-wrapper-' . $this->get_number(),
		);
		
		if ( $this->get_number() == 1 ) {
			array_push( $classes, 'step-wrapper-active' );
		}

		return apply_filters( 'woocommerce_gzdp_checkout_wrapper_classes', $classes, $this );
	}

	public function submit() {
		WC()->session->set( 'checkout_step', $this->id );
		
		do_action( 'woocommerce_gzdp_checkout_step_validation', $this );

		$this->checkout_validation();
	}

	public function refresh() {
		WC()->session->set( 'checkout_step', $this->id );

		do_action( 'woocommerce_gzdp_checkout_step_refresh', $this );
		
		$this->checkout_validation();
	}

	public function checkout_validation() {
		WC()->session->set( 'checkout_step', $this->id );

		// Temporarily remove after checkout validation
		remove_all_actions( 'woocommerce_after_checkout_validation' );

		// Process checkout and stop checkout default output 
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'after_checkout_validation' ), 10, 2 );
	}

	/**
	 * @param array    $posted_data
	 * @param WP_Error $errors
	 */
	public function after_checkout_validation( $posted_data, $errors ) {
		WC_GZDP_Multistep_Checkout::instance()->set_posted_data( $posted_data );

		$e_messages = '';

		if ( $errors && is_wp_error( $errors ) ) {
		    $e_messages = $errors->get_error_messages();
		}

		if ( wc_notice_count( 'error' ) != 0 || ! empty( $e_messages ) ) {
			return;
		}

		if ( $this->has_next() ) {
			WC()->session->set( 'checkout_step', $this->next->get_id() );
		}

		/**
		 * Explicitly set result to failure instead of unknown (in terms of WC) identifier like "step".
		 * This change was made to ensure compatibility e.g. with the WooCommerce Payments Plugin which seems
		 * to explicitly check the AJAX response result and does trigger the Stripe Confirmation in case the result is not failure.
		 */
		wp_send_json (
			array(
				'fragments' => WC_GZDP_Multistep_Checkout::instance()->refresh_order_fragments( array() ),
				'result'	=> 'failure',
				'step'		=> $this->number,
				'refresh'	=> 'true',
				'messages'  => ' ',
			)
		);

		exit();
	}
}