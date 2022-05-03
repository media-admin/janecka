<?php

namespace Vendidero\Germanized\Pro\StoreaBill;

use Vendidero\StoreaBill\Document\Document;

defined( 'ABSPATH' ) || exit;

class PostDocument extends Document {

	protected $data_store_name = 'post_document';

	/**
	 * @var null|\WP_Post
	 */
	protected $post = null;

	public function get_type() {
		return 'post_document';
	}

	public function get_item_types() {
		return array();
	}

	public function get_data() {
		$data = parent::get_data();

		$data['content'] = $this->get_content();
	}

	/**
	 * @return bool|\WP_Post
	 */
	public function get_reference() {
		if ( is_null( $this->post ) ) {
			if ( $post = get_post( $this->get_reference_id() ) ) {
				$this->post = $post;
			} else {
				$this->post = false;
			}
		}

		return $this->post;
	}

	public function get_post() {
		return $this->get_reference();
	}

	public function get_post_id( $context = 'view' ) {
		return $this->get_reference_id( $context );
	}

	public function set_post_id( $id ) {
		$this->set_reference_id( $id );
	}

	public function get_title( $with_type = true ) {
		if ( $post = $this->get_post() ) {
			return apply_filters( "{$this->get_hook_prefix()}title", $post->post_title, $with_type, $this );
		} else {
			return __( 'Default Post Title', 'woocommerce-germanized-pro' );
		}
	}

	public function get_journal() {
		return false;
	}

	/**
	 * Generates a new filename for the document.
	 *
	 * @return string
	 */
	protected function generate_filename() {
		$filename = sanitize_title( $this->get_title() );

		return sanitize_file_name( $filename . '.pdf' );
	}

	public function get_content() {
		$content = '';

		if ( $page_post = $this->get_post() ) {
			$content = WC_germanized()->emails->get_email_plain_content( $page_post );
		}

		return apply_filters( "{$this->get_hook_prefix()}content", $content, $this );
	}

	/**
	 * Replaces revocation_form shortcut with a link to the revocation form
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function revocation_form_replacement( $atts ) {
		return '<a href="' . esc_url( wc_gzd_get_page_permalink( 'revocation' ) ) . '">' . __( 'Forward your withdrawal online', 'woocommerce-germanized-pro' ) . '</a>';
	}

	public function set_reference_id( $reference_id ) {
		parent::set_reference_id( $reference_id );

		$this->post = null;
	}
}