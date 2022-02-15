<?php

namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Editor\Helper;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * AllProducts class.
 */
class PlaceholderBlock extends DynamicBlock {

	protected $block_title = '';

	protected $render_callback = null;

	protected $block_name = '';

	public function __construct( $block_name, $args ) {
		$this->block_name = $block_name;

		$args = wp_parse_args( $args, array(
			'render_callback' => null,
			'block_title'     => $this->block_name,
		) );

		$this->block_title     = $args['block_title'];
		$this->render_callback = $args['render_callback'];
	}

	public function get_block_title() {
		return $this->block_title;
	}

	public function get_render_callback() {
		return $this->render_callback;
	}

	/**
	 * Registers the block type with WordPress.
	 */
	public function register_type() {
		register_block_type(
			$this->namespace . '/' . $this->get_name(),
			array(
				'render_callback' => array( $this, 'render' ),
				'editor_script'   => 'sab-dynamic-content',
				'editor_style'    => 'sab-block-editor',
				'style'           => 'sab-block-style',
				'attributes'      => $this->get_attributes(),
				'supports'        => array(),
			)
		);
	}

	public function register_script() {
		Helper::register_script(
			'sab-dynamic-content',
			Package::get_url() . '/build/editor/dynamic-content.js',
			array( 'sab-settings', 'sab-blocks', 'sab-vendors', 'sab-format-types' )
		);
	}

	public function get_attributes() {
		return array(
			'textSize'  => $this->get_schema_string( sab_get_document_default_font_size() ),
			'fontSize'  => $this->get_schema_string(),
			'align'     => $this->get_schema_align(),
			'blockName' => $this->get_schema_string( $this->get_name() ),
			'textColor' => $this->get_schema_string(),
		);
	}

	/**
	 * Append frontend scripts when rendering the Product Categories List block.
	 *
	 * @param array   $attributes Block attributes. Default empty array.
	 * @param string  $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	public function render( $attributes = array(), $content = '' ) {
		self::maybe_setup_document();

		if ( ! isset( $GLOBALS['document'] ) ) {
			return $content;
		}

		$this->attributes = $this->parse_attributes( $attributes );
		$this->content    = $content;

		if ( is_callable( $this->render_callback ) ) {
			$this->content = call_user_func_array( $this->render_callback, array( 'block' => $this ) );
		}

		return $this->wrap( $this->content, $this->attributes );
	}

	protected function wrap( $output, $attributes ) {
		if ( $this->is_render_api_request() ) {
			return $output;
		}

		$classes = sab_generate_block_classes( $attributes );
		$styles  = sab_generate_block_styles( $attributes );

		return '<div class="' . sab_print_html_classes( $classes, false ) . '" style="' . sab_print_styles( $styles, false ) . '">' . $output . '</div>';
	}
}
