<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Interfaces\Previewable;

defined( 'ABSPATH' ) || exit;

/**
 * SimpleInvoice class
 */
class CancellationPreview extends Cancellation implements Previewable {

	protected $editor_preview = false;

	protected $parent = false;

	public function __construct( $args = array() ) {
		parent::__construct( 0 );

		$args = wp_parse_args( $args, array(
			'is_editor_preview' => false,
		) );

		$this->parent = new SimplePreview( $args );

		$this->set_is_editor_preview( $args['is_editor_preview'] );
		$this->set_date_created( time() );
		$this->set_prices_include_tax( true );
		$this->set_number( 1 );
		$this->set_formatted_number( $this->format_number( $this->get_number() ) );
		$this->set_address( $this->parent->get_address() );

		$this->set_parent_number( $this->parent->get_number() );
		$this->set_parent_formatted_number( $this->parent->get_formatted_number() );
		$this->set_reason( _x( 'Preview refund message', 'storeabill-core', 'woocommerce-germanized-pro' ) );

		foreach( $this->parent->get_items( $this->parent->get_item_types_cancelable() ) as $item ) {
			$new_item = clone $item;
			$new_item->set_id( 0 );

			$this->add_item( $new_item );
		}

		foreach( $this->parent->get_meta_data() as $meta ) {
			$this->update_meta_data( $meta->key, $meta->value );
		}

		$this->calculate_totals();
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