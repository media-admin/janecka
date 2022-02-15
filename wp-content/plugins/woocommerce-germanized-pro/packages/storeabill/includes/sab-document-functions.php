<?php
/**
 * StoreaBill Document Functions
 *
 * Document related functions available on both the front-end and admin.
 *
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use \Vendidero\StoreaBill\Document\Document;
use \Vendidero\StoreaBill\Document\Factory;

/**
 * Get all available document statuses.
 *
 * @return array
 */
function sab_get_document_statuses( $document_type = '', $include_hidden = true ) {

	$document_statuses = array(
		'draft'      => _x( 'Draft', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'closed'     => _x( 'Closed', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'archived'   => _x( 'Archived', 'storeabill-core', 'woocommerce-germanized-pro' ),
	);

	if ( ! empty( $document_type ) ) {
		if ( $type_data = sab_get_document_type( $document_type ) ) {
			$document_statuses = (array) $type_data->statuses;

			if ( ! $include_hidden ) {
				$document_statuses = array_diff_key( $document_statuses, array_flip( $type_data->statuses_hidden ) );
			}
		}
	}

	/**
	 * Add or adjust available document statuses.
	 *
	 * @param array $statuses The available document statuses.
	 *
	 * @since 1.0.0
	 * @package Vendidero/StoreaBill
	 */
	return apply_filters( 'storeabill_document_statuses', $document_statuses, $document_type );
}

function sab_get_document_status_name( $status, $type = '' ) {
	$status_name = '';
	$statuses    = sab_get_document_statuses( $type );

	if ( array_key_exists( $status, $statuses ) ) {
		$status_name = $statuses[ $status ];
	}

	/**
	 * Filter to adjust the document status name or title.
	 *
	 * @param string  $status_name The status name or title.
	 * @param integer $status The status slug.
	 *
	 * @since 1.0.0
	 * @package Vendidero/StoreaBill
	 */
	return apply_filters( 'storeabill_document_status_name', $status_name, $status );
}

/**
 * Get all available document notice types.
 *
 * @return array
 */
function sab_get_document_notice_types() {

	$document_notice_types = array(
		'info'       => _x( 'Info', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'error'      => _x( 'Error', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'manual'     => _x( 'Manual', 'storeabill-core', 'woocommerce-germanized-pro' ),
	);

	/**
	 * Add or adjust available document notice types.
	 *
	 * @param array $notice_types The available document notice types.
	 *
	 * @since 1.0.0
	 * @package Vendidero/StoreaBill
	 */
	return apply_filters( 'storeabill_document_notice_types', $document_notice_types );
}

function sab_get_document_item_type_title( $item_type ) {
	$mappings = array(
		'accounting_product'  => _x( 'Product', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'accounting_fee'      => _x( 'Fee', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'accounting_shipping' => _x( 'Shipping', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'accounting_tax'      => _x( 'Tax', 'storeabill-core', 'woocommerce-germanized-pro' )
	);

	$title = $item_type;

	if ( array_key_exists( $item_type, $mappings ) ) {
		$title = $mappings[ $item_type ];
	}

	return apply_filters( 'storeabill_item_type_title', $title, $item_type );
}

/**
 * Register document type. Do not use before init.
 *
 * Wrapper for register document type.
 *
 * @since  1.0
 *
 * @param  string $type Document type. (max. 20 characters, can not contain capital letters or spaces).
 * @param  array  $args An array of arguments.
 *
 * @return bool Success or failure
 */
function sab_register_document_type( $type, $args = array() ) {
	global $sab_document_types;

	if ( ! is_array( $sab_document_types ) ) {
		$sab_document_types = array();
	}

	/**
	 * Filters the arguments for registering a document type.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $args          Array of arguments for registering a document type.
	 * @param string $document_type Document type key.
	 */
	$args = apply_filters( "storeabill_register_document_type_args", $args, $type );

	// Register for SAB usage.
	$document_type_args = array(
		'class_name'                => '\Vendidero\StoreaBill\Invoice\Simple',
		'exclude_from_count'        => false,
		'labels'                    => array(),
		'group'                     => 'others',
		'statuses'                  => sab_get_document_statuses(),
		'statuses_hidden'           => array(),
		'default_status'            => 'draft',
		'preview_class_name'        => '\Vendidero\StoreaBill\Invoice\SimplePreview',
		'admin_table_class_name'    => '\Vendidero\StoreaBill\Invoice\SimpleTable',
		'email_class_name'          => '\Vendidero\StoreaBill\Emails\Document',
		'admin_email_class_name'    => '\Vendidero\StoreaBill\Emails\DocumentAdmin',
		'default_template'          => 'default',
		'total_types'               => array(),
		'supports'                  => array(),
		'api_endpoint'              => '',
		'additional_blocks'         => array(),
		'default_line_item_types'   => array(),
		'available_line_item_types' => array(),
		'barcode_code_types'        => array(),
		'shortcodes'                => array(),
		'date_types'                => array(),
		'document_type'             => $type,
		'exporters'                 => array(
			'file' => '\Vendidero\StoreaBill\Document\FileExporter'
		),
	);

	/**
	 * Make sure to always include our default file exporter which is supported by every document type.
	 */
	if ( isset( $args['exporters'] ) && ! empty( $args['exporters'] ) ) {
		$args['exporters'] = array_replace_recursive( $args['exporters'], $document_type_args['exporters'] );
	}

	$args = array_intersect_key( $args, $document_type_args );
	$args = wp_parse_args( $args, $document_type_args );

	$args['labels'] = wp_parse_args( $args['labels'], array(
		'singular' => '',
		'plural'   => '',
	) );

	$args['shortcodes'] = wp_parse_args( $args['shortcodes'], array(
		'document'      => array(),
		'document_item' => array(),
		'setting'       => array(),
	) );

	$args['date_types'] = array_merge( array(
		'date' => _x( 'Date', 'storeabill-core', 'woocommerce-germanized-pro' )
	), $args['date_types'] );

	/**
	 * Default document shortcodes
	 */
	$args['shortcodes']['document'] = array_merge( array(
		array(
			'shortcode'        => 'document?data=formatted_number',
			'title'            => _x( 'Formatted document number', 'storeabill-core', 'woocommerce-germanized-pro' ),
		),
		array(
			'shortcode'        => 'document?data=number',
			'title'            => _x( 'Document number', 'storeabill-core', 'woocommerce-germanized-pro' ),
		),
		array(
			'shortcode'        => 'document?data=formatted_full_name',
			'title'            => _x( 'Recipient full name', 'storeabill-core', 'woocommerce-germanized-pro' ),
		),
		array(
			'shortcode'        => 'document?data=first_name',
			'title'            => _x( 'Recipient first name', 'storeabill-core', 'woocommerce-germanized-pro' ),
		),
		array(
			'shortcode'        => 'document?data=last_name',
			'title'            => _x( 'Recipient last name', 'storeabill-core', 'woocommerce-germanized-pro' ),
		),
		array(
			'shortcode'        => 'document?data=current_page_no',
			'title'            => _x( 'Current page number', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'headerFooterOnly' => true,
		),
		array(
			'shortcode'        => 'document?data=total_pages',
			'title'            => _x( 'Total pages', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'headerFooterOnly' => true,
		)
	), $args['shortcodes']['document'] );

	/**
	 * Default setting shortcodes
	 */
	$args['shortcodes']['setting'] = array_merge( array(
		array(
			'shortcode'        => 'setting?data=bank_account_holder',
			'title'            => _x( 'Bank account holder', 'storeabill-core', 'woocommerce-germanized-pro' ),
		),
		array(
			'shortcode'        => 'setting?data=bank_account_bank_name',
			'title'            => _x( 'Bank name', 'storeabill-core', 'woocommerce-germanized-pro' ),
		),
		array(
			'shortcode'        => 'setting?data=bank_account_iban',
			'title'            => _x( 'IBAN', 'storeabill-core', 'woocommerce-germanized-pro' ),
		),
		array(
			'shortcode'        => 'setting?data=bank_account_bic',
			'title'            => _x( 'BIC', 'storeabill-core', 'woocommerce-germanized-pro' ),
		),
	), $args['shortcodes']['setting'] );

	/**
	 * Default barcode code data
	 */
	$args['barcode_code_types'] = array_merge( array(
		'document?data=formatted_number' => _x( 'Formatted document number', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'document?data=number'           => _x( 'Document number', 'storeabill-core', 'woocommerce-germanized-pro' ),
	), $args['barcode_code_types'] );

	if ( 'accounting' === $args['group'] ) {

		$args['barcode_code_types'] = array_merge( array(
			'document?data=total' => _x( 'Total', 'storeabill-barcode-data', 'woocommerce-germanized-pro' ),
		), $args['barcode_code_types'] );

		$args['additional_blocks'] = array_merge( $args['additional_blocks'], array(
			'storeabill/reverse-charge-notice',
			'storeabill/third-country-notice',
			'storeabill/shipping-address'
		) );

		if ( empty( $args['default_line_item_types'] ) ) {
			$args['default_line_item_types'] = array( 'product' );
		}

		if ( empty( $args['available_line_item_types'] ) ) {
			$args['available_line_item_types'] = array( 'product', 'fee', 'shipping' );
		}

		$args['supports'] = array_values( array_merge( $args['supports'], array( 'items', 'totals', 'item_totals', 'discounts' ) ) );

		$defaults = array(
			'total' => array(
				'title' => _x( 'Total', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Total', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'subtotal' => array(
				'title' => _x( 'Total', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Total (Before discounts)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'discount' => array(
				'title' => _x( 'Discount %s', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Discount', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'discount_net' => array(
				'title' => _x( 'Discount %s', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Discount (net)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'additional_costs_discount' => array(
				'title' => _x( 'Discount', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Discount (None line items)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'additional_costs_discount_net' => array(
				'title' => _x( 'Discount', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Discount (None line items, net)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'shipping' => array(
				'title' => _x( 'Shipping', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Shipping', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'shipping_subtotal' => array(
				'title' => _x( 'Shipping', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Shipping (Before discounts)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'shipping_net' => array(
				'title' => _x( 'Shipping', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Shipping (net)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'shipping_subtotal_net' => array(
				'title' => _x( 'Shipping', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Shipping (Before discounts, net)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'fee' => array(
				'title' => _x( 'Fee total', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Fee total', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'fee_subtotal' => array(
				'title' => _x( 'Fee total', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Fee total (Before discounts)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'fee_net' => array(
				'title' => _x( 'Fee total', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Fee total (net)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'fee_subtotal_net' => array(
				'title' => _x( 'Fee total', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Fee total (Before discounts, net)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'fees' => array(
				'title' => _x( 'Fee: %s', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Fees', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'line_subtotal' => array(
				'title' => _x( 'Subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'line_subtotal_net' => array(
				'title' => _x( 'Subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Subtotal (net)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'line_subtotal_after' => array(
				'title' => _x( 'Subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Subtotal (After discounts)', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
			'line_subtotal_after_net' => array(
				'title' => _x( 'Subtotal', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Subtotal (After discounts, net)', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
			'taxes' => array(
				'title' => _x( 'Tax %s %%', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Taxes', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
			'shipping_taxes' => array(
				'title' => _x( 'Shipping Tax %s %%', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Shipping Taxes', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
			'fee_taxes' => array(
				'title' => _x( 'Fee Tax %s %%', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Fee Taxes', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
			'net' => array(
				'title' => _x( 'Net', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Net total', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
			'nets' => array(
				'title' => _x( 'Net %s %%', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Net totals per tax rate', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
			'subtotal_net' => array(
				'title'       => _x( 'Net', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'        => _x( 'Net total (Before discounts)', 'storeabill-core', 'woocommerce-germanized-pro' ),
			),
			'gross_tax_shares' => array(
				'title' => _x( 'Gross %s %%', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'  => _x( 'Gross total per tax rate', 'storeabill-core', 'woocommerce-germanized-pro' )
			),
		);

		if ( empty( $args['total_types'] ) ) {
			$args['total_types'] = $defaults;
		}
	}

	$sab_document_types[ $type ] = (object) apply_filters( 'storeabill_register_document_type_parsed_args', $args, $type );

	return true;
}

/**
 * Get document type data by type name.
 *
 * @param  string $type Document type name.
 * @return bool|stdclass Details about the document type.
 */
function sab_get_document_type( $type ) {
	global $sab_document_types;

	// Remove simple from type e.g. invoice_simple resolves to invoice
	if ( strpos( $type, '_simple' ) !== false ) {
		$type = str_replace( '_simple', '', $type );
	}

	if ( isset( $sab_document_types[ $type ] ) ) {
		return $sab_document_types[ $type ];
	}

	return false;
}

function sab_document_type_supports( $type, $supports ) {
	if ( $document_type = sab_get_document_type( $type ) ) {
		return in_array( $supports, $document_type->supports );
	}

	return false;
}

/**
 * Get all registered document types.
 *
 * @since  1.0
 * @param  string $for Optionally define what you are getting document types for so
 *                     only relevant types are returned.
 *                     e.g. for 'count'.
 * @param string $group Load document types of a specific group only e.g. 'accounting'.
 * @return array
 */
function sab_get_document_types( $for = '', $group = '' ) {
	global $sab_document_types;

	if ( ! is_array( $sab_document_types ) ) {
		$sab_document_types = array();
	}

	$document_types = $sab_document_types;

	if ( ! empty( $group ) ) {
		$document_types = wp_filter_object_list( $document_types, array( 'group' => $group ), 'AND', false );
	}

	if ( ! empty( $for ) ) {
		$args = array();

		if ( 'count' === $for ) {
			$args = array(
				'exclude_from_count' => false,
			);
		}

		if ( ! empty( $args ) ) {
			$document_types = wp_filter_object_list( $document_types, $args, 'AND', false );
		}
	}

	$document_types = array_keys( $document_types );

	return apply_filters( 'storeabill_document_types', $document_types, $for );
}

function sab_get_document_type_line_item_types( $type ) {
	$item_types = array();

	if ( $document_type = sab_get_document_type( $type ) ) {
		$item_types = $document_type->default_line_item_types;
	}

	return $item_types;
}

function sab_get_document( $document_id = 0, $document_type = '' ) {
	if ( ! did_action( 'storeabill_registered_core_document_types' ) ) {
		sab_doing_it_wrong( __FUNCTION__, 'sab_get_document should not be called before document types are registered (storeabill_registered_core_document_types action)', '1.0' );
		return false;
	}

	return Factory::get_document( $document_id, $document_type );
}

function sab_remove_document_item_type_prefix( $maybe_prefixed_type, $document_type_name ) {
	if ( $document_type = sab_get_document_type( $document_type_name ) ) {
		$group = $document_type->group;

		if ( substr( $maybe_prefixed_type, 0, ( strlen( $group ) + 1 ) ) === $group . '_' ) {
			$maybe_prefixed_type = substr( $maybe_prefixed_type, strlen( $group ) + 1 );
		}
 	}

	return $maybe_prefixed_type;
}

function sab_maybe_prefix_document_item_type( $maybe_prefixed_type, $document_type_name ) {
	if ( $document_type = sab_get_document_type( $document_type_name ) ) {
		$group = $document_type->group;

		if ( substr( $maybe_prefixed_type, 0, ( strlen( $group ) + 1 ) ) !== $group . '_' ) {
			$maybe_prefixed_type = $group . '_' . $maybe_prefixed_type;
		}
	}

	return $maybe_prefixed_type;
}

function sab_get_document_item( $item_id = 0, $item_type = '' ) {
	if ( ! did_action( 'storeabill_registered_core_document_types' ) ) {
		sab_doing_it_wrong( __FUNCTION__, 'sab_get_document_item should not be called before document types are registered (storeabill_registered_core_document_types action)', '1.0' );
		return false;
	}

	return Factory::get_document_item( $item_id, $item_type );
}

function sab_get_document_number_placeholders( $document_type ) {
	$placeholders = array(
		'{number}' => _x( 'Plain sequential number (e.g. 1)', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'{y}'      => _x( 'Year (e.g. 20)', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'{Y}'      => _x( 'Year (e.g. 2020)', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'{d}'      => _x( 'Day (e.g. 01)', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'{j}'      => _x( 'Day (e.g. 1)', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'{m}'      => _x( 'Month (e.g. 05)', 'storeabill-core', 'woocommerce-germanized-pro' ),
		'{n}'      => _x( 'Month (e.g. 5)', 'storeabill-core', 'woocommerce-germanized-pro' ),
	);

	if ( 'invoice' === $document_type ) {
		$placeholders['{order_number}'] = _x( 'Order number (e.g. 1234)', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	return apply_filters( 'storeabill_available_document_number_placeholders', $placeholders, $document_type );
}

/**
 * @param int $notice_id
 * @param string $notice_type
 *
 * @return bool|\Vendidero\StoreaBill\Document\Notice
 */
function sab_get_document_notice( $notice_id = 0, $notice_type = '' ) {
	if ( ! did_action( 'storeabill_registered_core_document_types' ) ) {
		sab_doing_it_wrong( __FUNCTION__, 'sab_get_document_notice should not be called before document types are registered (storeabill_registered_core_document_types action)', '1.0' );
		return false;
	}

	return Factory::get_document_notice( $notice_id, $notice_type );
}

/**
 * @param $template_id
 * @param false $allow_first_page
 *
 * @return bool|\Vendidero\StoreaBill\Document\DefaultTemplate|\Vendidero\StoreaBill\Document\FirstPageTemplate
 */
function sab_get_document_template( $template_id, $allow_first_page = false ) {
	if ( ! did_action( 'storeabill_registered_core_document_types' ) ) {
		sab_doing_it_wrong( __FUNCTION__, 'sab_get_document_notice should not be called before document types are registered (storeabill_registered_core_document_types action)', '1.0' );
		return false;
	}

	return Factory::get_document_template( $template_id, '', $allow_first_page );
}

/**
 * @param $template_id
 *
 * @return bool|\Vendidero\StoreaBill\Document\DefaultTemplate|\Vendidero\StoreaBill\Document\FirstPageTemplate
 */
function sab_duplicate_document_template( $template_id ) {
	if ( $template = sab_get_document_template( $template_id, true ) ) {
		$new_template = clone $template;

		$new_template->set_id( 0 );
		$new_template->set_date_created( null );

		if ( $new_template->is_first_page() ) {
			$new_template->set_parent_id( 0 );
		} else {
			$new_template->set_title( sprintf( esc_html_x( '%s (Copy)', 'storeabill-core', 'woocommerce-germanized-pro' ), $template->get_title() ) );
		}

		$new_template->set_status( 'draft' );
		$new_id = $new_template->save();

		/**
		 * Duplicate and link first page template if available.
		 */
		if ( $new_id && is_a( $template, '\Vendidero\StoreaBill\Document\DefaultTemplate' ) && $template->has_custom_first_page() ) {
			$first_page_copy = sab_duplicate_document_template( $template->get_first_page()->get_id() );

			if ( $first_page_copy ) {
				$first_page_copy->set_parent_id( $new_id );
				$first_page_copy->save();
			}
		}

		do_action( 'storeabill_document_template_duplicated', $new_template, $template_id );

		return $new_template;
	}

	return false;
}

function sab_get_document_template_status_name( $status ) {
	$label = _x( 'Draft', 'storeabill-core', 'woocommerce-germanized-pro' );

	if( 'publish' === $status ) {
		$label = _x( 'Published', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	return $label;
}

function sab_get_document_templates( $document_types = array(), $include_drafts = true, $args = array() ) {
	if ( ! is_array( $document_types ) ) {
		$document_types = array( $document_types );
	}

	$document_types = array_filter( $document_types );

	$query_args = wp_parse_args( $args, array(
		'post_type'      => 'document_template',
		'post_status'    => $include_drafts ? array( 'publish', 'draft', 'auto-draft' ) : array( 'publish' ),
		'posts_per_page' => -1,
		'post_parent'    => 0,
		'meta_query'     => array(
			array(
				'key'     => '_template_name',
				'compare' => 'EXISTS'
			),
		),
	) );

	if ( ! empty( $document_types ) ) {
		$query_args['meta_query'][] = array(
			'key'     => '_document_type',
			'value'   => $document_types,
			'compare' => 'IN'
		);
	}

	$query     = new WP_Query( $query_args );
	$templates = array();

	foreach( $query->posts as $post ) {
		$templates[] = sab_get_document_template( $post->ID );
	}

	return $templates;
}

function sab_create_document_template( $document_type, $editor_template = 'default', $set_html = false ) {
	$template = new \Vendidero\StoreaBill\Document\DefaultTemplate();
	$template->set_document_type( $document_type );
	$template->set_status( 'publish' );

	if ( $editor_tpl = \Vendidero\StoreaBill\Editor\Helper::get_editor_template( $document_type, $editor_template ) ) {
		$template->set_props( $editor_tpl::get_template_data() );
		$template->set_template_name( $editor_tpl::get_name() );

		if ( $set_html ) {
			$template->set_content( $editor_tpl::get_html() );
			/**
			 * This one is ready to use.
			 */
			$template->set_status( 'publish' );
		} else {
			/**
			 * Only auto-drafts are loading post type template data.
			 */
			$template->set_status( 'auto-draft' );
		}
	} else {
		$template->set_template_name( 'default' );
	}

	do_action( 'storeabill_before_create_document_template', $template, $editor_template );

	$template->save();

	return $template;
}

/**
 * Returns the default document template based on a document type.
 *
 * @param string $document_type
 *
 * @return \Vendidero\StoreaBill\Document\Template|boolean
 */
function sab_get_default_document_template( $document_type ) {
	$template_id = \Vendidero\StoreaBill\Package::get_setting( $document_type . '_default_template' );
	$template_id = apply_filters( 'storeabill_default_document_template_id', $template_id, $document_type );

	if ( empty( $template_id ) || ! get_post( $template_id ) ) {
		if ( $template = sab_create_document_template( $document_type, 'default', true ) ) {
			$template_id = $template->get_id();

			update_option( 'storeabill_' . $document_type . '_default_template', $template_id );
		}
	}

	return sab_get_document_template( $template_id );
}

/**
 * @param $document_type
 *
 * @return bool|\Vendidero\StoreaBill\Interfaces\Previewable
 */
function sab_get_document_preview( $document_type, $is_editor_preview = false ) {
	$classname = '';
	$instance  = false;
	$args      = array(
		'is_editor_preview' => $is_editor_preview
	);

	if ( $type = sab_get_document_type( $document_type ) ) {
		$classname = $type->preview_class_name;
	}

	if ( ! empty( $classname ) ) {
		$instance = new $classname( $args );
	}

	if ( ! is_a( $instance, '\Vendidero\StoreaBill\Interfaces\Previewable' ) || ! is_a( $instance, '\Vendidero\StoreaBill\Document\Document' ) ) {
		$instance = false;
	} else {
		/**
		 * Make sure all the changes are applied to retrieve data conveniently.
		 */
		$instance->save();
	}

	return $instance;
}

function sab_get_document_default_font_size() {
	return apply_filters( 'storeabill_document_default_font_size', 13 );
}

function sab_get_document_default_color() {
	return apply_filters( 'storeabill_document_default_color', '#000000' );
}

function sab_get_document_font_sizes() {
	return array(
		'tiny' => array(
			'name' => _x( 'Tiny', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'size' => 10,
			'slug' => 'tiny'
		),
		'small' => array(
			'name' => _x( 'Small', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'size' => 11,
			'slug' => 'small'
		),
		'normal' => array(
			'name' => _x( 'Normal', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'size' => sab_get_document_default_font_size(),
			'slug' => 'normal'
		),
		'medium' => array(
			'name' => _x( 'Medium', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'size' => 14,
			'slug' => 'medium'
		),
		'large' => array(
			'name' => _x( 'Large', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'size' => 18,
			'slug' => 'large'
		),
	);
}

function sab_get_document_font_size( $slug ) {
	$font_sizes = sab_get_document_font_sizes();

	if ( is_numeric( $slug ) ) {
		return $slug;
	} elseif ( preg_match('~[0-9]+~', $slug ) ) {
		/**
		 * Support font size strings that contain units (e.g. px, mm, cm)
		 */
		$number = absint( preg_replace('/[^0-9]/', '', $slug ) );

		if ( empty( $number ) ) {
			return $font_sizes['normal']['size'];
		} else {
			return $number;
		}
	}

	if ( array_key_exists( $slug, $font_sizes ) ) {
		return $font_sizes[ $slug ]['size'];
	} else {
		return $font_sizes['normal']['size'];
	}
}

function sab_get_document_type_label( $type, $label_type = 'singular' ) {
	$label = 'singular' === $label_type ? _x( 'Document', 'storeabill-core', 'woocommerce-germanized-pro' ) : _x( 'Documents', 'storeabill-core', 'woocommerce-germanized-pro' );

	if ( is_object( $type ) && isset( $type->labels ) ) {
		$document_type = $type;
	} else {
		$document_type = sab_get_document_type( $type );
	}

	if ( $document_type ) {
		$label = array_key_exists( $label_type, $document_type->labels ) ? $document_type->labels[ $label_type ] : $document_type->labels['plural'];
	}

	return apply_filters( "storeabill_document_type_label", $label, $type, $label_type );
}

/**
 * @param Document $document
 *
 * @return string
 */
function sab_get_document_salutation( $document ) {
	return apply_filters( 'storeabill_document_address_salutation', sab_get_address_salutation( $document->get_address() ), $document );
}

function _sab_keep_force_filename( $new_filename ) {
	return ( ! empty( $GLOBALS['storeabill_forced_filename'] ) ? $GLOBALS['storeabill_forced_filename'] : $new_filename );
}

/**
 * @param $filename
 * @param $stream
 * @param bool $relative
 *
 * @return bool|mixed|void
 */
function sab_upload_document( $filename, $stream, $relative = true, $force_override = false ) {
	try {
		\Vendidero\StoreaBill\UploadManager::set_upload_dir_filter();

		if ( $force_override ) {
			add_filter( 'wp_unique_filename', '_sab_keep_force_filename', 250, 1 );
			$GLOBALS['storeabill_forced_filename'] = $filename;
		}

		$tmp = wp_upload_bits( $filename,null, $stream );

		if ( $force_override ) {
			remove_filter( 'wp_unique_filename', '_sab_keep_force_filename', 250 );
			unset( $GLOBALS['storeabill_forced_filename'] );
		}

		\Vendidero\StoreaBill\UploadManager::unset_upload_dir_filter();

		if ( isset( $tmp['file'] ) ) {
			$path = $tmp['file'];

			if ( $relative ) {
				$path = \Vendidero\StoreaBill\UploadManager::get_relative_upload_dir( $path );
			}

			return $path;
		} else {
			throw new Exception( _x( 'Error while uploading document.', 'storabill-core', 'storeabill' ) );
		}
	} catch ( Exception $e ) {
		return false;
	}
}

function sab_get_documents_counts( $type ) {
	$counts = array();

	foreach( array_keys( sab_get_document_statuses( $type ) ) as $status ) {
		$counts[ $status ] = sab_get_document_count( $type, $status );
	}

	return $counts;
}

function sab_get_document_count( $type, $status ) {
	$count           = 0;
	$statuses        = array_keys( sab_get_document_statuses( $type ) );
	$data_store_name = str_replace( '_', '-', $type );

	if ( ! in_array( $status, $statuses, true ) ) {
		return 0;
	}

	$cache_key    = \Vendidero\StoreaBill\Utilities\CacheHelper::get_cache_prefix( 'documents' ) . $status . $type;
	$cached_count = wp_cache_get( $cache_key, 'counts' );

	if ( false !== $cached_count ) {
		return $cached_count;
	}

	try {
		$data_store = sab_load_data_store( $data_store_name );

		if ( $data_store ) {
			$count += $data_store->get_document_count( $status, $type );
		}

		wp_cache_set( $cache_key, $count, 'counts' );
	} catch( Exception $e ) {}

	return $count;
}

function sab_get_document_type_exporter_classname( $document_type, $export_type = 'csv' ) {
	$exporter = false;

	if ( $document_type_object = sab_get_document_type( $document_type ) ) {
		$exporters = (array) $document_type_object->exporters;

		if ( array_key_exists( $export_type, $exporters ) ) {
			$exporter = $exporters[ $export_type ];
		}
	}

	return apply_filters( "storeabill_{$document_type}_{$export_type}_exporter_classname", $exporter, $document_type, $export_type );
}

/**
 * @param string $document_type
 * @param string $export_type
 *
 * @return mixed|\Vendidero\StoreaBill\Interfaces\Exporter
 */
function sab_get_document_type_exporter( $document_type, $export_type = 'csv' ) {
	$exporter = false;

	if ( $classname = sab_get_document_type_exporter_classname( $document_type, $export_type ) ) {
		$exporter = new $classname( $document_type );
	}

	return apply_filters( "storeabill_{$document_type}_{$export_type}_exporter", $exporter, $document_type, $export_type );
}

function sab_get_document_type_barcode_code_types( $document_type ) {
	$code_types = array();

	if ( $type_data = sab_get_document_type( $document_type ) ) {
		$code_types = $type_data->barcode_code_types;
	}

	return apply_filters( "storeabill_{$document_type}_barcode_code_types", $code_types, $document_type );
}

/**
 * Get the placeholder image.
 *
 * Uses wp_get_attachment_image if using an attachment ID @since 3.6.0 to handle responsiveness.
 *
 * @param string       $size Image size.
 * @param string|array $attr Optional. Attributes for the image markup. Default empty.
 * @return string
 */
function sab_placeholder_img( $size = '' ) {
	return trailingslashit( \Vendidero\StoreaBill\Package::get_assets_url() ) . 'images/placeholder.png';
}