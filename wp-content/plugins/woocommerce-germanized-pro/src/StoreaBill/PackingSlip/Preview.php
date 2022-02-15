<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PackingSlip;

use Vendidero\Germanized\Pro\StoreaBill\PackingSlip;
use Vendidero\StoreaBill\Interfaces\Previewable;

defined( 'ABSPATH' ) || exit;

class Preview extends PackingSlip implements Previewable {

	protected $editor_preview = false;

	public function __construct( $args = array() ) {
		parent::__construct( 0 );

		$args = wp_parse_args( $args, array(
			'is_editor_preview' => false,
		) );

		$this->set_is_editor_preview( $args['is_editor_preview'] );
		$this->set_date_created( sab_string_to_datetime( 'now' ) );
		$this->set_number( 1 );
		$this->set_formatted_number( $this->format_number( $this->get_number() ) );
		$this->set_shipment_id( 0 );
		$this->set_order_id( 4031 );

		$this->set_address( array(
			'first_name' => _x( 'John', 'example-address', 'woocommerce-germanized-pro' ),
			'last_name'  => _x( 'Doe', 'example-address', 'woocommerce-germanized-pro' ),
			'address_1'  => _x( 'Doe Street 12', 'example-address', 'woocommerce-germanized-pro' ),
			'postcode'   => _x( '12345', 'example-address', 'woocommerce-germanized-pro' ),
			'city'       => _x( 'Berlin', 'example-address', 'woocommerce-germanized-pro' ),
			'country'    => _x( 'DE', 'example-address', 'woocommerce-germanized-pro' ),
		) );

		$attributes = array(
			array(
				'key'   => 'attribute_1',
				'value' => __( 'Value 1', 'woocommerce-germanized-pro' ),
				'label' => __( 'Attribute 1', 'woocommerce-germanized-pro' ),
				'order' => 1,
			),
			array(
				'key'   => 'attribute_2',
				'value' => __( 'Value 2', 'woocommerce-germanized-pro' ),
				'label' => __( 'Attribute 2', 'woocommerce-germanized-pro' ),
				'order' => 2,
			),
		);

		$item = new ProductItem();
		$item->set_attributes( $attributes );
		$item->set_name( __( 'A simple item', 'woocommerce-germanized-pro' ) );
		$item->set_quantity( 2 );
		$item->set_sku( '123' );
		$item->set_price( 10 );
		$item->set_total( 20 );

		$this->add_item( $item );

		/**
		 * Editor previews do only allow previewing one item.
		 * While rendering the preview (e.g. as PDF) it is useful to include more than one item.
		 */
		if ( ! $this->is_editor_preview() ) {
			$item = new ProductItem();
			$item->set_attributes( $attributes );
			$item->set_name( __( 'Another simple item', 'woocommerce-germanized-pro' ) );
			$this->add_item( $item );
			$item->set_sku( '234' );
			$item->set_price( 10 );
			$item->set_total( 10 );
		}

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

	public function get_shipment_number( $context = 'view' ) {
		return 123;
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