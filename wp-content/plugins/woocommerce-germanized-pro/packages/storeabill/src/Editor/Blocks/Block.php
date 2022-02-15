<?php
/**
 * Abstract block class.
 *
 * @package WooCommerce/Blocks
 */

namespace Vendidero\StoreaBill\Editor\Blocks;

use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\Editor\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * AbstractBlock class.
 */
abstract class Block {

	/**
	 * Block namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'storeabill';

	/**
	 * Block namespace.
	 *
	 * @var string
	 */
	protected $block_name = '';

	/**
	 * Registers the block type with WordPress.
	 */
	public function register_type() {
		register_block_type(
			$this->namespace . '/' . $this->block_name,
			array(
				'editor_script' => 'sab-' . $this->block_name,
				'editor_style'  => 'sab-block-editor',
				'style'         => 'sab-block-style',
				'supports'      => array(),
			)
		);
	}

	public function get_name() {
		return $this->block_name;
	}

	public function get_type() {
		return \WP_Block_Type_Registry::get_instance()->get_registered( $this->namespace . '/' . $this->block_name );
	}

	public function parse_block( $block ) {
		return $block;
	}

	public function supports_document_type( $type ) {
		return in_array( $type, $this->get_supported_document_types() );
	}

	public function get_supported_document_types() {
		return sab_get_document_types();
	}

	public function get_available_shortcodes() {
		return array();
	}

	public function register_script() {
		Helper::register_script(
			'sab-' . $this->get_name(),
			Package::get_url() . '/build/editor/' . sanitize_key( $this->get_name() ) . '.js',
			array( 'sab-settings', 'sab-blocks', 'sab-vendors', 'sab-format-types' )
		);
	}
}
