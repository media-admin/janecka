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
interface ShortcodeHandleable {

	public function destroy();

	public function setup();

	public function get_shortcodes();

	public function supports( $document_type );
}
