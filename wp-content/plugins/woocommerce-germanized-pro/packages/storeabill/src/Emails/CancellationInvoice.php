<?php

namespace Vendidero\StoreaBill\Emails;

defined( 'ABSPATH' ) || exit;

class CancellationInvoice extends Document {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id             = 'sab_cancellation_invoice';
		$this->title          = _x( 'Cancellation (PDF)', 'storeabill-core', 'woocommerce-germanized-pro' );
		$this->description    = _x( 'This email sends a cancellation (PDF) to the customer.', 'storeabill-core', 'woocommerce-germanized-pro' );
		$this->template_html  = 'emails/customer-pdf-cancellation.php';
		$this->template_plain = 'emails/plain/customer-pdf-cancellation.php';
		$this->placeholders   = $this->get_default_placeholders();

		// Call parent constructor.
		Email::__construct();

		$this->customer_email = true;
	}

	/**
	 * @param $object_id
	 * @param bool $object
	 *
	 * @return bool|\Vendidero\StoreaBill\Invoice\Cancellation
	 */
	protected function get_document( $object_id, $object = false ) {
		if ( $object_id && ! is_a( $object, 'Vendidero\StoreaBill\Invoice\Cancellation' ) ) {
			$object = sab_get_invoice( $object_id );
		}

		return $object;
	}

	protected function is_valid( $document ) {
		return is_a( $document, 'Vendidero\StoreaBill\Invoice\Cancellation' ) && $document->has_file();
	}

	protected function get_default_placeholders() {
		$placeholders = parent::get_default_placeholders();

		$placeholders['{order_id}']                         = '';
		$placeholders['{order_number}']                     = '';
		$placeholders['{document_parent_number}']           = '';
		$placeholders['{document_parent_formatted_number}'] = '';

		return $placeholders;
	}

	protected function setup_placeholders() {
		parent::setup_placeholders();

		$this->placeholders['{order_id}']                         = $this->object->get_order_id();
		$this->placeholders['{order_number}']                     = $this->object->get_order_number();
		$this->placeholders['{document_parent_number}']           = $this->object->get_parent_number();
		$this->placeholders['{document_parent_formatted_number}'] = $this->object->get_parent_formatted_number();
	}
}