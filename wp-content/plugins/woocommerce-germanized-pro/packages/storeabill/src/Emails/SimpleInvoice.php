<?php

namespace Vendidero\StoreaBill\Emails;

defined( 'ABSPATH' ) || exit;

class SimpleInvoice extends Document {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id             = 'sab_simple_invoice';
		$this->title          = _x( 'Invoice (PDF)', 'storeabill-core', 'woocommerce-germanized-pro' );
		$this->description    = _x( 'This email sends an invoice (PDF) to the customer.', 'storeabill-core', 'woocommerce-germanized-pro' );
		$this->template_html  = 'emails/customer-pdf-invoice.php';
		$this->template_plain = 'emails/plain/customer-pdf-invoice.php';
		$this->placeholders   = $this->get_default_placeholders();

		// Call parent constructor.
		Email::__construct();

		$this->customer_email = true;
	}

	/**
	 * @param $object_id
	 * @param bool $object
	 *
	 * @return bool|\Vendidero\StoreaBill\Invoice\Simple
	 */
	protected function get_document( $object_id, $object = false ) {
		if ( $object_id && ! is_a( $object, 'Vendidero\StoreaBill\Invoice\Simple' ) ) {
			$object = sab_get_invoice( $object_id );
		}

		return $object;
	}

	protected function is_valid( $document ) {
		return is_a( $document, 'Vendidero\StoreaBill\Invoice\Simple' ) && $document->has_file();
	}

	protected function get_default_placeholders() {
		$placeholders = parent::get_default_placeholders();

		$placeholders['{order_id}']     = '';
		$placeholders['{order_number}'] = '';
		$placeholders['{total}']        = '';

		return $placeholders;
	}

	protected function setup_placeholders() {
		parent::setup_placeholders();

		$this->placeholders['{order_id}']     = $this->object->get_order_id();
		$this->placeholders['{order_number}'] = $this->object->get_order_number();
		$this->placeholders['{total}']        = $this->object->get_formatted_price( $this->object->get_total() );
	}
}