<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_GZDP_Settings_Tab_Shipments extends WC_GZD_Settings_Tab_Shipments {

	protected function get_auto_packing_settings() {
		return array(
			array(
				'title' => __( 'Automated packing', 'woocommerce-germanized' ),
				'type'  => 'title',
				'id'    => 'automated_packing_options',
			),

			array(
				'title' 	=> __( 'Enable', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Enable automated shipment packing.', 'woocommerce-germanized-pro' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'By enabling this option your automatically created shipments will be packed based on your available packaging options. For that purpose we are using a knapsack algorithm to best fit available items within your packaging. <a href="%s" target="_blank">Learn more</a> about the feature.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/sendungen-automatisiert-packen' ) . '</div>',
				'id' 		=> 'woocommerce_gzdp_enable_auto_shipment_packing',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> __( 'Separation', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Separate items by shipping class.', 'woocommerce-germanized-pro' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'Use this option to make sure only items with the same (or no) shipping class are packed within the same shipment.', 'woocommerce-germanized-pro' ) ) . '</div>',
				'id' 		=> 'woocommerce_gzdp_shipment_packing_group_by_shipping_class',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_auto_shipment_packing' => '',
				),
			),

			array(
				'title' 	=> __( 'Buffer type', 'woocommerce-germanized-pro' ),
				'desc' 		=> '<div class="wc-gzd-additional-desc">' . sprintf( __( 'Choose a buffer type for spacing between items and outer dimensions of your packaging.', 'woocommerce-germanized-pro' ) ) . '</div>',
				'id' 		=> 'woocommerce_gzdp_shipment_packing_inner_buffer_type',
				'default'	=> 'fixed',
				'type' 		=> 'select',
				'options'   => array(
					'fixed' => __( 'Fixed', 'woocommerce-germanized-pro' ),
					'percentage' => __( 'Percentage', 'woocommerce-germanized-pro' )
				),
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_auto_shipment_packing' => '',
				),
			),

			array(
				'title' 	=> __( 'Fixed Buffer', 'woocommerce-germanized-pro' ),
				'desc' 		=> 'mm',
				'id' 		=> 'woocommerce_gzdp_shipment_packing_inner_fixed_buffer',
				'default'	=> '5',
				'type' 		=> 'number',
				'css'       => 'max-width: 60px',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_auto_shipment_packing' => '',
					'data-show_if_woocommerce_gzdp_shipment_packing_inner_buffer_type' => 'fixed',
					'step' => 1
				),
			),

			array(
				'title' 	=> __( 'Percentage Buffer', 'woocommerce-germanized-pro' ),
				'desc' 		=> '%',
				'id' 		=> 'woocommerce_gzdp_shipment_packing_inner_percentage_buffer',
				'default'	=> '0.5',
				'type' 		=> 'number',
				'css'       => 'max-width: 60px',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_enable_auto_shipment_packing' => '',
					'data-show_if_woocommerce_gzdp_shipment_packing_inner_buffer_type' => 'percentage',
					'step' => 0.1
				),
			),

			array( 'type' => 'sectionend', 'id' => 'automated_packing_options' ),
		);
	}
}