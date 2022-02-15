<?php

namespace Vendidero\Germanized\Pro\StoreaBill\PackingSlip;

use Vendidero\Germanized\Pro\StoreaBill\PackingSlip;
use Vendidero\StoreaBill\Emails\DocumentAdmin;

defined( 'ABSPATH' ) || exit;

class Email extends DocumentAdmin {

	/**
	 * Object this email is for.
	 *
	 * @var PackingSlip|bool
	 */
	public $object;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id             = 'sab_packing_slip';
		$this->title          = __( 'Packing Slip (PDF)', 'woocommerce-germanized-pro' );
		$this->description    = __( 'This email sends a packing slip (PDF) to the shop manager.', 'woocommerce-germanized-pro' );
		$this->template_html  = 'emails/admin-pdf-document.php';
		$this->template_plain = 'emails/plain/admin-pdf-document.php';
		$this->placeholders   = $this->get_default_placeholders();

		\Vendidero\StoreaBill\Emails\Email::__construct();

		// Other settings.
		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Get email subject.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}]: {document_title}', 'woocommerce-germanized-pro' );
	}

	/**
	 * Get email heading.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( '{document_title}', 'woocommerce-germanized-pro' );
	}

	protected function setup_email( $document ) {
		$this->object = $document;

		$this->setup_placeholders();
	}

	protected function get_default_placeholders() {
		$placeholders = parent::get_default_placeholders();

		$placeholders['{order_id}']        = '';
		$placeholders['{order_number}']    = '';
		$placeholders['{shipment_id}']     = '';
		$placeholders['{shipment_number}'] = '';

		return $placeholders;
	}

	protected function setup_placeholders() {
		parent::setup_placeholders();

		$this->placeholders['{order_id}']        = $this->object->get_order_id();
		$this->placeholders['{order_number}']    = $this->object->get_order_number();
		$this->placeholders['{shipment_number}'] = $this->object->get_shipment_number();
		$this->placeholders['{shipment_id}']     = $this->object->get_shipment_id();
	}

	public function is_admin() {
		return true;
	}

	protected function get_additional_form_fields() {
		return array(
			'recipient'       => array(
				'title'       => _x( 'Recipient(s)', 'woocommerce-germanized-pro' ),
				'type'        => 'text',
				/* translators: %s: WP admin email */
				'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce-germanized-pro' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}
}