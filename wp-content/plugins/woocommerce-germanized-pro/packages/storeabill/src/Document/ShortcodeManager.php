<?php

namespace Vendidero\StoreaBill\Document;
use Vendidero\StoreaBill\Interfaces\ShortcodeHandleable;

defined( 'ABSPATH' ) || exit;

class ShortcodeManager {

	protected static $_instance = null;

	protected $handlers = array();

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
		/**
		 * Destroy existing handlers before setting up a handler for a specific document type.
		 */
		$this->destroy();

		$handler_class = $this->get_handler_class( $document_type );

		if ( class_exists( $handler_class ) ) {
			$handler = new $handler_class();

			if ( $handler->supports( $document_type ) ) {
				$handler->setup();
			}

			$this->handlers[ $document_type ] = $handler;
		}
	}

	/**
	 * @param $document_type
	 *
	 * @return false|ShortcodeHandleable
	 */
	public function get_handler( $document_type ) {
		return isset( $this->handlers[ $document_type ] ) ? $this->handlers[ $document_type ] : false;
	}

	/**
	 * @return ShortcodeHandleable[]
	 */
	public function get_handlers() {
		return $this->handlers;
	}

	protected function get_handler_class( $document_type ) {
		$fallback_handler = '\Vendidero\StoreaBill\Document\Shortcodes';
		$handler          = $fallback_handler;

		$handlers = array(
			'invoice'              => '\Vendidero\StoreaBill\Invoice\Shortcodes',
			'invoice_cancellation' => '\Vendidero\StoreaBill\Invoice\Shortcodes',
		);

		if ( array_key_exists( $document_type, $handlers ) ) {
			$handler = $handlers[ $document_type ];
		}

		$handler = apply_filters( "storeabill_{$document_type}_shortcode_handler_classname", $handler, $document_type );

		return $handler;
	}

	public function remove( $document_type ) {
		if ( $handler = $this->get_handler( $document_type ) ) {
			if ( is_a( $handler, '\Vendidero\StoreaBill\Interfaces\ShortcodeHandleable' ) ) {
				$handler->destroy();
			}
		}
	}

	public function destroy() {
		foreach( $this->get_handlers() as $handler ) {
			$handler->destroy();
		}
	}
}