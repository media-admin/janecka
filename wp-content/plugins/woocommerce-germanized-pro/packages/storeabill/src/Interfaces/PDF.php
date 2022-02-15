<?php

namespace Vendidero\StoreaBill\Interfaces;

use Vendidero\StoreaBill\Exceptions\DocumentRenderException;

/**
 * PDF Library Interface
 *
 * @package  Germanized/StoreaBill/Interfaces
 * @version  1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PDF Library.
 */
interface PDF {

	public static function supports( $feature );

	public static function get_version();

	public function __construct( $args );

	public function set_options( $options = array() );

	public function get_option( $key );

	public function set_content( $html );

	public function set_header( $html );

	public function set_wrapper_before( $html );

	public function set_wrapper_after( $html );

	public function set_header_first_page( $html );

	public function set_footer( $html );

	public function set_footer_first_page( $html );

	public function set_template( $template );

	/**
	 * @param string $filename
	 *
	 * @throws DocumentRenderException
	 */
	public function output( $filename );

	/**
	 * Stream the document.
	 *
	 * @return mixed
	 *
	 * @throws DocumentRenderException
	 */
	public function stream();
}