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
class WC_GZDP_Settings_Tab_Taxes extends WC_GZD_Settings_Tab_Taxes {

	protected function get_vat_id_settings() {
		return array(
			array(	'title' => __( 'VAT ID', 'woocommerce-germanized-pro' ), 'type' => 'title', 'id' => 'vat_id_options' ),

			array(
				'title' 	=> __( 'Check', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Enable VAT ID check.', 'woocommerce-germanized-pro' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'This will add a new field within your checkout (vat number). Customers from other EU states owning a valid VAT ID will be able to remove VAT. VAT ID is being validated through the European Union VAT service API. <a href="%s" target="_blank">Learn more</a> about configuring your VAT ID check.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/umsatzsteuer-id-check' ) . '</div>',
				'id' 		=> 'woocommerce_gzdp_enable_vat_check',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> __( 'Mandatory', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'VAT ID field shall be mandatory.', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_vat_id_required',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_vat_check' => '',
				),
				'desc_tip'	=> __( 'You may require a valid VAT ID to complete the checkout and/or registration.', 'woocommerce-germanized-pro' ),
			),

			array(
				'title' 	=> __( 'Request VAT ID', 'woocommerce-germanized-pro' ),
				'desc_tip'  => __( 'Insert your own VAT ID here. Will be transmitted during the request to make sure that an identifier is returned.', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_vat_requester_vat_id',
				'default'	=> '',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_vat_check' => '',
				),
				'type' 		=> 'text',
			),

			array(
				'title' 	=> __( 'Additional check', 'woocommerce-germanized-pro' ),
				'desc'      => '<div class="wc-gzd-additional-desc">' . sprintf( __( 'You might want to make sure that company data connected to the VAT ID matches data declared by the customer (e.g. within checkout form). In case the VIES API returns additional company data (some member states do not support that) Germanized will check whether the data exists within the data returned by the API. In case the data could not be verified, error messages will be shown. As the data supplied by the API is not structured, a simple word-check is performed. <a href="%s" target="_blank">Learn more</a>.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/umsatzsteuer-id-check' ) . '</div>',
				'id' 		=> 'woocommerce_gzdp_vat_id_additional_field_check',
				'default'	=> array(),
				'type'      => 'multiselect',
				'class'     => 'wc-enhanced-select',
				'options'   => array(
					'company'  => __( 'Company', 'woocommerce-germanized-pro' ),
					'postcode' => __( 'Postcode', 'woocommerce-germanized-pro' ),
					'city'     => __( 'City', 'woocommerce-germanized-pro' )
				),
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_vat_check' => '',
				),
			),

			array(
				'title' 	=> __( 'Company field', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Make company field mandatory in case a VAT ID is supplied.', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_vat_id_company_required',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_vat_check' => '',
				),
			),

			array(
				'title' 	=> __( 'Cache', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'days', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_vat_check_cache',
				'default'	=> 7,
				'type' 		=> 'number',
				'css'       => 'max-width: 60px;',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_vat_check' => '',
				),
				'desc_tip'  => __( 'Enable positive API reponse cache to ensure that validated VAT IDs won\'t get checked on every request (leads to better performance). Leave empty to disable caching.', 'woocommerce-germanized-pro' )
			),

			array(
				'title' 	=> __( 'On Login', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Remove VAT for users with valid VAT ID after login.', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_enable_vat_check_login',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_vat_check' => '',
				),
				'desc_tip'	=> __( 'This option will remove vat for customers with valid VAT ID directly after login. VAT ID for customers will be populated during checkout.', 'woocommerce-germanized-pro' ),
			),

			array(
				'title' 	=> __( 'Registration', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Enable VAT ID check within registration form.', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_enable_vat_check_register',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_vat_check' => '',
				),
				'desc_tip'	=> __( 'This option inserts a VAT ID field within the registration form and validates the ID accordingly if the customer provides one.', 'woocommerce-germanized-pro' ),
			),

			array(
				'title' 	=> __( 'Mandatory', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'VAT ID field shall be mandatory for registration.', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_vat_id_registration_required',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_vat_check' => '',
					'data-show_if_woocommerce_gzdp_enable_vat_check_register' => '',
				),
				'desc_tip'	=> __( 'You may require a valid VAT ID to complete registration.', 'woocommerce-germanized-pro' ),
			),

			array(
				'title' 	=> __( 'Base country', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Enable VAT ID Check for your base country.', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_vat_id_base_country_included',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_vat_check' => '',
				),
				'desc_tip'	=> __( 'You may want to check VAT IDs for your base country as well. The customer will not turn into a vat exempt.', 'woocommerce-germanized-pro' ),
			),

			array(
				'title' 	=> __( 'Virtual Products B2B', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Do only sell virtual products to EU customers if they own a valid VAT ID.', 'woocommerce-germanized-pro' ) . '<div class="wc-gzd-additional-desc">' . __( 'This option will help you to stop selling virtual products to private customers from EU. You might want to choose this option to avoid administrative barriers regarding VAT calculation.', 'woocommerce-germanized-pro' ) . '</div>',
				'id' 		=> 'woocommerce_gzdp_force_virtual_product_business',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_vat_check' => '',
				),
			),

			array( 'type' => 'sectionend', 'id' => 'vat_id_options' ),
		);
	}

	protected function after_save( $settings, $current_section = '' ) {
		if ( '' === $current_section ) {

			// Check if SoapClient Class exists
			if ( isset( $_POST['woocommerce_gzdp_enable_vat_check'] ) && ! empty( $_POST['woocommerce_gzdp_enable_vat_check'] ) && ! class_exists( 'SoapClient' ) ) {
				WC_Admin_Settings::add_error( __( 'To enable VAT check PHP 5.0.1 has to be installed (SoapClient is needed)', 'woocommerce-germanized-pro' ) );
				update_option( 'woocommerce_gzdp_enable_vat_check', 'no' );
			}

			// Parse requester VAT ID
			if ( isset( $_POST['woocommerce_gzdp_vat_requester_vat_id'] ) && ! empty( $_POST['woocommerce_gzdp_vat_requester_vat_id'] ) )  {
				if ( ! class_exists( 'WC_GZDP_VAT_Helper' ) ) {
					WC_germanized_pro()->load_vat_module();
				}

				$vat_id = WC_GZDP_VAT_Helper::instance()->get_vat_id_from_string( wc_clean( $_POST['woocommerce_gzdp_vat_requester_vat_id'] ) );
				update_option( 'woocommerce_gzdp_vat_requester_vat_id', $vat_id['country'] . $vat_id['number'] );
			}
		}

		parent::after_save( $settings, $current_section );
	}
}