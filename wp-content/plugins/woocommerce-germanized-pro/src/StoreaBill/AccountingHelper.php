<?php

namespace Vendidero\Germanized\Pro\StoreaBill;

use Vendidero\OneStopShop\Package;
use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\ProductItem;
use Vendidero\StoreaBill\WooCommerce\Order;
use Vendidero\StoreaBill\WooCommerce\OrderItem;
use Vendidero\StoreaBill\WooCommerce\OrderItemProduct;

defined( 'ABSPATH' ) || exit;

class AccountingHelper {

	protected static $current_email_instance = false;

	public static function init() {
		$document_types = array( 'packing_slip', 'post_document', 'invoice', 'invoice_cancellation' );

		foreach( $document_types as $document_type ) {
			add_action( "storeabill_{$document_type}_default_template_after_company_address_header", array( __CLASS__, 'add_vat_id' ), 10 );
			add_action( "storeabill_{$document_type}_default_template_after_company_contact_header", array( __CLASS__, 'add_phone' ), 10 );
			add_action( "storeabill_{$document_type}_default_template_after_company_contact_footer", array( __CLASS__, 'add_phone' ), 10 );
			add_action( "storeabill_{$document_type}_default_template_after_totals", array( __CLASS__, 'add_small_business' ), 10 );

			// On adding new templates show prices incl or excl tax based on Woo settings
			add_filter( "storeabill_{$document_type}_default_template_prices_include_tax", array( __CLASS__, 'show_incl_tax' ), 5 );
		}

		add_filter( 'storeabill_document_shortcodes', array( __CLASS__, 'register_document_shortcodes' ), 10 );

		add_action( 'storeabill_woo_order_item_line_item_synced', array( __CLASS__, 'sync_order_item_product' ), 10, 2 );

		foreach( array( 'packing_slip', 'invoice', 'invoice_cancellation' ) as $document_type ) {
			add_filter( "storeabill_{$document_type}_preview_product_item_meta_types", array( __CLASS__, 'register_item_preview_meta' ), 10, 3 );
		}

		add_filter( 'storeabill_invoice_layout_help_link', array( __CLASS__, 'template_help_link' ), 10 );
		add_filter( 'storeabill_invoice_cancellation_layout_help_link', array( __CLASS__, 'template_help_link' ), 10 );
		add_filter( 'storeabill_invoice_finalize_help_link', array( __CLASS__, 'invoice_finalize_help_link' ), 10 );
		add_filter( 'storeabill_accounting_help_link', array( __CLASS__, 'accounting_help_link' ), 10, 2 );

        add_filter( 'storeabill_locate_theme_template_locations', array( __CLASS__, 'register_gzd_template_location' ), 10, 2 );
        add_filter( 'storeabill_email_template_path', array( __CLASS__, 'register_gzd_email_template_path' ) );

		/**
		 * Adjust email salutation based on Germanized settings
		 */
		add_filter( 'storeabill_email_document_customer_salutation', array( __CLASS__, 'adjust_email_salutation' ), 10, 3 );

		/**
		 * Adjust date of service of the order in case a shipment
		 * exists and has been sent
		 */
		add_filter( 'storeabill_woo_order_date_of_service', array( __CLASS__, 'order_date_of_service' ), 10, 2 );

		/**
		 * Voucher total
		 */
		add_filter( 'storeabill_woo_order_voucher_total', array( __CLASS__, 'order_voucher_total' ), 10, 2 );
		add_filter( 'storeabill_woo_order_voucher_tax', array( __CLASS__, 'order_voucher_tax' ), 10, 2 );
		add_filter( 'storeabill_woo_order_tax_display_mode', array( __CLASS__, 'order_voucher_tax_display_mode' ), 10, 2 );

		/**
		 * OSS Check
		 */
		add_filter( 'storeabill_woo_order_tax_is_oss', array( __CLASS__, 'order_tax_is_moss' ), 10, 3 );
		add_filter( 'storeabill_invoice_is_oss', array( __CLASS__, 'invoice_legacy_is_oss' ), 10, 2 );
		add_filter( 'storeabill_invoice_cancellation_is_oss', array( __CLASS__, 'invoice_legacy_is_oss' ), 10, 2 );
		add_filter( 'storeabill_woo_order_is_oss', array( __CLASS__, 'order_is_oss' ), 10, 2 );

		/**
		 * WC_GZD_Emails template name compatibility
		 */
		add_action( 'storeabill_before_template_part', array( __CLASS__, 'add_email_instance' ), 10, 4 );
		add_filter( 'woocommerce_gzd_current_email_instance', array( __CLASS__, 'set_current_email_instance' ), 10 );
		add_action( 'woocommerce_gzd_reset_email_instance', array( __CLASS__, 'reset_current_email_instance' ) );

		/**
		 * Disallow auto cancelling/billing certain legacy imported orders.
		 */
		add_filter( 'storeabill_woo_order_allow_auto_cancel', array( __CLASS__, 'maybe_skip_cancel_for_legacy_imports' ), 20, 3 );
		add_filter( 'storeabill_woo_order_needs_billing', array( __CLASS__, 'maybe_skip_billing_for_legacy_imports' ), 20, 3 );
		add_filter( 'storeabill_woo_order_needs_cancelling', array( __CLASS__, 'maybe_skip_cancelling_for_legacy_imports' ), 20, 2 );

		/**
		 * Maybe enable split tax calculation for additional costs
		 */
		add_filter( 'storeabill_woo_order_item_shipping_sync_props', array( __CLASS__, 'enable_item_split_tax' ), 10, 3 );
		add_filter( 'storeabill_woo_order_item_fee_sync_props', array( __CLASS__, 'enable_item_split_tax' ), 10, 3 );

		/**
		 * Maybe allow additional cost tax rounding
		 */
		add_filter( 'storeabill_woo_order_item_type_includes_tax', array( __CLASS__, 'maybe_treat_additional_costs_including_tax' ), 10, 3 );
		add_filter( 'storeabill_woo_order_item_type_round_tax_at_subtotal', array( __CLASS__, 'maybe_round_additional_costs_tax_at_subtotal' ), 10, 3 );
		add_filter( 'storeabill_woo_order_allow_round_split_taxes_at_subtotal', array( __CLASS__, 'maybe_allow_round_split_taxes_at_subtotal' ), 10, 2 );

		add_filter( 'storeabill_document_template_editor_asset_whitelist_paths', array( __CLASS__, 'register_asset_whitelist_paths' ), 10 );

		add_action( 'admin_notices', array( __CLASS__, 'accounting_disabled_notice' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'check_accounting_enable' ), 20 );

		/**
		 * Add additional format support, e.g. for encrypted direct debit meta data.
		 */
		add_filter( 'storeabill_format_shortcode_result', array( __CLASS__, 'improve_shortcode_format_support' ), 10, 4 );

		add_filter( 'storeabill_maybe_encrypt_sensitive_data', array( __CLASS__, 'maybe_encrypt' ), 10 );
		add_filter( 'storeabill_maybe_decrypt_sensitive_data', array( __CLASS__, 'maybe_decrypt' ), 10 );

		// Whitelist shipment emails
		add_filter( 'storeabill_woo_order_transactional_email_ids_whitelist', array( __CLASS__, 'register_additional_emails' ), 10 );

		/**
         * TM Product Options
         */
		add_filter( 'storeabill_woo_order_item_before_retrieve_attributes', array( __CLASS__, 'set_tm_admin_filter' ), 20 );
		add_filter( 'storeabill_woo_shipment_item_before_retrieve_attributes', array( __CLASS__, 'set_tm_admin_filter' ), 20 );

		add_filter( 'storeabill_woo_order_item_after_retrieve_attributes', array( __CLASS__, 'remove_tm_admin_filter' ), 20 );
		add_filter( 'storeabill_woo_shipment_item_after_retrieve_attributes', array( __CLASS__, 'remove_tm_admin_filter' ), 20 );

        add_filter( 'woocommerce_gzd_product_warranties_email_product_ids', array( __CLASS__, 'warranties_product_ids' ), 10, 3 );
	}

    public static function register_gzd_email_template_path() {
        return untrailingslashit( WC_germanized_pro()->template_path() );
    }

	/**
     * Allow storeabill templates to be overridden via the woocommerce-germanized-pro template path too.
     *
	 * @param string[] $template_locations
	 * @param string $template_name
	 *
	 * @return string[]
	 */
    public static function register_gzd_template_location( $template_locations, $template_name ) {
	    /**
	     * Do not override storeabill templates from woocommerce-germanized-pro template folder
         * because legacy v2 templates might be stored here.
	     */
        if ( ! get_option( 'wc_gzdp_invoice_simple' ) ) {
	        $template_locations[] = trailingslashit( WC_germanized_pro()->template_path() ) . $template_name;
        }

        return $template_locations;
    }

    public static function warranties_product_ids( $product_ids, $object, $mail_id ) {
        if ( is_a( $object, '\Vendidero\StoreaBill\Invoice\Invoice' ) ) {
            $product_ids = array();

            foreach( $object->get_items( 'product' ) as $product ) {
                $product_ids[] = $product->get_product_id();
            }
        } elseif ( is_a( $object, '\Vendidero\Germanized\Pro\StoreaBill\PackingSlip' ) ) {
	        $product_ids = array();

            foreach( $object->get_items( 'product' ) as $product ) {
                if ( $instance = $product->get_product() ) {
                    $product_ids[] = $instance->get_id();
                }
            }
        }

        return $product_ids;
    }

	public static function set_tm_admin_filter() {
	    if ( apply_filters( 'woocommerce_gzdp_suppress_epo_admin_filter', true ) ) {
		    add_filter( 'wc_epo_admin_in_shop_order', '__return_false', 99 );
        }
    }

	public static function remove_tm_admin_filter() {
		if ( apply_filters( 'woocommerce_gzdp_suppress_epo_admin_filter', true ) ) {
			remove_filter( 'wc_epo_admin_in_shop_order', '__return_false', 99 );
		}
	}

	/**
	 * @param $is_oss
	 * @param Invoice $invoice
	 */
	public static function invoice_legacy_is_oss( $is_oss, $invoice ) {
		/**
		 * This invoice version did not yet store the OSS status within it's dataset.
		 */
	    if ( version_compare( $invoice->get_version(), '1.7.1', '<' ) ) {
	        if ( class_exists( '\Vendidero\OneStopShop\Package' ) && Package::oss_procedure_is_enabled() && $invoice->is_eu_cross_border_taxable() ) {
	            $is_oss = true;
            }
        }

	    return $is_oss;
    }

	/**
	 * @param $is_oss
	 * @param \WC_Order $order
	 */
    public static function order_is_oss( $is_oss, $order ) {
	    /**
	     * In case the OSS procedure is enabled, allow this invoice to be part of the OSS regulation.
	     */
	    if ( $is_oss && class_exists( '\Vendidero\OneStopShop\Package' ) && Package::oss_procedure_is_enabled() ) {
	        $is_oss = true;
        } else {
	        $is_oss = false;
        }

	    return $is_oss;
    }

	public static function register_additional_emails( $emails ) {
		$emails[] = 'customer_shipment';

		return $emails;
	}

	public static function maybe_encrypt( $value ) {
		if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) ) {
			$encrypted = \WC_GZD_Secret_Box_Helper::encrypt( $value );

			if ( ! is_wp_error( $encrypted ) ) {
				$value = $encrypted;
			}
		}

		return $value;
	}

	public static function maybe_decrypt( $value ) {
		if ( class_exists( 'WC_GZD_Secret_Box_Helper' ) ) {
			$decrypted = \WC_GZD_Secret_Box_Helper::decrypt( $value );

			if ( ! is_wp_error( $decrypted ) ) {
				$value = $decrypted;
			}
		}

		return $value;
	}

	public static function improve_shortcode_format_support( $return_data, $format, $data, $atts ) {
		$maybe_encrypted = array(
			'direct_debit_iban',
			'direct_debit_bic'
		);

		if ( in_array( $atts['data'], $maybe_encrypted ) && ! empty( $return_data ) ) {
			$gateway = class_exists( 'WC_GZD_Gateway_Direct_Debit' ) ? new \WC_GZD_Gateway_Direct_Debit() : false;

			if ( $gateway && is_callable( array( $gateway, 'maybe_decrypt' ) ) ) {
				$return_data = $gateway->maybe_decrypt( $return_data );
			}
		}

		return $return_data;
	}

	public static function show_incl_tax( $incl_tax ) {
		return 'yes' === get_option( 'woocommerce_prices_include_tax' );
	}

	public static function check_accounting_enable() {
		if ( current_user_can( 'manage_woocommerce' ) && isset( $_GET['wc-gzdp-action'], $_GET['_wpnonce'] ) && 'activate-accounting' === $_GET['wc-gzdp-action'] && wp_verify_nonce( $_GET['_wpnonce'], 'wc-gzdp-activate-accounting' ) ) {
			update_option( 'woocommerce_gzdp_invoice_enable', 'yes' );

			wp_safe_redirect( remove_query_arg( array( 'wc-gzdp-action', '_wpnonce' ) ) );
		}
	}

	public static function accounting_disabled_notice() {
		if ( ! WC_germanized_pro()->enable_invoicing() && isset( $_GET['tab'] ) && ( $screen = get_current_screen() ) ) {
			if ( 'woocommerce_page_wc-settings' === $screen->id && 'germanized-storeabill' === $_GET['tab'] && current_user_can( 'manage_woocommerce' ) ) {
				$activation_link = wp_nonce_url( add_query_arg( array( 'wc-gzdp-action' => 'activate-accounting' ) ), 'wc-gzdp-activate-accounting' );

				echo '<div class="notice notice-error"><p>' . sprintf( __( 'The accounting feature is currently disabled. To edit PDF templates and settings, please <a href="%s">activate</a> the feature first.', 'woocommerce-germanized-pro' ), $activation_link ) . '</p></div>';
			}
		}
	}

	/**
	 * @param $props
	 * @param OrderItem $item
	 * @param $args
	 *
	 * @return mixed
	 */
	public static function enable_item_split_tax( $props, $item, $args ) {
		$enable_split_tax = self::enable_split_tax_calculation();

		if ( $order_item = $item->get_order_item() ) {
			if ( $order = $order_item->get_order() ) {
				if ( 'yes' === $order->get_meta( '_has_split_tax' ) ) {
					$enable_split_tax = true;
				} else {
					// Check whether item contains more than one tax item
					$taxes = $order_item->get_taxes();

					if ( sizeof( $taxes ) > 1 ) {
						$enable_split_tax = true;
					} else {
						$enable_split_tax = false;
					}
				}
			}
		}

		$props['enable_split_tax'] = $enable_split_tax;

		return $props;
	}

	/**
	 * Older versions of Germanized forced tax rounding per tax rate
	 * for additional costs such as shipping costs or fees. Newer versions include
	 * the _additional_costs_include_tax meta value within the corresponding order.
	 *
	 * @param boolean $allow
	 * @param Order $order
	 *
	 * @return bool
	 */
	public static function maybe_allow_round_split_taxes_at_subtotal( $allow, $order ) {
		if ( $order->get_meta( '_additional_costs_include_tax' ) ) {
			return true;
		} else {
			return $allow;
		}
	}

	protected static function enable_split_tax_calculation() {
		$enable_split_tax = function_exists( 'wc_gzd_enable_additional_costs_split_tax_calculation' ) ? wc_gzd_enable_additional_costs_split_tax_calculation() : 'yes' === get_option( 'woocommerce_gzd_shipping_tax' );

		return $enable_split_tax;
	}

	/**
     * Make sure to mark shipping costs to be tax-rounded at subtotal e.g. in case the overall
     * tax settings are set to excluded but additional costs do still include taxes due to a filter.
     *
	 * @param boolean $round_tax_at_subtotal
	 * @param string $order_item_type
	 * @param Order $order
	 *
	 * @return bool
	 */
	public static function maybe_round_additional_costs_tax_at_subtotal( $round_tax_at_subtotal, $order_item_type, $order ) {
		if ( in_array( $order_item_type, array( 'shipping', 'fee' ) ) && self::enable_split_tax_calculation() ) {
            if ( self::maybe_treat_additional_costs_including_tax( true, $order_item_type, $order ) ) {
                $round_tax_at_subtotal = true;
            }
		}

		return $round_tax_at_subtotal;
	}

	/**
	 * @param boolean $includes_tax
	 * @param string $order_item_type
	 * @param Order $order
	 *
	 * @return bool
	 */
	public static function maybe_treat_additional_costs_including_tax( $includes_tax, $order_item_type, $order ) {
		if ( in_array( $order_item_type, array( 'shipping', 'fee' ) ) && self::enable_split_tax_calculation() ) {
			/**
			 * This meta is included in newer versions of Germanized (>= 3.3.4). In earlier versions
			 * additional costs were always treated including taxes regardless of whether tax options.
			 */
			$meta = $order->get_meta( '_additional_costs_include_tax' );

			if ( ! empty( $meta ) ) {
				$includes_tax = wc_string_to_bool( $meta );
			} else {
				$includes_tax = true;
			}
		}

		return $includes_tax;
	}

	public static function register_asset_whitelist_paths( $paths ) {
		$paths = array_merge( $paths, array(
			'plugins/woocommerce-germanized-pro',
			'plugins/woocommerce-germanized'
		) );

		return $paths;
	}

	/**
	 * @param boolean $needs_cancelling
	 * @param Order $sab_order
	 */
	public static function maybe_skip_cancelling_for_legacy_imports( $needs_cancelling, $sab_order ) {
		$order               = $sab_order->get_order();
		$legacy_invoice_meta = $order->get_meta( '_invoices', true );

		if ( $legacy_invoice_meta ) {
			foreach( $sab_order->get_documents() as $document ) {
				if ( 'wc_gzdp_legacy_import' === $document->get_created_via() ) {
					$needs_cancelling = false;
					break;
				}
			}
		}

		return $needs_cancelling;
	}

	/**
	 * @param boolean $allow_cancel
	 * @param \WC_Order $order
	 * @param Order $sab_order
	 */
	public static function maybe_skip_billing_for_legacy_imports( $needs_billing, $order, $sab_order ) {
		$legacy_invoice_meta = $order->get_meta( '_invoices', true );

		if ( $legacy_invoice_meta ) {
			foreach( $sab_order->get_documents() as $document ) {
				if ( '0.0.1-legacy-incomplete' === $document->get_meta( '_legacy_version' ) && 'cancelled' !== $document->get_status() ) {
					$needs_billing = false;
					break;
				}
			}
		}

		return $needs_billing;
	}

	/**
	 * @param boolean $allow_cancel
	 * @param \WC_Order $order
	 * @param Order $sab_order
	 */
	public static function maybe_skip_cancel_for_legacy_imports( $allow_cancel, $order, $sab_order ) {
		$legacy_invoice_meta = $order->get_meta( '_invoices', true );

		if ( $legacy_invoice_meta ) {
			foreach( $sab_order->get_documents() as $document ) {
				if ( 'wc_gzdp_legacy_import' === $document->get_created_via() ) {
					$allow_cancel = false;
					break;
				}
			}
		}

		return $allow_cancel;
	}

	/**
	 * Decide (based on tax class and order data) whether this tax item
	 * is a MOSS tax item or not.
	 *
	 * @param boolean $is_oss
	 * @param \WC_Order_Item_Tax $tax
	 * @param Order $order
	 */
	public static function order_tax_is_moss( $is_oss, $tax, $order ) {
		if ( ! $is_oss && ( $tax_rate_id = $tax->get_rate_id() ) ) {
			$tax_rate             = \WC_Tax::_get_tax_rate( $tax_rate_id );
			$virtual_rate_classes = array( 'virtual-rate', 'virtual-reduced-rate' );

			if ( $tax_rate && in_array( $tax_rate['tax_rate_class'], $virtual_rate_classes ) ) {
				$country  = $order->get_taxable_country();
				$postcode = $order->get_taxable_postcode();

				if ( ! $order->is_reverse_charge() && ( $country !== Countries::get_base_country() && Countries::is_eu_vat_country( $country, $postcode ) ) ) {
					$is_oss = true;
				}
			}
		}

		return $is_oss;
	}

	public static function reset_current_email_instance() {
		self::$current_email_instance = false;
	}

	public static function set_current_email_instance( $instance ) {
		if ( self::$current_email_instance ) {
			$instance = self::$current_email_instance;
		}

		return $instance;
	}

	public static function add_email_instance( $template_name, $template_path, $located, $args ) {
		if ( isset( $args['email'] ) && is_a( $args['email'], 'WC_Email' ) ) {
			self::$current_email_instance = $args['email'];
		}
	}

	/**
	 * @param $total
	 * @param \WC_Order $order
	 */
	public static function order_voucher_total( $total, $order ) {
		return self::get_order_voucher_total( $order );
	}

	/**
	 * @param $total
	 * @param \WC_Order $order
	 */
	public static function order_voucher_tax( $total, $order ) {
		return self::get_order_voucher_tax( $order );
	}

	/**
	 * @param $tax_display_mode
	 * @param \WC_Order $order
	 */
	public static function order_voucher_tax_display_mode( $tax_display_mode, $order ) {
		if ( ! $order ) {
			return $tax_display_mode;
		}

		if ( $coupons = $order->get_items( 'coupon' ) ) {
			foreach ( $coupons as $coupon ) {
				if ( $coupon->get_meta( 'tax_display_mode', true ) ) {
					$tax_display_mode = $coupon->get_meta( 'tax_display_mode', true );
					break;
				}
			}
		}

		return $tax_display_mode;
	}

	/**
	 * @param \WC_Order $order
	 */
	protected static function get_order_voucher_total( $order, $inc_tax = true ) {
		if ( ! $order ) {
			return 0;
		}

		$total = 0;

		if ( $coupons = $order->get_items( 'coupon' ) ) {
			foreach ( $coupons as $coupon ) {
				if ( 'yes' === $coupon->get_meta( 'is_voucher', true ) ) {
					$total += $coupon->get_discount();

					if ( $inc_tax ) {
						$total += $coupon->get_discount_tax();
					}
				}
			}
		}

		return wc_format_decimal( $total );
	}

	/**
	 * @param \WC_Order $order
	 */
	protected static function get_order_voucher_tax( $order ) {
		$total = 0;

		if ( $coupons = $order->get_items( 'coupon' ) ) {
			foreach ( $coupons as $coupon ) {
				if ( 'yes' === $coupon->get_meta( 'is_voucher', true ) ) {
					$total += $coupon->get_discount_tax();
				}
			}
		}

		return wc_format_decimal( $total );
	}

	/**
	 * @param \WC_DateTime $date_of_service
	 * @param Order $order
	 */
	public static function order_date_of_service( $date_of_service, $order ) {
		if ( $shipment_order = wc_gzd_get_shipment_order( $order->get_order() ) ) {
			$date_shipped = $shipment_order->get_date_shipped();

			if ( $date_shipped ) {
				return $date_shipped;
			}
		}

		return $date_of_service;
	}

	/**
	 * @param Document $document
	 * @param $email
	 */
	public static function adjust_email_salutation( $salutation, $document, $email ) {
		if ( ! apply_filters( 'woocommerce_gzdp_replace_storeabill_email_salutation', true, $email ) ) {
			return $salutation;
		}

		$title_text      = get_option( 'woocommerce_gzd_email_title_text' );
		$title_formatted = '';
		$address_data    = $document->get_address();

		if ( ! empty( $address_data['title'] ) ) {
			if ( is_numeric( $address_data['title'] ) ) {
				$title_formatted = wc_gzd_get_customer_title( $address_data['title'] );
			} else {
				$title_formatted =$address_data['title'];
			}
		}

		$title_options = array(
			'{first_name}' => $document->get_first_name(),
			'{last_name}'  => $document->get_last_name(),
			'{title}'      => $title_formatted,
		);

		$salutation = str_replace( array_keys( $title_options ), array_values( $title_options ), $title_text );

		return $salutation;
	}

	public static function register_document_shortcodes( $shortcodes ) {
		$shortcodes['small_business_info'] = array( __CLASS__, 'small_business_shortcode' );

		return $shortcodes;
	}

	public static function small_business_shortcode( $atts ) {
		$return = '';

		if ( wc_gzd_is_small_business() ) {
			ob_start();
			wc_get_template( 'global/small-business-info.php' );
			$return = strip_tags( ob_get_clean() );
		}

		return apply_filters( 'woocommerce_gzdp_shortcode_small_business', $return, $atts );
	}

	public static function accounting_help_link( $link, $section ) {
		if ( empty( $section ) ) {
			return 'https://vendidero.de/dokumentation/woocommerce-germanized/buchhaltung-rechnungen';
		}

		return $link;
	}

	public static function invoice_finalize_help_link() {
		return 'https://vendidero.de/dokument/rechnungen-festschreiben';
	}

	public static function template_help_link() {
		return 'https://vendidero.de/dokument/pdf-vorlagen-bearbeiten';
	}

	/**
	 * @param array $meta
	 * @param ProductItem|\Vendidero\Germanized\Pro\StoreaBill\PackingSlip\ProductItem|boolean $item
	 * @param Invoice|PackingSlip $preview
	 *
	 * @return array
	 */
	public static function register_item_preview_meta( $meta, $item, $preview ) {
		$unit_price      = '';
		$unit_price_excl = '';
		$product_units   = '';

		if ( $item ) {
			$unit_price = wc_gzd_format_unit_price( $preview->get_formatted_price( $item->get_price_subtotal() ), wc_gzd_format_unit( _x( 'kg', 'unit', 'woocommerce-germanized-pro' ) ), wc_gzd_format_unit_base( 1 ) );

			if ( is_callable( array( $item, 'get_price_subtotal_net' ) ) ) {
				$unit_price_excl = wc_gzd_format_unit_price( $preview->get_formatted_price( $item->get_price_subtotal_net() ), wc_gzd_format_unit( _x( 'kg', 'unit', 'woocommerce-germanized-pro' ) ), wc_gzd_format_unit_base( 1 ) );
			}

			$text = get_option( 'woocommerce_gzd_product_units_text' );

			$replacements = array(
				'{product_units}' => str_replace( '.', ',', 1 ),
				'{unit}'          => wc_gzd_format_unit( _x( 'kg', 'unit', 'woocommerce-germanized-pro' ) ),
				'{unit_price}'    => $unit_price,
			);

			$product_units = wc_gzd_replace_label_shortcodes( $text, $replacements );
		}

		$delivery_time_html = wc_gzd_replace_label_shortcodes( get_option( 'woocommerce_gzd_delivery_time_text' ), array( '{delivery_time}' => __( '3-4 days', 'woocommerce-germanized-pro' ) ) );

		$meta = array_merge( $meta, array(
			array(
				'title'   => __( 'Unit Price', 'woocommerce-germanized-pro' ),
				'preview' => $unit_price,
				'icon'    => '',
				'type'    => 'unit_price'
			),
			array(
				'title'   => __( 'Cart Description', 'woocommerce-germanized-pro' ),
				'preview' => '<p>' . __( 'Just an item cart description.', 'woocommerce-germanized-pro' ) . '</p>',
				'icon'    => '',
				'type'    => 'cart_desc'
			),
			array(
				'title'   => __( 'Product Units', 'woocommerce-germanized-pro' ),
				'preview' => $product_units,
				'icon'    => '',
				'type'    => 'product_units'
			),
			array(
				'title'   => __( 'Delivery Time', 'woocommerce-germanized-pro' ),
				'preview' => $delivery_time_html,
				'icon'    => '',
				'type'    => 'delivery_time'
			),
		) );

		if ( in_array( $preview->get_type(), array( 'invoice', 'invoice_cancellation' ) ) ) {
			$meta = array_merge( $meta, array(
				array(
					'title'   => __( 'Unit Price (excl. tax)', 'woocommerce-germanized-pro' ),
					'preview' => $unit_price_excl,
					'icon'    => '',
					'type'    => 'unit_price_excl'
				)
			) );
		}

		return $meta;
	}

	/**
	 * @param OrderItemProduct $item
	 * @param ProductItem $document_item
	 */
	public static function sync_order_item_product( $item, $document_item ) {
		if ( $order_item = wc_gzd_get_order_item( $item->get_order_item() ) ) {
			$document_item->update_meta_data( '_unit_price', $order_item->get_formatted_unit_price() );
			$document_item->update_meta_data( '_unit_price_excl', $order_item->get_formatted_unit_price( false ) );
			$document_item->update_meta_data( '_cart_desc', $order_item->get_cart_description() );
			$document_item->update_meta_data( '_delivery_time', $order_item->get_delivery_time() );
			$document_item->update_meta_data( '_product_units', $order_item->get_formatted_product_units() );
			$document_item->update_meta_data( '_unit', $order_item->get_formatted_unit() );

			$woo_order_item = $item->get_order_item();

			if ( is_a( $woo_order_item, 'WC_Order_Item_Product' ) && ( $product = wc_gzd_get_product( $item->get_order_item()->get_product() ) ) ) {
				$document_item->set_has_differential_taxation( $product->is_differential_taxed() ? true : false );
			}
		}
	}

	public static function add_small_business() {
		if ( wc_gzd_is_small_business() ) {
			?>
			<!-- wp:paragraph -->
			<p class="has-text-align-left">[small_business_info]</p>
			<!-- /wp:paragraph -->
			<?php
		}
	}

	public static function add_phone() {
		$phone = get_option( 'widerruf_v2_telefon' );

		if ( ! empty( $phone ) ) {
			printf( _x( 'Phone: %s', 'storeabill-template', 'woocommerce-germanized-pro' ) . '<br>', trim( $phone ) );
		}
	}

	public static function add_vat_id() {
		$vat_id = get_option( 'woocommerce_gzdp_vat_requester_vat_id' );

		if ( ! empty( $vat_id ) ) {
			printf( _x( 'VAT ID: %s', 'storeabill-template', 'woocommerce-germanized-pro' ) . '<br>', $vat_id );
		}
	}
}