<?php

namespace wpai_woocommerce_add_on\libraries\importer;

require_once dirname(__FILE__) . '/ImportOrderBase.php';

/**
 *
 * Import Order details - status, date
 *
 * Class ImportOrderDetails
 * @package wpai_woocommerce_add_on\libraries\importer
 */
class ImportOrderDetails extends ImportOrderBase {

    /**
     * @return int|\WP_Error
     */
	public function import() {

		$order_status = trim( $this->getValue( 'status' ) );

		// detect order status by slug or title
		$all_order_statuses = wc_get_order_statuses();
		if ( empty( $all_order_statuses[ $order_status ] ) ) {
			$status_founded = false;
			foreach ( $all_order_statuses as $key => $value ) {
				if ( strtolower( $value ) == strtolower( $order_status ) ) {
					$order_status   = $key;
					$status_founded = true;
					break;
				}
			}
			if ( ! $status_founded ) {
				$order_status = 'wc-pending';
			}
		}

		// Start Store order data for internal use.
		$this->order_data = array(
			'ID' => $this->getOrderID(),
			'post_title' => 'Order &ndash; ' . date_i18n('F j, Y @ h:i A', strtotime($this->getValue('date'))),
			'post_content' => '',
			'post_date' => $this->getValue('date'),
			'post_date_gmt' => get_gmt_from_date($this->getValue('date')),
			'post_status' => $order_status,
			'ping_status' => 'closed',
			'post_password' => uniqid('order_'),
			'post_excerpt' => $this->getValue('customer_provided_note'),
		);

		if (!$this->isNewOrder()) {

			if ($this->getImport()->options['update_all_data'] == 'no') {

				if (!$this->getImport()->options['is_update_dates']) { // preserve date of already existing article when duplicate is found
					$this->order_data['post_title'] = 'Order &ndash; ' . date_i18n('F j, Y @ h:i A', strtotime($this->getArticleData('post_date')));
					$this->order_data['post_date'] = $this->getArticleData('post_date');
					$this->order_data['post_date_gmt'] = $this->getArticleData('post_date_gmt');
				}
				if (!$this->getImport()->options['is_update_status']) { // preserve status and trashed flag
					$this->order_data['post_status'] = $this->getArticleData('post_status');
				}
				if (!$this->getImport()->options['is_update_excerpt']) { // preserve customer's note
					$this->order_data['post_excerpt'] = $this->getArticleData('post_excerpt');
				}

			}
		}

		// End Store order data for internal use.

		$order = wc_get_order( $this->getOrderID() );

		try {

			// Update order data.
			if ( $this->getImport()->options['is_update_dates'] || $this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' ) {
				$order->set_date_created( $this->order_data['post_date'] );
			}
			if ( $this->getImport()->options['is_update_status'] || $this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' ) {
				$order->set_status( $order_status );
			}
			if ( $this->getImport()->options['is_update_excerpt'] || $this->isNewOrder() || $this->getImport()->options['update_all_data'] == 'yes' ) {
				$order->set_customer_note( $this->getValue( 'customer_provided_note' ) );
			}

			if ( ! $this->isNewOrder() ) {
				// Store previous order status.
				update_post_meta( $this->getPid(), '_previous_status', $this->getArticleData( 'post_status' ) );
			}

		} catch ( \WC_Data_Exception $e ) {
			self::getLogger() && call_user_func( self::getLogger(), '<b>ERROR:</b> ' . $e->getMessage() );
		}

        return $order->save();
	}

    /**
     * @return mixed
     */
    public function getOrderData() {
        return $this->order_data;
    }

    /**
     * @param mixed $order_data
     */
    public function setOrderData($order_data) {
        $this->order_data = $order_data;
    }
}
