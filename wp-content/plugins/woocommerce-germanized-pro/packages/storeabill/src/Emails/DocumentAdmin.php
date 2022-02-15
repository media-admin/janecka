<?php

namespace Vendidero\StoreaBill\Emails;

defined( 'ABSPATH' ) || exit;

class DocumentAdmin extends Document {

	/**
	 * Object this email is for.
	 *
	 * @var \Vendidero\StoreaBill\Document\Document|bool
	 */
	public $object;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id             = 'sab_document_admin';
		$this->title          = _x( 'Document Admin (PDF)', 'storeabill-core', 'woocommerce-germanized-pro' );
		$this->description    = _x( 'This email sends a certain document (PDF) to the shop manager.', 'storeabill-core', 'woocommerce-germanized-pro' );
		$this->template_html  = 'emails/admin-pdf-document.php';
		$this->template_plain = 'emails/plain/admin-pdf-document.php';
		$this->placeholders   = $this->get_default_placeholders();

		Email::__construct();

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
		return _x( '[{site_title}]: {document_title}', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	/**
	 * Get email heading.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return _x( '{document_title}', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	protected function setup_email( $document ) {
		$this->object = $document;

		$this->setup_placeholders();
	}

	public function is_admin() {
		return true;
	}

	protected function get_additional_form_fields() {
		return array(
			'recipient'       => array(
				'title'       => _x( 'Recipient(s)', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'type'        => 'text',
				/* translators: %s: WP admin email */
				'description' => sprintf( _x( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'storeabill-core', 'woocommerce-germanized-pro' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}
}