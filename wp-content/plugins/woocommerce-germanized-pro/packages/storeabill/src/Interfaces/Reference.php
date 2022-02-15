<?php

namespace Vendidero\StoreaBill\Interfaces;

/**
 * Order Interface
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order class.
 */
interface Reference {

	/**
	 * Return the unique identifier for the order
	 *
	 * @return mixed
	 */
	public function get_id();

	/**
	 * Returns the reference type e.g. woocommerce.
	 *
	 * @return string
	 */
	public function get_reference_type();

	public function is_callable( $method );

	public function get_meta( $key, $single = true, $context = 'view' );

	public function get_object();
}
