<?php

namespace Vendidero\StoreaBill\Compatibility;

use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\FirstPageTemplate;
use Vendidero\StoreaBill\Document\Template;
use Vendidero\StoreaBill\Interfaces\Compatibility;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\WooCommerce\Order;

defined( 'ABSPATH' ) || exit;

class WPML implements Compatibility {

	protected static $old_language = false;

	protected static $new_language = false;

	protected static $email_lang = false;

	protected static $email_old_lang = false;

	protected static $email_locale = false;

	public static function is_active() {
		return class_exists( 'SitePress' );
	}

	public static function init() {
		add_filter( 'wpml_link_to_translation', array( __CLASS__, 'adjust_document_template_translation_link' ), 10, 4 );
		add_action( 'storeabill_before_create_new_document_template', array( __CLASS__, 'maybe_copy_template' ), 10, 3 );

		/**
		 * Make sure while syncing documents with order - translate order data (e.g. items).
		 */
		add_action( 'storeabill_woo_order_before_sync_invoice', array( __CLASS__, 'maybe_switch_order_lang' ), 10, 1 );
		add_action( 'storeabill_woo_order_after_sync_invoice', array( __CLASS__, 'maybe_restore_order_language' ), 10, 1 );
		add_action( 'storeabill_woo_order_synced_invoice', array( __CLASS__, 'sync_invoice_language' ), 10, 2 );

		/**
		 * Maybe switch language while rendering the invoice to make sure translated templates are loaded
		 * in the language chosen for the current document.
		 */
		add_action( 'storeabill_before_render_document', array( __CLASS__, 'maybe_switch_render_lang' ), 10, 2 );
		add_action( 'storeabill_after_render_document', array( __CLASS__, 'maybe_restore_render_lang' ), 10, 2 );

		/**
		 * Translate the current template id.
		 */
		add_filter( 'storeabill_default_document_template_id', array( __CLASS__, 'filter_default_template_id' ), 10 );

		// Setup and restore email customer locale
		add_action( 'storeabill_switch_email_locale', array( __CLASS__, 'setup_email_locale' ), 10, 2 );
		add_action( 'storeabill_restore_email_locale', array( __CLASS__, 'restore_email_locale' ), 10, 1 );

		// Add compatibility with email string translation by WPML
		add_filter( 'wcml_emails_options_to_translate', array( __CLASS__, 'register_email_options' ), 10, 1 );
		add_filter( 'wcml_emails_section_name_to_translate', array( __CLASS__, 'filter_email_section' ), 10, 2 );

		add_filter( 'storeabill_email_setup_locale', '__return_false' );
		add_filter( 'storeabill_email_restore_locale', '__return_false' );
	}

	/**
	 * Returns whether to translate invoices (to the language chosen by the customer) or not.
	 *
	 * @return boolean
	 */
	public static function translate_documents( $document_type ) {
		return apply_filters( "storeabill_wpml_translate_{$document_type}", true );
	}

	/**
	 * Setup email locale based on customer.
	 *
	 * @param \WC_Email       $email
	 * @param string|boolean $lang
	 */
	public static function setup_email_locale( $email, $lang ) {
		global $sitepress;

		$object = $email->object;

		if ( ! $email->is_customer_email() ) {
			// Lets check the recipients language
			$recipients = explode( ',', $email->get_recipient() );

			foreach ( $recipients as $recipient ) {
				$user = get_user_by( 'email', $recipient );

				if ( $user ) {
					$lang = $sitepress->get_user_admin_language( $user->ID, true );
				} else {
					$lang = $sitepress->get_default_language();
				}
			}
		} else {
			if ( $object ) {
				if ( is_a( $object, 'Vendidero\StoreaBill\Document\Document' ) ) {
					$lang = $object->get_meta( '_wpml_language', true );
				}
			}
		}

		$lang = apply_filters( 'storeabill_wpml_email_lang', $lang, $email );

		if ( ! empty( $lang ) ) {
			self::$email_lang = $lang;

			add_filter( 'plugin_locale', array( __CLASS__, 'filter_email_locale' ), 50 );
			add_filter( 'wcml_email_language', array( __CLASS__, 'filter_email_lang' ), 10 );

			self::switch_email_lang( $lang );

			/*
			 * Reload email settings to make sure that translated strings are loaded from DB.
			 * This must happen before get_subject() and get_heading() etc. is called - therefore before triggering
			 * the send method.
			 */
			$email->init_settings();

			/**
			 * Manually adjust subject + heading option which does seem to cause problems
			 * for custom emails such as invoice and cancellation email.
			 */
			if ( $subject = self::translate_email_setting( $email->id, 'subject' ) ) {
				$email->settings['subject'] = $subject;
			}

			if ( $heading = self::translate_email_setting( $email->id, 'heading' ) ) {
				$email->settings['heading'] = $heading;
			}

			do_action( 'storeabill_wpml_switched_email_language', self::$email_lang, $email );
		}
	}

	/**
	 * Restore email locale after successfully sending the email
	 */
	public static function restore_email_locale() {
		global $sitepress;

		if ( self::$email_locale ) {
			$old_lang = self::$email_old_lang ? self::$email_old_lang : $sitepress->get_default_language();

			$sitepress->switch_lang( $old_lang );

			remove_filter( 'plugin_locale', array( __CLASS__, 'filter_email_locale' ), 50 );
			remove_filter( 'wcml_email_language', array( __CLASS__, 'filter_email_lang' ), 10 );

			self::$email_lang     = false;
			self::$email_old_lang = false;
			self::$email_locale   = false;

			self::reload_locale();
		}
	}

	protected static function translate_email_setting( $email_id, $option_name = 'heading' ) {
		global $woocommerce_wpml;

		if ( ! is_callable( array( $woocommerce_wpml->emails, 'wcml_get_translated_email_string' ) ) ) {
			return false;
		}

		$domain     = 'admin_texts_woocommerce_' . $email_id . '_settings';
		$namePrefix = '[woocommerce_' . $email_id . '_settings]';

		return $woocommerce_wpml->emails->wcml_get_translated_email_string( $domain, $namePrefix . $option_name, false, self::$email_lang );
	}

	/**
	 * Switch current email to a certain language by reloading locale and triggering Woo WPML.
	 *
	 * @param $lang
	 */
	protected static function switch_email_lang( $lang ) {
		global $woocommerce_wpml, $sitepress;

		$current_language = $sitepress->get_current_language();

		if ( empty( $current_language ) ) {
			$current_language = $sitepress->get_default_language();
		}

		self::$email_old_lang = $current_language;

		if ( isset( $woocommerce_wpml->emails ) && is_callable( array( $woocommerce_wpml->emails, 'change_email_language' ) ) ) {
			$woocommerce_wpml->emails->change_email_language( $lang );

			self::$email_locale = $sitepress->get_locale( $lang );
			self::reload_locale();
		}
	}

	/**
	 * Filters the Woo WPML email language based on a global variable.
	 *
	 * @param $lang
	 */
	public static function filter_email_lang( $p_lang ) {
		if ( self::$email_lang && ! empty( self::$email_lang ) ) {
			$p_lang = self::$email_lang;
		}

		return $p_lang;
	}

	/**
	 * Force the locale to be filtered while changing email language.
	 *
	 * @param $locale
	 */
	public static function filter_email_locale( $locale ) {
		if ( self::$email_locale && ! empty( self::$email_locale ) ) {
			$locale = self::$email_locale;
		}

		return $locale;
	}

	/**
	 * This filter makes sure that we are using the translated default template ID
	 * to render documents.
	 *
	 * @param $template_id
	 *
	 * @return mixed|void
	 */
	public static function filter_default_template_id( $template_id ) {
		$filtered_template_id = apply_filters( 'wpml_object_id', $template_id, 'post' );

		if ( ! empty( $filtered_template_id ) && get_post( $filtered_template_id ) ) {
			return $filtered_template_id;
		}

		return $template_id;
	}

	/**
	 * @param Document $document
	 * @param $is_preview
	 */
	public static function maybe_switch_render_lang( $document, $is_preview ) {
		if ( ! self::translate_documents( $document->get_type() ) ) {
			return;
		}

		if ( ( $lang = $document->get_meta( '_wpml_language', true ) ) && ! empty( $lang ) ) {
			self::switch_language( $lang );
		}
	}

	/**
	 * @param Document $document
	 * @param $is_preview
	 */
	public static function maybe_restore_render_lang( $document ) {
		if ( ! self::translate_documents( $document->get_type() ) ) {
			return;
		}

		if ( ( $lang = $document->get_meta( '_wpml_language', true ) ) && ! empty( $lang ) ) {
			self::restore_language();
		}
	}

	protected static function get_order_language( $order ) {
		$order_id = is_numeric( $order ) ? $order : $order->get_id();

		/**
		 * Prefer get_post_meta over $order->get_meta due to
		 * meta cache issues in case Woo multilingual still uses update_post_meta
		 * instead of using getter methods of the order.
		 */
		return get_post_meta( $order_id, 'wpml_language', true );
	}

	/**
	 * @param Order $order
	 */
	public static function maybe_switch_order_lang( $order ) {
		if ( ! self::translate_documents( 'invoice' ) ) {
			return;
		}

		$lang = self::get_order_language( $order );

		if ( ! empty( $lang ) ) {
			self::switch_language( $lang );
		}
	}

	/**
	 * @param Invoice $invoice
	 * @param Order   $order
	 */
	public static function sync_invoice_language( $invoice, $order ) {
		if ( ! self::translate_documents( 'invoice' ) ) {
			return;
		}

		$lang = self::get_order_language( $order );

		if ( ! empty( $lang ) ) {
			$invoice->update_meta_data( '_wpml_language', $lang );
		}
	}

	/**
	 * @param Order $order
	 */
	public static function maybe_restore_order_language( $order ) {
		if ( ! self::translate_documents( 'invoice' ) ) {
			return;
		}

		$lang = self::get_order_language( $order );

		if ( ! empty( $lang ) ) {
			self::restore_language();
		}
	}

	protected static function get_emails() {
		return apply_filters( 'storeabill_wpml_email_ids', array(
			'\Vendidero\StoreaBill\Emails\SimpleInvoice'       => 'sab_simple_invoice',
			'\Vendidero\StoreaBill\Emails\CancellationInvoice' => 'sab_cancellation_invoice',
			'\Vendidero\StoreaBill\Emails\Document'            => 'sab_document',
			'\Vendidero\StoreaBill\Emails\DocumentAdmin'       => 'sab_document_admin',
		) );
	}

	protected static function get_email_options() {
		$email_options = array();

		foreach( self::get_emails() as $key => $email_id ) {
			$email_options[ $key ] = 'woocommerce_' . $email_id . '_settings';
		}

		return $email_options;
	}

	public static function register_email_options( $options ) {
		$email_options = self::get_email_options();

		return array_merge( $options, $email_options );
	}

	public static function filter_email_section( $name ) {
		if ( strpos( $name, 'sab_' ) !== false ) {
			$name = str_replace( 'wc_email_sab_', 'storeabill_', $name );
			$name = str_replace( '_', '', $name );
			$name = str_replace( 'storeabill', 'storeabill_', $name );
		}

		return $name;
	}

	/**
	 * Copy template data from parent in case a translation of a document template is added.
	 *
	 * @param Template $template
	 * @param $post_id
	 * @param $post
	 */
	public static function maybe_copy_template( $template, $post_id, $post ) {
		if ( isset( $_GET['trid'] ) ) {
			$original_id      = absint( $_GET['trid'] );
			$original_post_id = \SitePress::get_original_element_id_by_trid( $original_id );

			/**
			 * Prevent infinite loops while saving custom first page templates.
			 */
			remove_action( 'storeabill_before_create_new_document_template', array( __CLASS__, 'maybe_copy_template' ), 10 );

			if ( $original_template = sab_get_document_template( $original_post_id ) ) {
				$original_data = $original_template->get_data();

				unset( $original_data['date_created'] );
				unset( $original_data['date_modified'] );
				unset( $original_data['status'] );
				unset( $original_data['id'] );
				unset( $original_data['parent_id'] );

				$template->set_props( $original_data );
				$template->set_content( $original_template->get_content() );
				$template->set_status( 'draft' );

				if ( ! $original_template->is_first_page() && $original_template->has_custom_first_page() ) {
					if ( $original_first_page = $original_template->get_first_page() ) {
						$first_page    = new FirstPageTemplate();
						$original_data = $original_first_page->get_data();

						unset( $original_data['date_created'] );
						unset( $original_data['date_modified'] );
						unset( $original_data['status'] );
						unset( $original_data['id'] );
						unset( $original_data['parent_id'] );

						$first_page->set_props( $original_data );
						$first_page->set_content( $original_first_page->get_content() );
						$first_page->set_status( 'draft' );
						$first_page->set_parent_id( $post_id );
						$first_page->save();
					}
				}
			}
		}
	}

	/**
	 * Add the document type to a translation link.
	 *
	 * @param $link
	 * @param $post_id
	 * @param $lang
	 * @param $trid
	 *
	 * @return string
	 */
	public static function adjust_document_template_translation_link( $link, $post_id, $lang, $trid ) {
		if ( $post = get_post( $post_id ) ) {
			if ( 'document_template' === $post->post_type ) {
				if ( $template = sab_get_document_template( $post_id ) ) {
					$link = add_query_arg( array( 'document_type' => $template->get_document_type() ), $link );
				}
			}
		}

		return $link;
	}

	public static function switch_language( $lang, $set_default = false ) {
		global $sitepress;

		if ( $set_default ) {
			self::$old_language = $lang;
		} elseif ( ! self::$old_language || empty( self::$old_language ) ) {
			// Make sure default language is stored within global to ensure reset works
			if ( is_callable( array( $sitepress, 'get_current_language' ) ) ) {
				self::$old_language = $sitepress->get_current_language();
			}
		}

		if ( isset( $sitepress ) && is_callable( array( $sitepress, 'get_current_language' ) ) && is_callable( array( $sitepress, 'switch_lang' ) ) ) {

			if ( $sitepress->get_current_language() != $lang ) {
				self::$new_language = $lang;
			}

			$sitepress->switch_lang( $lang, true );

			// Somehow WPML doesn't automatically change the locale
			if ( is_callable( array( $sitepress, 'reset_locale_utils_cache' ) ) ) {
				$sitepress->reset_locale_utils_cache();
			}

			// Filter locale because WPML does still use the user locale within admin panel
			add_filter( 'locale', array( __CLASS__, 'language_locale_filter' ), 50 );

			if ( function_exists( 'switch_to_locale' ) ) {
				switch_to_locale( get_locale() );

				// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
				add_filter( 'plugin_locale', 'get_locale' );

				self::reload_locale();
			}

			add_filter( 'woocommerce_order_item_get_name', array( __CLASS__, 'order_item_name_filter' ), 10, 2 );
			add_filter( 'woocommerce_order_item_get_product_id', array( __CLASS__, 'order_item_product_id_filter' ), 10, 2 );
			add_filter( 'woocommerce_order_item_get_variation_id', array( __CLASS__, 'order_item_variation_id_filter' ), 10, 2 );
			add_filter( 'woocommerce_order_item_display_meta_value', array( __CLASS__, 'order_item_variation_attribute_filter' ), 10, 3 );

			do_action( 'storeabill_wpml_language_switched', $lang, self::$old_language );
		}
	}

	/**
	 * @param \WC_Order_Item $item
	 * @param $lang
	 *
	 * @return false|\WC_Product
	 */
	protected static function get_order_item_product( $item, $lang ) {
		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			$translated_product_id   = apply_filters( 'translate_object_id', self::get_item_product_id( $item ), 'product', false, $lang );
			$translated_variation_id = apply_filters( 'translate_object_id', $item->get_variation_id(), 'product_variation', false, $lang );

			if ( ! is_null( $translated_variation_id ) ) {
				return wc_get_product( $translated_variation_id );
			} elseif ( ! is_null( $translated_product_id ) ) {
				return wc_get_product( $translated_product_id );
			}
		}

		return false;
	}

	public static function order_item_product_id_filter( $product_id, $item ) {
		global $sitepress;

		$lang = $sitepress->get_current_language();

		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			$parent_product_id = $product_id;

			if ( 'product_variation' === get_post_type( $parent_product_id ) ) {
				$parent_product_id = wp_get_post_parent_id( $parent_product_id );
			}

			$translated_product_id = apply_filters( 'translate_object_id', $parent_product_id, 'product', false, $lang );

			if ( ! is_null( $translated_product_id ) ) {
				return $translated_product_id;
			}
		}

		return $product_id;
	}

	public static function order_item_variation_id_filter( $variation_id, $item ) {
		global $sitepress;

		$lang = $sitepress->get_current_language();

		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			$translated_variation_id = apply_filters( 'translate_object_id', $variation_id, 'product_variation', false, $lang );

			if ( ! is_null( $translated_variation_id ) ) {
				return $translated_variation_id;
			}
		}

		return $variation_id;
	}

	public static function order_item_name_filter( $name, $item ) {
		global $sitepress, $woocommerce_wpml;

		$lang = $sitepress->get_current_language();

		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			if ( $product = self::get_order_item_product( $item, $lang ) ) {
				return $product->get_title();
			}
		} elseif ( is_a( $item, 'WC_Order_Item_Shipping' ) && is_callable( array( $woocommerce_wpml->shipping, 'translate_shipping_method_title' ) ) ) {
			$shipping_id = $item->get_method_id();

			if ( $shipping_id ) {
				if ( method_exists( $item, 'get_instance_id' ) ) {
					$shipping_id .= $item->get_instance_id();
				}

				return $woocommerce_wpml->shipping->translate_shipping_method_title( $item->get_method_title(), $shipping_id, $lang );
			}
		}

		return $name;
	}

	public static function order_item_variation_attribute_filter( $display_value, $meta, $item ) {
		global $sitepress, $woocommerce_wpml;

		$lang = $sitepress->get_current_language();

		if ( is_a( $item, 'WC_Order_Item_Product' ) ) {
			$variation_id = $item->get_variation_id();

			if ( $variation_id ) {
				$taxonomy = substr( $meta->key, 0, 3 ) !== 'pa_' ? wc_attribute_taxonomy_name( $meta->key ) : $meta->key;

				if ( is_callable( array( $woocommerce_wpml->terms, 'wcml_get_term_id_by_slug' ) ) && is_callable( array( $woocommerce_wpml->terms, 'wcml_get_translated_term' ) ) ) {
					$term_id         = $woocommerce_wpml->terms->wcml_get_term_id_by_slug( $taxonomy, $meta->value );
					$translated_term = $woocommerce_wpml->terms->wcml_get_translated_term( $term_id, $taxonomy, $lang );

					if ( $translated_term ) {
						return $translated_term->name;
					}
				}
			}
		}

		return $display_value;
	}

	/**
	 * @param \WC_Order_Item_Product $item
	 *
	 * @return false|int
	 */
	private static function get_item_product_id( $item ) {
		$item_product_id = $item->get_product_id();

		if ( 'product_variation' === get_post_type( $item_product_id ) ) {
			$item_product_id = wp_get_post_parent_id( $item_product_id );
		}

		return $item_product_id;
	}

	public static function language_locale_filter( $default ) {
		global $sitepress;

		if ( self::$new_language && ! empty( self::$new_language ) ) {
			if ( isset( $sitepress ) && is_callable( array( $sitepress, 'get_locale' ) ) ) {
				return $sitepress->get_locale( self::$new_language );
			}
		}

		return $default;
	}

	/**
	 * Reload locale
	 */
	protected static function reload_locale() {
		unload_textdomain( 'default' );
		unload_textdomain( 'woocommerce' );

		// Init WC locale.
		WC()->load_plugin_textdomain();

		Package::load_plugin_textdomain();

		load_default_textdomain( get_locale() );

		do_action( 'storeabill_reload_locale' );

		/**
		 * Force reloading document types to enable label translation.
		 */
		global $sab_document_types;
		$sab_document_types = array();

		Package::register_document_types();
	}

	public static function restore_language() {
		if ( self::$old_language && ! empty( self::$old_language ) ) {
			$old_language = self::$old_language;
			$new_language = self::$new_language;

			self::switch_language( self::$old_language );
			self::$new_language = false;

			remove_filter( 'locale', array( __CLASS__, 'language_locale_filter' ), 50 );
			remove_filter( 'woocommerce_order_get_items', array( __CLASS__, 'filter_order_items_language' ), 10 );

			remove_filter( 'woocommerce_order_item_get_name', array( __CLASS__, 'order_item_name_filter' ), 10 );
			remove_filter( 'woocommerce_order_item_get_product_id', array( __CLASS__, 'order_item_product_id_filter' ), 10 );
			remove_filter( 'woocommerce_order_item_get_variation_id', array( __CLASS__, 'order_item_variation_id_filter' ), 10 );
			remove_filter( 'woocommerce_order_item_display_meta_value', array( __CLASS__, 'order_item_variation_attribute_filter' ), 10 );

			do_action( 'storeabill_restored_language', $old_language, $new_language );
		}
	}
}