<?php

namespace Vendidero\StoreaBill\Invoice;

use Vendidero\StoreaBill\Admin\Admin;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Table;

defined( 'ABSPATH' ) || exit;

/**
 * Class Table
 */
class SimpleTable extends Table {

	public function __construct( $args = array() ) {
		$args['type'] = 'invoice';

		parent::__construct( $args );
	}

	public function get_document( $id ) {
		return sab_get_invoice( $id );
	}

	protected function get_default_hidden_columns() {
		return array( 'address' );
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

	protected function get_custom_columns() {
		$columns = array();

		$columns['cb']       = '<input type="checkbox" />';
		$columns['title']    = _x( 'Title', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['date']     = _x( 'Date', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['status']   = _x( 'Status', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['date_due'] = _x( 'Due until', 'storeabill-core', 'woocommerce-germanized-pro' );
		$columns['payment']  = _x( 'Payment', 'storeabill-core', 'woocommerce-germanized-pro' );
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
			'date_due' => array( 'date_due', false ),
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
	 * Invoice title
	 *
	 * @param Simple $document The current document object.
	 */
	public function column_payment( $document ) {
		echo '<span class="sab-status sab-payment-status sab-invoice-type-' . esc_attr( $document->get_invoice_type() ) . '-payment-status sab-payment-status-' . esc_attr( $document->get_payment_status() ) . '">' . sab_get_invoice_payment_status_name( $document->get_payment_status() ) .'</span>';
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

	/**
	 * Invoice date due
	 *
	 * @param Simple $document The current document object.
	 */
	public function column_date_due( $document ) {
		$timestamp = $document->get_date_due() ? $document->get_date_due()->getTimestamp() : '';

		if ( ! $timestamp ) {
			echo '&ndash;';
			return;
		}

		$show_date = $document->get_date_due()->date_i18n( apply_filters( "{$this->get_hook_prefix()}date_format", sab_date_format() ) );
		$classes   = 'invoice-date-past-due ' . ( $document->is_past_due() ? 'past-due' : 'valid' );

		printf(
			'<time datetime="%1$s" title="%2$s" class="%3$s">%4$s</time>',
			esc_attr( $document->get_date_due()->date( 'c' ) ),
			esc_html( $document->get_date_due()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			$classes,
			esc_html( $show_date )
		);
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
	 * Display the list of views available on this table.
	 *
	 * @since 3.1.0
	 */
	public function views() {
		parent::views();

		$views = $this->get_payment_views();

		if ( empty( $views ) ) {
			return;
		}

		echo '<div class="sab-payment-views"><span class="payment-title">' . _x( 'Payment:', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</span>';
		echo "<ul class='subsubsub'>\n";
		foreach ( $views as $class => $view ) {
			$views[ $class ] = "\t<li class='$class'>$view";
		}
		echo implode( " |</li>\n", $views ) . "</li>\n";
		echo '</ul>';
		echo '</div>';
	}

	public function get_payment_views() {
		$views         = array();
		$num_documents = sab_get_invoice_payment_statuses_counts();

		foreach( sab_get_invoice_payment_statuses() as $status => $title ) {
			$class = '';

			if ( empty( $num_documents[ $status ] ) ) {
				continue;
			}

			if ( isset( $_REQUEST['document_payment_status'] ) && $status === $_REQUEST['document_payment_status'] ) {
				$class = 'current';
			}

			$status_label = sprintf(
				translate_nooped_plural( _nx_noop( $title . ' <span class="count">(%s)</span>', $title . ' <span class="count">(%s)</span>', 'storeabill-core', 'woocommerce-germanized-pro' ), $num_documents[ $status ] ),
				number_format_i18n( $num_documents[ $status ] )
			);

			$url        = add_query_arg( array( 'document_payment_status' => $status ), $this->get_main_page() );
			$class_html = $aria_current = '';

			if ( ! empty( $class ) ) {
				$class_html = sprintf(
					' class="%s"',
					esc_attr( $class )
				);

				if ( 'current' === $class ) {
					$aria_current = ' aria-current="page"';
				}
			}

			$status_link = sprintf(
				'<a href="%s"%s%s>%s</a>',
				esc_url( $url ),
				$class_html,
				$aria_current,
				$status_label
			);

			$views['payment_status_' . $status ] = $status_link;
		}

		return $views;
	}

	public function get_main_page() {
		return 'admin.php?page=sab-accounting&document_type=' . $this->document_type;
	}
}