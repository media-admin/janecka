<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Admin\Admin;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class Table
 */
class CancellationTable extends Table {

	public function __construct( $args = array() ) {
		$args['type'] = 'invoice_cancellation';

		parent::__construct( $args );
	}

	public function get_document( $id ) {
		return sab_get_invoice( $id, 'cancellation' );
	}

	public function get_query( $args ) {

		/**
		 * Order by total
		 */
		if ( isset( $args['orderby'] ) && 'total' === $args['orderby'] ) {
			$args['orderby']  = 'meta_value_num';
			$args['meta_key'] = '_total';
		}

		return new Query( $args );
	}

	protected function get_default_hidden_columns() {
		return array( 'address' );
	}

	protected function get_custom_columns() {
		$columns = array();

		$columns['cb']       = '<input type="checkbox" />';
		$columns['title']    = _x( 'Title', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['date']     = _x( 'Date', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['status']   = _x( 'Status', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['invoice']  = _x( 'Invoice', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['order']    = _x( 'Order', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['address']  = _x( 'Address', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['total']    = _x( 'Total', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['actions']  = _x( 'Actions', 'storeabill-core', 'woocommerce-germanized-pro' );

		return $columns;
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'date'     => array( 'date_created', false ),
			'title'    => array( 'number' ),
			'total'    => array( 'total' ),
			'order'    => array( 'order_id' ),
		);
	}

	/**
	 * Invoice total
	 *
	 * @param Simple $document The current document object.
	 */
	public function column_total( $document ) {
		echo $document->get_formatted_price( $document->get_total() );
	}

	/**
	 * Invoice title
	 *
	 * @param Simple $document The current document object.
	 */
	public function column_title( $document ) {
		$title = $document->get_title();

		if ( $url = $document->get_edit_url() ) {
			echo '<a href="' . esc_url( $url ) . '">' . $title . '</a> ';
		} else {
			echo $title . ' ';
		}
	}

	/**
	 * Invoice order
	 *
	 * @param Cancellation $document The current document object.
	 */
	public function column_invoice( $document ) {
		echo $document->get_parent_formatted_number();
	}

	/**
	 * Invoice order
	 *
	 * @param Simple $document The current document object.
	 */
	public function column_order( $document ) {
		if ( ( $order = $document->get_order() ) ) {
			echo '<a href="' . $order->get_edit_url() . '">' . $order->get_formatted_number() . '</a>';
		} else {
			echo $document->get_order_number();
		}
	}

	/**
	 * Invoice address
	 *
	 * @param Simple $document The current document object.
	 */
	public function column_address( $document ) {
		$address = $document->get_formatted_address();

		if ( $address ) {
			echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );
		} else {
			echo '&ndash;';
		}
	}

	public function get_main_page() {
		return 'admin.php?page=sab-accounting&document_type=' . $this->document_type;
	}
}