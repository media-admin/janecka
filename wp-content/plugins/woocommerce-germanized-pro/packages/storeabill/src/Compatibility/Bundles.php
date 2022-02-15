<?php

namespace Vendidero\StoreaBill\Compatibility;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Interfaces\Compatibility;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Item;

defined( 'ABSPATH' ) || exit;

class Bundles implements Compatibility {

	public static function is_active() {
		return class_exists( 'WC_Bundles' );
	}

	public static function init() {
		add_action( 'storeabill_before_render_document', array( __CLASS__, 'register_hooks' ), 50 );
		add_action( 'storeabill_after_render_document', array( __CLASS__, 'unregister_hooks' ), 50 );
	}

	protected static function get_bundle_document_types() {
		return apply_filters( 'storeabill_bundles_compatibility_document_types', array(
			'invoice',
			'invoice_cancellation',
		) );
	}

	public static function unregister_hooks() {
		foreach( self::get_bundle_document_types() as $document_type ) {
			remove_filter( "storeabill_{$document_type}_item_table_items", array( __CLASS__, 'hide_bundled_items' ), 10 );
			remove_filter( "storeabill_{$document_type}_item_table_column_classes", array( __CLASS__, 'add_bundle_classes' ), 10 );
			remove_action( "storeabill_{$document_type}_item_table_after_row", array( __CLASS__, 'add_bundle_items' ), 10 );
			remove_action( "storeabill_{$document_type}_item_table_before_row", array( __CLASS__, 'maybe_hide_bundle_container_prices' ), 10 );

			remove_action( "storeabill_{$document_type}_hide_email_details", array( __CLASS__, 'maybe_hide_details' ), 10 );
		}
	}

	public static function register_hooks( $document ) {
		wp_cache_delete( 'sab_bundle_items_map_' . $document->get_id(), 'sab-bundle-items' );

		foreach( self::get_bundle_document_types() as $document_type ) {
			add_filter( "storeabill_{$document_type}_item_table_items", array( __CLASS__, 'hide_bundled_items' ), 10, 2 );
			add_filter( "storeabill_{$document_type}_item_table_column_classes", array( __CLASS__, 'add_bundle_classes' ), 10, 3 );
			add_action( "storeabill_{$document_type}_item_table_after_row", array( __CLASS__, 'add_bundle_items' ), 10, 5 );
			add_action( "storeabill_{$document_type}_item_table_before_row", array( __CLASS__, 'maybe_hide_bundle_container_prices' ), 10, 5 );

			add_action( "storeabill_{$document_type}_hide_email_details", array( __CLASS__, 'maybe_hide_details' ), 10, 2 );
		}
	}

	/**
	 * Hide email document items table in case the invoice contains bundles.
	 *
	 * @param $hide_details
	 * @param Invoice $document
	 */
	public static function maybe_hide_details( $hide_details, $document ) {
		if ( self::document_has_bundle( $document ) ) {
			$hide_details = true;
		}

		return $hide_details;
	}

	/**
	 * @param Document $document
	 */
	protected static function document_has_bundle( $document ) {
		$bundle_items = self::get_bundle_items_map( $document );

		return ( ! empty( $bundle_items ) ? true : false );
	}

	/**
     * @param array $classes
	 * @param Item $item
	 * @param Invoice $document
	 */
	public static function add_bundle_classes( $classes, $item, $document ) {
		$bundle_items  = self::get_bundle_items_map( $document );
		$bundled_items = self::get_bundled_items_ids( $document );

		if ( array_key_exists( $item->get_id(), $bundle_items ) ) {
			$classes[] = 'sab-bundle-container-item';
		} elseif( in_array( $item->get_id(), $bundled_items ) ) {
			$classes[] = 'sab-bundle-child-item';
		}

		return $classes;
    }

	/**
	 * @param Item $item
	 * @param Invoice $document
	 * @param array $columns
	 * @param integer $count
	 * @param integer $item_size
	 */
    public static function maybe_hide_bundle_container_prices( $item, $document, $columns, $count, $item_size ) {
	    $bundle_items = self::get_bundle_items_map( $document );

	    if ( array_key_exists( $item->get_id(), $bundle_items ) ) {
		    /**
		     * Bundled prices with zero total
		     */
		    $hide_zero_prices = apply_filters( "storeabill_{$document->get_type()}_hide_container_bundle_zero_prices", true, $item, $document );

		    if ( $item->get_total() == 0 && $hide_zero_prices ) {
			    add_filter( 'storeabill_formatted_price', array( __CLASS__, 'hide_price' ), 10 );
			    add_filter( 'storeabill_formatted_tax_rate_percentage', array( __CLASS__, 'hide_tax_rate' ), 10 );
			    add_filter( 'storeabill_formatted_tax_rate_percentage_html', array( __CLASS__, 'hide_tax_rate' ), 10 );
		    }
	    }
    }

	/**
	 * @param \Vendidero\StoreaBill\Document\Item $item
	 */
    protected static function get_document_order_item( $item ) {
	    if ( $ref_item = $item->get_reference() ) {
	    	if ( is_callable( array( $ref_item, 'get_order_item' ) ) ) {
			    $order_item = $ref_item->get_order_item();

			    if ( is_a( $order_item, 'WC_Order_Item_Product' ) ) {
					return $order_item;
			    }
		    }
	    }

	    return false;
    }

	/**
	 * @param Item $item
	 * @param Invoice $document
     * @param array $columns
     * @param integer $count
     * @param integer $item_size
	 */
	public static function add_bundle_items( $item, $document, $columns, $count, $item_size ) {
		$bundle_items = self::get_bundle_items_map( $document );

		if ( array_key_exists( $item->get_id(), $bundle_items ) ) {
			/**
			 * Maybe remove filters set within maybe_hide_bundle_container_prices
			 */
			remove_filter( 'storeabill_formatted_price', array( __CLASS__, 'hide_price' ), 10 );
			remove_filter( 'storeabill_formatted_tax_rate_percentage', array( __CLASS__, 'hide_tax_rate' ), 10 );
			remove_filter( 'storeabill_formatted_tax_rate_percentage_html', array( __CLASS__, 'hide_tax_rate' ), 10 );

			$item_keys = $bundle_items[ $item->get_id() ];

			if ( ! empty( $item_keys ) ) {
				foreach ( $item_keys as $item_key ) {
					if ( $bundled_item = $document->get_item( $item_key ) ) {
						/**
						 * Bundled prices with zero total
						 */
						$hide_zero_prices = apply_filters( "storeabill_{$document->get_type()}_hide_bundled_zero_prices", true, $bundled_item, $document );

						if ( $bundled_item->get_total() == 0 && $hide_zero_prices ) {
							add_filter( 'storeabill_formatted_price', array( __CLASS__, 'hide_price' ), 10 );
							add_filter( 'storeabill_formatted_tax_rate_percentage', array( __CLASS__, 'hide_tax_rate' ), 10 );
							add_filter( 'storeabill_formatted_tax_rate_percentage_html', array( __CLASS__, 'hide_tax_rate' ), 10 );
						}

						sab_get_template( 'blocks/item-table/row.php', array(
							'document'  => $document,
							'count'     => $count,
							'item'      => $bundled_item,
							'item_size' => $item_size,
							'columns'   => $columns
						) );

						/**
						 * Bundled prices with zero total
						 */
						if ( $bundled_item->get_total() == 0 && $hide_zero_prices ) {
							remove_filter( 'storeabill_formatted_price', array( __CLASS__, 'hide_price' ), 10 );
							remove_filter( 'storeabill_formatted_tax_rate_percentage', array( __CLASS__, 'hide_tax_rate' ), 10 );
							remove_filter( 'storeabill_formatted_tax_rate_percentage_html', array( __CLASS__, 'hide_tax_rate' ), 10 );
						}
					}
				}
			}
		}
	}

	public static function hide_tax_rate( $tax_rate ) {
		return '-';
	}

	public static function hide_price( $price ) {
		return '-';
	}

	/**
	 * @param Document $document
	 */
	protected static function get_document_order( $document ) {
		if ( is_callable( array( $document, 'get_order' ) ) ) {
			if ( $order = $document->get_order() ) {
				if ( is_a( $order, '\Vendidero\StoreaBill\Interfaces\Order' ) ) {
					$order = $order->get_object();
				}

				if ( is_a( $order, 'WC_Order' ) ) {
					return $order;
				}
			}
		}

		return false;
	}

	/**
	 * @param $document
	 *
	 * @return string[]
	 */
	protected static function get_bundled_items_ids( $document ) {
		$bundle_item_map = self::get_bundle_items_map( $document );
		$bundle_items    = array();

		foreach( $bundle_item_map as $bundled_item_ids ) {
			$bundle_items = array_merge( $bundle_items, $bundled_item_ids );
		}

		return $bundle_items;
	}

	/**
	 * @param $document
	 *
	 * @return mixed
	 */
	protected static function get_bundle_items_map( $document ) {
		$items_map = wp_cache_get( 'sab_bundle_items_map_' . $document->get_id(), 'sab-bundle-items' );

		if ( false === $items_map || ! is_array( $items_map ) ) {
			$items_map = array();

			if ( function_exists( 'wc_pb_is_bundled_order_item' ) && function_exists( 'wc_pb_get_bundled_order_item_container' ) ) {
				if ( $order = $document->get_order() ) {
					$bundled_items = array();
					$items_left    = array();

					foreach( $document->get_items( 'product' ) as $key => $item ) {
						if ( $order_item = self::get_document_order_item( $item ) ) {
							if ( wc_pb_is_bundled_order_item( $order_item, $order ) ) {
								$bundled_items[ $order_item->get_id() ] = array(
									'order_item' => $order_item,
									'item_key'   => $key,
									'item'       => $item,
								);
							} else {
								$items_left[ $order_item->get_id() ] = array(
									'order_item' => $order_item,
									'item_key'   => $key,
									'item'       => $item,
								);
							}
						}
					}

					/**
					 * Map each bundled item to it's parent in case the parent/container item does actually exist.
					 * If no container item can be found - do not treat this item as a bundle.
					 */
					if ( ! empty( $bundled_items ) ) {
						foreach( $bundled_items as $order_item_id => $bundled_data ) {
							if ( $parent_order_item_id = wc_pb_get_bundled_order_item_container( $bundled_data['order_item'], $order, true ) ) {
								if ( is_numeric( $parent_order_item_id ) && array_key_exists( $parent_order_item_id, $items_left ) ) {
									$document_item_id = $items_left[ $parent_order_item_id ]['item_key'];

									if ( ! array_key_exists( $document_item_id, $items_map ) ) {
										$items_map[ $document_item_id ] = array();
									}

									$items_map[ $document_item_id ][] = $bundled_data['item_key'];
								}
							}
						}
					}
				}
			}

			wp_cache_set( 'sab_bundle_items_map_' . $document->get_id(), $items_map, 'sab-bundle-items' );
		}

		return $items_map;
	}

	/**
	 * Remove bundled items from table.
	 *
	 * @param Item[] $items
	 * @param $document
	 *
	 * @return mixed
	 */
	public static function hide_bundled_items( $items, $document ) {
		$bundled_items = self::get_bundled_items_ids( $document );

		foreach( $items as $key => $item ) {
			if ( in_array( $key, $bundled_items ) ) {
				unset( $items[ $key ] );
			}
		}

		return $items;
	}
}