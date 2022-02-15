<?php

namespace wpai_woocommerce_add_on\libraries\importer;

use WC_Data_Store;
use WC_Order;

require_once dirname(__FILE__) . '/Importer.php';

/**
 * Class OrdersImporter
 * @package wpai_woocommerce_add_on\libraries\importer
 */
class OrdersImporter extends Importer {

    /**
     * @var array
     */
    public $importers = array();

    /**
     *
     * Import WooCommerce Order
     *
     * @return array
     */
    public function import() {

        $data = $this->getParsedData()['pmwi_order'];

        // Block email notifications during import process.
        if (!empty($this->getImport()->options['do_not_send_order_notifications'])) {
            add_filter('woocommerce_email_classes', [$this, 'woocommerce_email_classes'], 99, 1);
        }

        $this->importers['orderAddress'] = new ImportOrderAddress($this->getIndexObject(), $this->getOptions(), $data);
        $this->importers['orderPayment'] = new ImportOrderPayment($this->getIndexObject(), $this->getOptions(), $data);
        $this->importers['orderProductItems'] = new ImportOrderProductItems($this->getIndexObject(), $this->getOptions(), $data);
        $this->importers['orderFeeItems'] = new ImportOrderFeeItems($this->getIndexObject(), $this->getOptions(), $data);
        $this->importers['orderCouponItems'] = new ImportOrderCouponItems($this->getIndexObject(), $this->getOptions(), $data);
        $this->importers['orderShippingItems'] = new ImportOrderShippingItems($this->getIndexObject(), $this->getOptions(), $data);
        $this->importers['orderTotal'] = new ImportOrderTotal($this->getIndexObject(), $this->getOptions(), $data);
        $this->importers['orderRefunds'] = new ImportOrderRefunds($this->getIndexObject(), $this->getOptions(), $data);
        $this->importers['orderNotes'] = new ImportOrderNotes($this->getIndexObject(), $this->getOptions(), $data);
        $this->importers['orderTaxItems'] = new ImportOrderTaxItems($this->getIndexObject(), $this->getOptions(), $data);
        $this->importers['orderDetails'] = new ImportOrderDetails($this->getIndexObject(), $this->getOptions(), $data);

        /** @var ImportOrderBase $importer */
        foreach ($this->importers as $importer) {
            $importer->import();
        }
    }

    public function woocommerce_email_classes($emails) {
        remove_all_actions( 'woocommerce_order_status_cancelled_to_completed_notification');
        remove_all_actions( 'woocommerce_order_status_cancelled_to_on-hold_notification');
        remove_all_actions( 'woocommerce_order_status_cancelled_to_processing_notification');
        remove_all_actions( 'woocommerce_order_status_completed_notification');
        remove_all_actions( 'woocommerce_order_status_failed_to_completed_notification');
        remove_all_actions( 'woocommerce_order_status_failed_to_on-hold_notification');
        remove_all_actions( 'woocommerce_order_status_failed_to_processing_notification');
        remove_all_actions( 'woocommerce_order_status_on-hold_to_cancelled_notification');
        remove_all_actions( 'woocommerce_order_status_on-hold_to_failed_notification');
        remove_all_actions( 'woocommerce_order_status_on-hold_to_processing_notification');
        remove_all_actions( 'woocommerce_order_status_pending_to_completed_notification');
        remove_all_actions( 'woocommerce_order_status_pending_to_failed_notification');
        remove_all_actions( 'woocommerce_order_status_pending_to_on-hold_notification');
        remove_all_actions( 'woocommerce_order_status_pending_to_processing_notification');
        remove_all_actions( 'woocommerce_order_status_processing_to_cancelled_notification');
        return [];
    }

    /**
     *
     * After Import WooCommerce Order
     *
     * @return array
     */
    public function afterPostImport() {
	    $data_store = WC_Data_Store::load( 'customer-download' );
	    $data_store->delete_by_order_id( $this->getPid() );
	    wc_downloadable_product_permissions($this->getPid(), true);
        update_option('wp_all_import_previously_updated_order_' . $this->getImport()->id, $this->getPid());
    }
}
