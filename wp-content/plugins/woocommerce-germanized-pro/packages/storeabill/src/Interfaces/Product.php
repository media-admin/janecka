<?php

namespace Vendidero\StoreaBill\Interfaces;

use Vendidero\StoreaBill\Document\Attribute;

/**
 * Product Interface
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product class.
 */
interface Product extends Reference {

	/**
	 * Return the unique identifier for the order
	 *
	 * @return mixed
	 */
	public function get_id();

	public function get_name();

	public function get_sku();

	public function get_type();

	public function is_type( $type );

	public function is_virtual();

	public function is_service();

	public function get_parent_id();

	public function get_parent();

	/**
	 * @param $slug
	 *
	 * @return Attribute
	 */
	public function get_attribute_by_slug( $slug );

	/**
	 * @param array $custom_attribute_slugs
	 * @param array $existing_slugs
	 *
	 * @return Attribute[]
	 */
	public function get_additional_attributes( $custom_attribute_slugs, $existing_slugs = array() );

	public function get_image_url( $size = '', $placeholder = false );
}
