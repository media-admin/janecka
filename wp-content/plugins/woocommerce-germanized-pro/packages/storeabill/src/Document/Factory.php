<?php

namespace Vendidero\StoreaBill\Document;

use Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Document factory class
 */
class Factory {

	/**
	 * @param int $document_id Document ID if availabe. Leave empty to create new document.
	 * @param string $document_type The document type in case a new document is to be created.
	 *
	 * @return bool|Document
	 */
	public static function get_document( $document_id = 0, $document_type = '' ) {
		$document_id = self::get_document_id( $document_id );

		if ( false === $document_id ) {
			return false;
		}

		if ( is_numeric( $document_id ) && ! empty( $document_id ) ) {
			$document_type = self::get_document_type( $document_id );
		}

		$document_type_data = sab_get_document_type( $document_type );

		if ( $document_type_data ) {
			$classname = $document_type_data->class_name;
		} else {
			$classname = false;
		}

		// Filter classname so that the class can be overridden if extended.
		$classname = apply_filters( 'storeabill_document_classname', $classname, $document_type, $document_id );

		if ( ! class_exists( $classname ) ) {
			return false;
		}

		try {
			return new $classname( $document_id );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $document_id ) );
			return false;
		}
	}

	public static function get_document_type( $document_id ) {
		global $wpdb;

		$type = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT document_type FROM {$wpdb->storeabill_documents} WHERE document_id = %d LIMIT 1",
				$document_id
			)
		);

		return ! empty( $type ) ? $type[0] : false;
	}

	/**
	 * Get document item.
	 *
	 * @param int $item_id Document item ID to get. Leave empty to create a new one.
	 * @param string $item_type The item type necessary to create a new item (e.g. accounting_product).
	 *
	 * @return Item|false if not found
	 */
	public static function get_document_item( $item_id = 0, $item_type = '' ) {
		$item_id = self::get_document_item_id( $item_id );

		if ( false === $item_id ) {
			return false;
		}

		if ( is_numeric( $item_id ) && ! empty( $item_id ) ) {
			$item_type = self::get_document_item_type( $item_id );
		}

		if ( ! empty( $item_type ) ) {
			$classname = false;

			switch ( $item_type ) {
				case 'accounting_product':
					$classname = '\Vendidero\StoreaBill\Invoice\ProductItem';
					break;
				case 'accounting_fee':
					$classname = '\Vendidero\StoreaBill\Invoice\FeeItem';
					break;
				case 'accounting_shipping':
					$classname = '\Vendidero\StoreaBill\Invoice\ShippingItem';
					break;
				case 'accounting_tax':
					$classname = '\Vendidero\StoreaBill\Invoice\TaxItem';
					break;
			}

			$classname = apply_filters( 'storeabill_document_item_classname', $classname, $item_type, $item_id );

			if ( $classname && class_exists( $classname ) ) {
				try {
					return new $classname( $item_id );
				} catch ( Exception $e ) {
					wc_caught_exception( $e, __FUNCTION__, array( $item_id ) );
					return false;
				}
			}
		}

		return false;
	}

	public static function get_document_item_type( $item_id ) {
		global $wpdb;

		$type = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT document_item_type FROM {$wpdb->storeabill_document_items} WHERE document_item_id = %d LIMIT 1",
				$item_id
			)
		);

		return ( ! empty( $type ) ? $type[0] : false );
	}

	/**
	 * @param int $notice_id Notice ID if availabe. Leave empty to create new notice.
	 * @param string $notice_type The document notice type in case a new notice is to be created.
	 *
	 * @return bool|Notice
	 */
	public static function get_document_notice( $notice_id = 0, $notice_type = '' ) {
		$notice_id = self::get_document_notice_id( $notice_id );

		if ( false === $notice_id ) {
			return false;
		}

		if ( is_numeric( $notice_id ) && ! empty( $notice_id ) ) {
			$notice_type = self::get_document_notice_type( $notice_id );
		}

		$classname = apply_filters( 'storeabill_document_notice_classname', '\Vendidero\StoreaBill\Document\Notice', $notice_type, $notice_id );

		if ( $classname && class_exists( $classname ) ) {
			try {
				$notice = new $classname( $notice_id );

				if ( empty( $notice_id ) ) {
					$notice->set_type( $notice_type );
				}

				return $notice;
			} catch ( Exception $e ) {
				wc_caught_exception( $e, __FUNCTION__, array( $notice_id ) );
				return false;
			}
		}

		return false;
	}

	public static function get_document_notice_type( $notice_id ) {
		global $wpdb;

		$type = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT document_notice_type FROM {$wpdb->storeabill_document_notices} WHERE document_notice_id = %d LIMIT 1",
				$notice_id
			)
		);

		return ! empty( $type ) ? $type[0] : false;
	}

	/**
	 * @param int    $template_id   Template ID if availabe. Leave empty to create new template.
	 * @param string $document_type The document type in case a new template is to be created.
	 *
	 * @return bool|DefaultTemplate|FirstPageTemplate
	 */
	public static function get_document_template( $template_id = 0, $document_type = '', $first_page = false ) {
		$template_id = self::get_document_template_id( $template_id );

		if ( false === $template_id ) {
			return false;
		}

		$default_classname = '\Vendidero\StoreaBill\Document\DefaultTemplate';

		if ( ! empty( $template_id ) ) {
			$post = get_post( $template_id );

			if ( $post && $post->post_parent > 0 ) {

				if ( ! $first_page ) {
					$template_id = $post->post_parent;
				} else {
					$default_classname = '\Vendidero\StoreaBill\Document\FirstPageTemplate';
				}
			}
		}

		if ( empty( $template_id ) && $first_page ) {
			$default_classname = '\Vendidero\StoreaBill\Document\FirstPageTemplate';
		}

		$classname = apply_filters( 'storeabill_document_template_classname', $default_classname, $template_id );

		if ( $classname && class_exists( $classname ) ) {
			try {
				$template = new $classname( $template_id );

				if ( empty( $template_id ) ) {
					$template->set_document_type( $document_type );
				}

				return $template;
			} catch ( Exception $e ) {
				wc_caught_exception( $e, __FUNCTION__, array( $classname ) );
				return false;
			}
		}

		return false;
	}

	/**
	 * Get the document id depending on what was passed.
	 *
	 * @since 1.0.0
	 * @param  mixed $document Document data to convert to an ID.
	 * @return int|bool false on failure
	 */
	public static function get_document_id( $document ) {
		if ( is_numeric( $document ) ) {
			return $document;
		} elseif ( $document instanceof Document ) {
			return $document->get_id();
		} elseif ( ! empty( $document->document_id ) ) {
			return $document->document_id;
		} else {
			return false;
		}
	}

	/**
	 * Get the item id depending on what was passed.
	 *
	 * @since 1.0.0
	 * @param  mixed $document Item data to convert to an ID.
	 * @return int|bool false on failure
	 */
	public static function get_document_item_id( $item ) {
		if ( is_numeric( $item ) ) {
			return $item;
		} elseif ( $item instanceof Item ) {
			return $item->get_id();
		} elseif ( ! empty( $item->document_item_id ) ) {
			return $item->document_item_id;
		} else {
			return false;
		}
	}

	/**
	 * Get the notice id depending on what was passed.
	 *
	 * @since 1.0.0
	 * @param  mixed $notice Notice data to convert to an ID.
	 * @return int|bool false on failure
	 */
	public static function get_document_notice_id( $notice ) {
		if ( is_numeric( $notice ) ) {
			return $notice;
		} elseif ( $notice instanceof Notice ) {
			return $notice->get_id();
		} elseif ( ! empty( $notice->document_notice_id ) ) {
			return $notice->document_notice_id;
		} else {
			return false;
		}
	}

	/**
	 * Get the template id depending on what was passed.
	 *
	 * @since 1.0.0
	 * @param  mixed $template Template data to convert to an ID.
	 * @return int|bool false on failure
	 */
	public static function get_document_template_id( $template ) {
		if ( is_numeric( $template ) ) {
			return $template;
		} elseif ( $template instanceof Template ) {
			return $template->get_id();
		} elseif ( ! empty( $template->ID ) ) {
			return $template->ID;
		} else {
			return false;
		}
	}
}
