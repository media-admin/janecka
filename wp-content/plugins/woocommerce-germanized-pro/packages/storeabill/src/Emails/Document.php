<?php

namespace Vendidero\StoreaBill\Emails;

defined( 'ABSPATH' ) || exit;

class Document extends Email {

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
		$this->id             = 'sab_document';
		$this->title          = _x( 'Document (PDF)', 'storeabill-core', 'woocommerce-germanized-pro' );
		$this->description    = _x( 'This email sends a certain document (PDF) to the customer.', 'storeabill-core', 'woocommerce-germanized-pro' );
		$this->template_html  = 'emails/customer-pdf-document.php';
		$this->template_plain = 'emails/plain/customer-pdf-document.php';
		$this->placeholders   = $this->get_default_placeholders();

		// Call parent constructor.
		parent::__construct();

		$this->customer_email = true;
	}

	protected function get_default_placeholders() {
		return array(
			'{document_number}'           => '',
			'{document_id}'               => '',
			'{document_formatted_number}' => '',
			'{document_date}'             => '',
			'{document_type}'             => '',
			'{document_title}'            => '',
		);
	}

	/**
	 * Get email subject.
	 *
	 * @since  3.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return _x( '{document_title} from {site_title}', 'storeabill-core', 'woocommerce-germanized-pro' );
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

	/**
	 * @param $object_id
	 * @param bool $object
	 *
	 * @return bool|\Vendidero\StoreaBill\Document\Document
	 */
	protected function get_document( $object_id, $object = false ) {
		if ( $object_id && ! is_a( $object, 'Vendidero\StoreaBill\Document\Document' ) ) {
			$object = sab_get_document( $object_id );
		}

		return $object;
	}

	protected function is_valid( $document ) {
		return is_a( $document, 'Vendidero\StoreaBill\Document\Document' ) && $document->has_file();
	}

	protected function setup_email( $document ) {
		$this->object    = $document;
		$this->recipient = $this->object->get_email();

		$this->setup_placeholders();
	}

	protected function setup_placeholders() {
		$this->placeholders['{document_date}']             = wc_format_datetime( $this->object->get_date_created() );
		$this->placeholders['{document_number}']           = $this->object->get_number();
		$this->placeholders['{document_formatted_number}'] = $this->object->get_formatted_number();
		$this->placeholders['{document_id}']               = $this->object->get_id();
		$this->placeholders['{document_type}']             = sab_get_document_type_label( $this->object->get_type() );
		$this->placeholders['{document_title}']            = $this->object->get_title();
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int $document_id The document id.
	 * @param \Vendidero\StoreaBill\Document\Document|false $document Document object.
	 *
	 * @return bool
	 */
	public function trigger( $document_id, $document = false ) {
		$this->setup_locale();

		$document = $this->get_document( $document_id, $document );

		if ( $this->is_valid( $document ) ) {
			$this->setup_email( $document );
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->setup_email_locale();
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
			$this->restore_email_locale();

			return true;
		}

		$this->restore_locale();

		return false;
	}

	public function setup_email_locale( $lang = false ) {
		parent::setup_email_locale( $lang );

		/**
		 * Make sure to reload placeholders to enable translation.
		 */
		$this->setup_placeholders();
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return sab_get_template_html(
			$this->template_html,
			array(
				'document'           => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => $this->is_admin(),
				'plain_text'         => false,
				'email'              => $this,
			)
		);
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return sab_get_template_html(
			$this->template_plain,
			array(
				'document'           => $this->object,
				'email_heading'      => $this->get_heading(),
				'additional_content' => $this->get_additional_content(),
				'sent_to_admin'      => $this->is_admin(),
				'plain_text'         => true,
				'email'              => $this,
			)
		);
	}

	protected function get_additional_form_fields() {
		return array();
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields = array_merge( $this->form_fields, $this->get_additional_form_fields() );
	}

	public function get_attachments() {
		$attachments = parent::get_attachments();

		if ( $this->is_valid( $this->object ) ) {
			$attachments[] = $this->object->get_path();
		}

		return $attachments;
	}
}