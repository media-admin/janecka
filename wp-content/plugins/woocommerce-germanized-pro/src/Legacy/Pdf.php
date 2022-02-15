<?php

namespace Vendidero\Germanized\Pro\Legacy;

use Vendidero\StoreaBill\Document\Document;

defined( 'ABSPATH' ) || exit;

class Pdf {

	public $id = 0;

	public $content_type = '';

	public $type = '';

	/**
	 * @var null|Document
	 */
	protected $document = null;

	public function __construct( $pdf ) {
		if ( is_a( $pdf, '\Vendidero\StoreaBill\Document\Document' ) ) {
			$this->id           = $pdf->get_id();
			$this->content_type = $pdf->get_type();

			if ( is_a( $pdf, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
				$this->content_type = 'invoice';
				$this->type         = $pdf->get_invoice_type();
			} elseif ( is_a( $pdf, '\Vendidero\Germanized\Pro\StoreaBill\PackingSlip' ) ) {
				$this->content_type = 'packing_slip';
				$this->type         = 'simple';
			}

			$this->document = $pdf;
		}

		if ( ! is_a( $this->document, '\Vendidero\StoreaBill\Document\Document' ) ) {
			throw new \Exception( __( 'Cannot instantiate document', 'woocommerce-germanized-pro' ) );
		}
	}

	public function __get( $name ) {
		$getter = "get_{$name}";

		if ( is_callable( array( $this->document, $getter ) ) ) {
			return $this->document->$getter();
		}

		return false;
	}

	/**
	 * @return Document
	 */
	public function get_document() {
		return $this->document;
	}

	public function is_type( $type ) {
		return $this->type === $type;
	}

	public function get_id() {
		return $this->document->get_id();
	}

	public function refresh() {
		do_action( 'woocommerce_gzdp_before_pdf_refresh', $this );

		do_action( 'woocommerce_gzdp_before_pdf_save', $this );

		$this->document->render();
	}

	public function delete( $bypass_trash ) {
		return $this->document->delete( $bypass_trash );
	}

	public function generate_pdf( $preview = false ) {
		do_action( 'woocommerce_gzdp_generate_pdf', $this );

		$this->document->render( $preview );

		return $this->document->get_path();
	}

	public function get_pdf_url( $force = false ) {
		return $this->document->get_download_url( $force );
	}

	public function get_filename() {
		return $this->document->get_filename();
	}

	public function has_attachment() {
		return $this->document->has_file();
	}

	public function get_pdf_path() {
		return $this->document->get_path();
	}

	public function get_date( $format ) {
		return $this->document->get_date_created()->date_i18n( $format );
	}

	public function get_title_pdf() {
		return apply_filters( 'woocommerce_gzdp_' . $this->content_type . '_title_pdf', $this->get_title( true ), $this );
	}

	public function populate() {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::populate', '3.0.0' );

		return;
	}

	public function get_option( $key, $default = false, $suppress_typing = false ) {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::get_option', '3.0.0' );

		return false;
	}

	public function get_color_option( $key ) {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::get_color_option', '3.0.0' );

		return false;
	}

	public function get_static_pdf_text( $where = '' ) {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::get_static_pdf_text', '3.0.0' );

		return '';
	}

	public function get_font() {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::get_font', '3.0.0' );

		return '';
	}

	public function has_custom_font() {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::has_custom_font', '3.0.0' );

		return false;
	}

	public function get_font_size() {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::get_font_size', '3.0.0' );

		return 8;
	}

	public function save_attachment( $file ) {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::save_attachment', '3.0.0' );

		return false;
	}

	public function get_pdf_template( $first = false ) {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::get_pdf_template', '3.0.0' );

		return false;
	}

	public function has_pdf_footer() {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::has_pdf_footer', '3.0.0' );

		return false;
	}

	public function has_pdf_header() {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::has_pdf_header', '3.0.0' );

		return false;
	}

	public function has_pdf_header_first() {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::has_pdf_header_first', '3.0.0' );

		return false;
	}

	public function filename_exists() {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::filename_exists', '3.0.0' );

		return false;
	}

	public function locate_template( $template ) {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::locate_template', '3.0.0' );

		return false;
	}

	public function get_template_content( $template, $pdf ) {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::get_template_content', '3.0.0' );

		return '';
	}

	public function keep_filename( $filename, $ext, $dir ) {
		wc_deprecated_function( 'WC_GZDP_Post_PDF::keep_filename', '3.0.0' );

		return '';
	}

	/**
	 * Check if a method is callable by checking the underlying order object.
	 * Necessary because is_callable checks will always return true for this object
	 * due to overloading __call.
	 *
	 * @param $method
	 *
	 * @return bool
	 */
	public function is_callable( $method ) {
		if ( method_exists( $this, $method ) ) {
			return true;
		} elseif( is_callable( array( $this->get_document(), $method ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Call child methods if the method does not exist.
	 *
	 * @param $method
	 * @param $args
	 *
	 * @return bool|mixed
	 */
	public function __call( $method, $args ) {
		if ( method_exists( $this->document, $method ) ) {
			return call_user_func_array( array( $this->document, $method ), $args );
		}

		return false;
	}
}