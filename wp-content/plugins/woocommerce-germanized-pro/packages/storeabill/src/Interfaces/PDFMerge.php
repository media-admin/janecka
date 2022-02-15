<?php

namespace Vendidero\StoreaBill\Interfaces;

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
interface PDFMerge {

	public function __construct();

	public function add( $path, $pages = array(), $width = 210 );

	public function output( $filename );

	public function stream();
}