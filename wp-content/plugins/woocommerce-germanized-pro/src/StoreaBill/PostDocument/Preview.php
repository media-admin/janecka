<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PostDocument;

use Vendidero\Germanized\Pro\StoreaBill\PostDocument;
use Vendidero\StoreaBill\Interfaces\Previewable;

defined( 'ABSPATH' ) || exit;

class Preview extends PostDocument implements Previewable {

	protected $editor_preview = false;

	public function __construct( $args = array() ) {
		parent::__construct( 0 );

		$args = wp_parse_args( $args, array(
			'is_editor_preview' => false,
		) );

		$post_id = wc_get_page_id( 'terms' );

		if ( ! empty( $post_id ) && ( $post = get_post( $post_id ) ) ) {
			$this->set_post_id( $post_id );
		}

		$this->set_is_editor_preview( $args['is_editor_preview'] );
		$this->set_date_created( sab_string_to_datetime( 'now' ) );
	}

	public function get_item_preview_meta( $item_type, $item = false ) {
		return array();
	}

	public function get_preview_meta() {
		return array();
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

	public function get_content() {
		$post = $this->get_post();

		if ( ! $post || empty( $post->post_content ) ) {
			return '<h1>Default Content</h1>';
		}

		return parent::get_content();
	}

	public function save() {
		$this->apply_changes();

		return false;
	}
}