<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Adds Germanized Multistep Checkout settings.
 *
 * @class 		WC_GZDP_Settings_Tab_Multistep_Checkout
 * @version		3.0.0
 * @author 		Vendidero
 */
class WC_GZDP_Settings_Tab_Contract extends WC_GZD_Settings_Tab_Contract {

	public function get_tab_settings( $current_section = '' ) {
		return array(
			array( 'title' => '', 'type' => 'title', 'id' => 'contract_options' ),

			array(
				'title' 	=> __( 'Conclusion of Contract', 'woocommerce-germanized-pro' ),
				'desc'	    => __( 'Manually review and confirm an order before closing contract.', 'woocommerce-germanized-pro' ) . '<div class="wc-gzd-additional-desc">' . __( 'By default WooCommerce only allows closing contracts right after the order submit button has been clicked. This will trigger an email which is by default used to close a contract with the customer. You may want to close a contract only after you have manually reviewed the order. If you set this option, the first email after checkout does only confirm the incoming offer. Order status is set to on-hold and no payment information will be given. Now you have time to review the order within the backend and then click the confirm button (which will confirm the contract to the customer).', 'woocommerce-germanized-pro' ) . '</div>',
				'id' 		=> 'woocommerce_gzdp_contract_after_confirmation',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'contract_options' ),
		);
	}

	public function supports_disabling() {
		return true;
	}

	protected function get_enable_option_name() {
		return 'woocommerce_gzdp_contract_after_confirmation';
	}

	public function is_enabled() {
		return 'yes' === get_option( $this->get_enable_option_name() );
	}

	public function enable() {
		$this->maybe_update_confirmation_text();
		parent::enable();
	}

	protected function maybe_update_confirmation_text() {
		if ( 'yes' !== get_option( 'woocommerce_gzdp_contract_after_confirmation' ) ) {
			update_option( 'woocommerce_gzd_email_order_confirmation_text', __( 'Your order has been processed. We are glad to confirm the order to you. Your order details are shown below for your reference.', 'woocommerce-germanized-pro' ) );
		}
	}

	protected function before_save( $settings, $current_section = '' ) {
		if ( isset( $_POST['woocommerce_gzdp_contract_after_confirmation'] ) && get_option( 'woocommerce_gzdp_contract_after_confirmation' ) !== 'yes' ) {
			$this->maybe_update_confirmation_text();
		}

		parent::before_save( $settings, $current_section );
	}
}