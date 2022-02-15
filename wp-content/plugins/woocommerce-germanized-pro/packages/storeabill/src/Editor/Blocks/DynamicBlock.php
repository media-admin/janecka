<?php
/**
 * Abstract dynamic block class.
 */
namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Document;
use Vendidero\StoreaBill\Exceptions\DocumentRenderException;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractDynamicBlock class.
 */
abstract class DynamicBlock extends Block {

	/**
	 * Attributes.
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * InnerBlocks content.
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * Registers the block type with WordPress.
	 */
	public function register_type() {
		register_block_type(
			$this->namespace . '/' . $this->get_name(),
			array(
				'render_callback' => array( $this, 'render' ),
				'editor_script'   => 'sab-' . $this->get_name(),
				'editor_style'    => 'sab-block-editor',
				'style'           => 'sab-block-style',
				'attributes'      => $this->get_attributes(),
				'supports'        => array(),
			)
		);
	}

	protected function is_render_api_request() {
		return WC()->is_rest_api_request() ? true : false;
	}

	/**
	 * Maybe setup the document.
	 */
	protected function maybe_setup_document() {
		if ( ! isset( $GLOBALS['document'] ) ) {
			global $post;

			if ( $post && 'document_template' === $post->post_type && ( $template = sab_get_document_template( $post->ID ) ) ) {
				$type = $template->get_document_type();

				if ( $preview = sab_get_document_preview( $type ) ) {
					$preview->set_template( $template );

					Package::setup_document( $preview );
				}
			}
		}
	}

	/**
	 * Setup document item.
	 */
	protected function maybe_setup_document_item() {
		self::maybe_setup_document();

		if ( ! isset( $GLOBALS['document_item'] ) && isset( $GLOBALS['document'] ) ) {
			/**
			 * @var Document\Document $document
			 */
			$document = $GLOBALS['document'];

			/**
			 * For previews.
			 */
			$items = $document->get_items( $document->get_line_item_types() );

			if ( ! empty( $items ) ) {
				$GLOBALS['document_item'] = array_values( $items )[0];
			}
		}
	}

	/**
	 * Include and render a dynamic block.
	 *
	 * @param array  $attributes Block attributes. Default empty array.
	 * @param string $content    Block content. Default empty string.
	 * @return string Rendered block type output.
	 */
	abstract public function render( $attributes = array(), $content = '' );

	public function pre_render( $content, $block ) {
		return $content;
	}

	protected function wrap( $output, $attributes ) {
		return $output;
	}

	/**
	 * Get block attributes.
	 *
	 * @return array
	 */
	protected function get_attributes() {
		return array();
	}

	/**
	 * Get the block's attributes.
	 *
	 * @param array $attributes Block attributes. Default empty array.
	 * @return array  Block attributes merged with defaults.
	 */
	protected function parse_attributes( $attributes ) {
		$defaults = array();

		foreach( $this->get_attributes() as $attribute => $schema ) {
			$defaults[ $attribute ] = isset( $schema['default'] ) ? $schema['default'] : '';
		}

		return wp_parse_args( $attributes, $defaults );
	}

	/**
	 * Get the schema for the alignment property.
	 *
	 * @return array Property definition for align.
	 */
	protected function get_schema_align( $default = '' ) {
		return array(
			'type'    => 'string',
			'enum'    => array( 'left', 'center', 'right', 'wide', 'full' ),
			'default' => $default,
		);
	}

	/**
	 * Get the schema for the alignment property.
	 *
	 * @return array Property definition for align.
	 */
	protected function get_schema_vertical_align( $default = '' ) {
		return array(
			'type'    => 'string',
			'enum'    => array( 'top', 'center', 'bottom' ),
			'default' => $default,
		);
	}

	/**
	 * Get the schema for a boolean value.
	 *
	 * @param  string $default  The default value.
	 * @return array Property definition.
	 */
	protected function get_schema_boolean( $default = true ) {
		return array(
			'type'    => 'boolean',
			'default' => $default,
		);
	}

	/**
	 * Get the schema for a numeric value.
	 *
	 * @param  string $default  The default value.
	 * @return array Property definition.
	 */
	protected function get_schema_number( $default ) {
		return array(
			'type'    => 'number',
			'default' => $default,
		);
	}

	/**
	 * Get the schema for a string value.
	 *
	 * @param  string $default  The default value.
	 * @return array Property definition.
	 */
	protected function get_schema_string( $default = '' ) {
		return array(
			'type'    => 'string',
			'default' => $default,
		);
	}
}
