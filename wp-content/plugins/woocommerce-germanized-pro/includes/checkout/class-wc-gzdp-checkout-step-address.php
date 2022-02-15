<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Checkout_Step_Address extends WC_GZDP_Checkout_Step {

	public function __construct( $id, $title ) {

		parent::__construct( $id, $title, '#customer_details' );

	}

	public function submit() {
		// Temporarily set cart to not need payment to stop validating payment method input data
		add_filter( 'woocommerce_cart_needs_payment', array( $this, 'remove_payment_validation' ), 1500 );

		parent::submit();

		// Remove filter again
		add_action( 'woocommerce_after_checkout_validation', array( $this, 'remove_payment_validation_filter' ), 0 );
	}

	public function after_checkout_validation( $posted_data, $errors ) {
		WC_GZDP_Multistep_Checkout::instance()->set_posted_data( $posted_data );

		$e_messages = '';

		$registration_required = method_exists( WC_Checkout::instance(), 'is_registration_required' ) ? WC_Checkout::instance()->is_registration_required() : false;

		// Explicitly check the email address within the first step if customer account is to be created
		if ( ! is_user_logged_in() && ( $registration_required || ! empty( $posted_data['createaccount'] ) ) ) {
			$email = wc_clean( $posted_data['billing_email'] );

			if ( ! empty( $email ) && is_email ( $email ) && email_exists( $email ) ) {
				$errors->add( 'validation', apply_filters( 'woocommerce_germanized_pro_registration_error_email_exists', __( 'An account is already registered with your email address. Please log in.', 'woocommerce-germanized-pro' ), $email ) );
			}
		}

		if ( 'yes' === get_option( 'woocommerce_gzdp_checkout_privacy_policy_first_step' ) && 'yes' === get_option( 'woocommerce_gzdp_checkout_privacy_policy_checkbox' ) ) {
			if ( ! isset( $_POST['gzdp_privacy_policy'] ) ) {
				$errors->add( 'validation', apply_filters( 'woocommerce_germanized_pro_privacy_policy_error_message', __( 'Please accept our privacy policy to continue.', 'woocommerce-germanized-pro' ) ) );
			}
		}

		do_action( 'woocommerce_gzdp_multistep_checkout_validate_address', $posted_data, $errors );

		if ( $errors && is_wp_error( $errors ) ) {
			$e_messages = $errors->get_error_messages();
		}

		if ( wc_notice_count( 'error' ) != 0 || ( ! empty( $e_messages ) ) ) {
			return;
		}

		if ( $this->has_next() ) {
			// We are now changing to the new step
			WC()->session->set( 'checkout_step', $this->next->get_id() );
		}

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

	public function remove_payment_validation() {
		return false;
	}

	public function remove_payment_validation_filter() {
		remove_filter( 'woocommerce_cart_needs_payment', array( $this, 'remove_payment_validation' ), 1500 );
	}
}