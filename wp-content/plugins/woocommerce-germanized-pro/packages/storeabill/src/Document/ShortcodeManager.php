<?php

namespace Vendidero\StoreaBill\Document;
use WC_Object_Query;
use WC_Data_Store;
use WP_Meta_Query;
use WP_Date_Query;
use wpdb;

defined( 'ABSPATH' ) || exit;

class ShortcodeManager {

	protected static $_instance = null;

	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initializes Shortcodes
	 */
	public function __construct() {}

	public function setup( $document_type ) {
		foreach( $this->get_handlers( $document_type ) as $handler_class ) {
			if ( class_exists( $handler_class ) ) {
				$handler = new $handler_class();

				if ( is_a( $handler, '\Vendidero\StoreaBill\Interfaces\ShortcodeHandleable' ) ) {
					$handler->destroy();

					if ( $handler->supports( $document_type ) ) {
						$handler->setup();
					}
				}
			}
		}
	}

	protected function get_handlers( $document_type ) {
		$handlers = array(
			'\Vendidero\StoreaBill\Document\Shortcodes',
			'\Vendidero\StoreaBill\Invoice\Shortcodes',
		);

		$handlers = apply_filters( "storeabill_{$document_type}_shortcode_handlers", $handlers );

		return $handlers;
	}

	public function remove( $document_type ) {
		foreach( $this->get_handlers( $document_type ) as $handler_class ) {
			if ( class_exists( $handler_class ) ) {
				$handler = new $handler_class();

				if ( is_a( $handler, '\Vendidero\StoreaBill\Interfaces\ShortcodeHandleable' ) ) {
					$handler->destroy();
				}
			}
		}
	}
}