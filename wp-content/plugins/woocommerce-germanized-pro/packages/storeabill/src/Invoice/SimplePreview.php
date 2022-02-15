<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Interfaces\Previewable;

defined( 'ABSPATH' ) || exit;

/**
 * SimpleInvoice class
 */
class SimplePreview extends Simple implements Previewable {

	protected $editor_preview = false;

	public function __construct( $args = array() ) {
		parent::__construct( 0 );

		$args = wp_parse_args( $args, array(
			'is_editor_preview' => false,
		) );

		$this->set_is_editor_preview( $args['is_editor_preview'] );
		$this->set_date_created( time() );
		$this->set_date_due( sab_calculate_invoice_date_due( sab_string_to_datetime( 'now' ) ) );
		$this->set_payment_status( 'complete' );
		$this->set_date_paid( sab_string_to_datetime( 'now' ) );
		$this->set_date_of_service( sab_string_to_datetime( 'now' ) );
		$this->set_date_of_service_end( sab_string_to_datetime( 'now' ) );
		$this->set_prices_include_tax( true );
		$this->set_number( 1 );
		$this->set_order_number( '1234' );

		$this->set_formatted_number( $this->format_number( $this->get_number() ) );
		$this->set_payment_method_name( 'sample' );
		$this->set_payment_method_title( _x( 'Sample payment method', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		$this->set_discount_notice( _x( 'XYZ123', 'storeabill-core', 'woocommerce-germanized-pro' ) );

		$this->set_address( array(
			'first_name' => _x( 'John', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'last_name'  => _x( 'Doe', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'address_1'  => _x( 'Doe Street 12', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'postcode'   => _x( '12345', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'city'       => _x( 'Berlin', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'country'    => _x( 'DE', 'storeabill-core', 'woocommerce-germanized-pro' ),
		) );

		$attributes   = array();
		$attributes[] = array(
			'key'   => 'attribute_1',
			'value' => _x( 'Value 1', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'label' => _x( 'Attribute 1', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'order' => 1,
		);
		$attributes[] = array(
			'key'   => 'attribute_2',
			'value' => _x( 'Value 2', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'label' => _x( 'Attribute 2', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'order' => 2,
		);

		$item = new ProductItem();
		$item->set_prices_include_tax( true );
		$item->set_name( _x( 'A simple invoice item name', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		$item->set_sku( 245 );
		$item->set_line_total( 26 );
		$item->set_quantity( 2 );
		$item->set_line_subtotal( 30 );
		$item->set_attributes( $attributes );
		$item->add_tax_rate( array(
			'percent' => 19,
		) );

		$this->add_item( $item );

		$item = new ShippingItem();
		$item->set_prices_include_tax( true );
		$item->set_name( _x( 'Shipping', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		$item->set_line_total( 4 );
		$item->set_quantity( 1 );
		$item->set_line_subtotal( 4 );
		$item->add_tax_rate( array(
			'percent' => 19,
		) );

		$this->add_item( $item );

		$item = new FeeItem();
		$item->set_prices_include_tax( true );
		$item->set_name( _x( 'Payment', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		$item->set_line_total( 4 );
		$item->set_quantity( 1 );
		$item->set_line_subtotal( 4 );
		$item->add_tax_rate( array(
			'percent' => 19,
		) );

		$this->add_item( $item );

		/**
		 * Editor previews do only allow previewing one item.
		 * While rendering the preview (e.g. as PDF) it is useful to include more than one item.
		 */
		if ( ! $this->is_editor_preview() ) {
			$item = new ProductItem();
			$item->set_prices_include_tax( true );
			$item->set_sku( 246 );
			$item->set_name( _x( 'Another simple invoice item name', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			$item->set_line_total( 10 );
			$item->set_attributes( $attributes );
			$item->add_tax_rate( array(
				'percent' => 19,
			) );
			$item->set_line_subtotal( 10 );

			$this->add_item( $item );
		}

		$this->calculate_totals();

		foreach( $this->get_items() as $item ) {
			foreach ( $this->get_item_preview_meta( $item->get_item_type(), $item ) as $meta ) {
				$meta = wp_parse_args( $meta, array(
					'type'    => '',
					'preview' => '',
				) );

				$item->update_meta_data( $meta['type'], $meta['preview'] );
			}
		}

		foreach ( $this->get_preview_meta() as $meta ) {
			$meta = wp_parse_args( $meta, array(
				'type'    => '',
				'preview' => '',
			) );

			$this->update_meta_data( $meta['type'], $meta['preview'] );
		}
	}

	public function get_preview_meta() {
		$meta_fields = apply_filters( "storeabill_{$this->get_type()}_preview_meta_types", array(), $this );

		return $meta_fields;
	}

	public function get_item_preview_meta( $item_type, $item = false ) {
		$meta_fields = apply_filters( "storeabill_{$this->get_type()}_preview_{$item_type}_item_meta_types", array(), $item, $this );

		return $meta_fields;
	}

	public function is_editor_preview() {
		return $this->editor_preview === true;
	}

	public function set_is_editor_preview( $is_editor ) {
		$this->editor_preview = $is_editor;
	}

	public function set_template( $template ) {
		$this->template = $template;
	}

	public function save() {
		foreach( $this->get_items() as $item ) {
			$item->apply_changes();
		}

		$this->apply_changes();

		return false;
	}
}