<?php
/**
 * Invoice
 *
 * @package Vendidero/StoreaBill
 * @version 1.0.0
 */
namespace Vendidero\StoreaBill\Invoice;

use Exception;
use ReflectionMethod;
use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\Document\Document;
use Vendidero\StoreaBill\Document\Total;
use Vendidero\StoreaBill\Interfaces\Discountable;
use Vendidero\StoreaBill\Interfaces\Order;
use Vendidero\StoreaBill\Interfaces\TotalsContainable;
use Vendidero\StoreaBill\Document\TaxTotal;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\Tax;
use Vendidero\StoreaBill\Utilities\Numbers;
use WC_Data_Exception;
use WC_DateTime;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Class.
 */
abstract class Invoice extends Document implements \Vendidero\StoreaBill\Interfaces\Invoice, TotalsContainable, Discountable {

	protected $order = null;

	protected $data_store_name = 'invoice';

	protected $tax_totals = null;

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @var bool|array
	 */
	protected $payment_status_transition = false;

	/**
	 * Stores invoice data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'                => null,
		'date_modified'               => null,
		'date_sent'                   => null,
		'date_custom'                 => null,
		'date_custom_extra'           => null,
		'date_due'                    => null,
		'date_paid'                   => null,
		'date_of_service'             => null,
		'date_of_service_end'         => null,
		'created_via'                 => '',
		'version'                     => '',
		'parent_id'                   => 0,
		'payment_status'              => '',
		'payment_method_title'        => '',
		'payment_method_name'         => '',
		'payment_transaction_id'      => '',
		'reference_id'                => '',
		'reference_type'              => '',
		'reference_number'            => '',
		'customer_id'                 => 0,
		'author_id'                   => 0,
		'number'                      => '',
		'formatted_number'            => '',
		'journal_type'                => '',
		'vat_id'                      => '',
		'status'                      => '',
		'discount_notice'             => '',
		'address'                     => array(),
		'shipping_address'            => array(),
		'external_sync_handlers'      => array(),
		'relative_path'               => '',
		'currency'                    => '',
		'prices_include_tax'          => false,
		'tax_display_mode'            => 'incl',
		'is_reverse_charge'           => false,
		'is_oss'                      => false,
		'is_taxable'                  => true,
		'round_tax_at_subtotal'       => null,
		'total'                       => 0,
		'subtotal'                    => 0,
		'total_paid'                  => 0,
		'product_total'               => 0,
		'shipping_total'              => 0,
		'fee_total'                   => 0,
		'product_subtotal'            => 0,
		'shipping_subtotal'           => 0,
		'fee_subtotal'                => 0,
		'discount_total'              => 0,
		'total_tax'                   => 0,
		'subtotal_tax'                => 0,
		'product_tax'                 => 0,
		'shipping_tax'                => 0,
		'fee_tax'                     => 0,
		'product_subtotal_tax'        => 0,
		'shipping_subtotal_tax'       => 0,
		'fee_subtotal_tax'            => 0,
		'discount_tax'                => 0,
		'voucher_total'               => 0,
		'voucher_tax'                 => 0,
		'stores_vouchers_as_discount' => false,
	);

	/**
	 * This method decides whether an invoice is fixed e.g. must
	 * be protected from being edited.
	 *
	 * @return bool
	 */
	public function is_finalized() {
		$is_finalized = $this->is_editable() ? false : true;

		/**
		 * In case a status transition to closed is currently happening - still allow adjustments
		 */
		if ( $this->status_transition ) {
			if ( $this->status_transition['to'] === 'closed' ) {
				$is_finalized = false;
			}
		}

		/**
		 * In case a number transitioning is happening - still allow adjustments
		 */
		if ( $this->numbering_transition ) {
			$is_finalized = false;
		}

		return $is_finalized;
	}

	/**
	 * @param bool $defer_render
	 *
	 * @return bool|WP_Error
	 */
	public function finalize( $defer_render = false ) {

		// Update date created on finalization
		$this->set_date_created( time() );

		// Update date due on finalization
		if ( ! $this->get_date_due( 'edit' ) ) {
			$this->set_date_due( sab_calculate_invoice_date_due( $this->get_date_created() ) );
		}

		// Update date of service on finalization
		if ( ! $this->get_date_of_service( 'edit' ) ) {
			$this->set_date_of_service( current_time( 'timestamp', true ) );
		}

		if ( ! $this->get_date_of_service_end( 'edit' ) ) {
			$this->set_date_of_service_end( $this->get_date_of_service() );
		}

		/**
		 * This action is being executed after an invoice has been finalized.
		 *
		 * The dynamic portion of this hook, `$this->get_type()` is used to
		 * construct an individual prefix based on the current invoice type.
		 *
		 * Example hook name: storeabill_invoice_before_finalize_render
		 *
		 * @param Invoice $invoice The invoice object.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		do_action( "{$this->get_general_hook_prefix()}before_finalize", $this );

		$this->maybe_set_paid();

		$updated = $this->update_status( 'closed' );
		$defer   = true === $defer_render && ! sab_allow_deferring( 'render' ) ? false : $defer_render;

		if ( $updated ) {
			if ( $defer ) {
				$this->render_deferred();
			} else {
				$render_result = $this->render();

				if ( is_wp_error( $render_result ) ) {
					return $render_result;
				}
			}

			/**
			 * This action is being executed after an invoice has been finalized.
			 *
			 * The dynamic portion of this hook, `$this->get_type()` is used to
			 * construct an individual prefix based on the current invoice type.
			 *
			 * Example hook name: storeabill_invoice_before_finalize_render
			 *
			 * @param Invoice $invoice The invoice object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			do_action( "{$this->get_general_hook_prefix()}after_finalize", $this );
		}

		return $updated;
	}

	/**
	 * Do not allow re-rendering the document
	 * if the invoice has already been finalized and a file exists.
	 *
	 * @return bool|WP_Error
	 */
	public function render( $is_preview = false, $preview_output = false ) {
		if ( ! $is_preview && ( $this->is_finalized() && $this->has_file() ) ) {
			return new WP_Error( 'render-error', _x( 'This document has already been finalized and cannot be re-rendered.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		return parent::render( $is_preview, $preview_output );
	}

	/**
	 * Returns the document type.
	 *
	 * @return string
	 */
	public function get_type() {
		return ( 'simple' === $this->get_invoice_type() ) ? 'invoice' : 'invoice_' . $this->get_invoice_type();
	}

	abstract public function get_invoice_type();

	public function get_data() {
		$data = parent::get_data();

		// Force core address data to exist
		$address_fields = apply_filters( "{$this->get_general_hook_prefix()}shipping_address_fields", array(
			'first_name' => '',
			'last_name'  => '',
			'company'    => '',
			'address_1'  => '',
			'address_2'  => '',
			'city'       => '',
			'state'      => '',
			'postcode'   => '',
			'country'    => '',
			'vat_id'     => '',
			'email'      => '',
		), $this );

		foreach( $address_fields as $field => $default_value ) {
			if ( ! isset( $data['shipping_address'][ $field ] ) ) {
				$data['shipping_address'][ $field ] = $default_value;
			}
		}

		$data['formatted_shipping_address'] = $this->get_formatted_shipping_address();
		$data['tax_rate_percentages']       = $this->get_tax_rate_percentages();

		$data['tax_totals']   = $this->get_tax_totals();
		$data['totals']       = $this->get_totals();
		$data['order_id']     = $this->get_order_id();
		$data['order_type']   = $this->get_order_type();
		$data['order_number'] = $this->get_order_number();
		$data['date_paid']    = $this->get_date_paid();
		$data['date_due']     = $this->get_date_due();
		$data['total_net']    = $this->get_total_net();
		$data['subtotal']     = $this->get_subtotal();
		$data['subtotal_tax'] = $this->get_subtotal_tax();
		$data['subtotal_net'] = $this->get_subtotal_net();

		$data['is_oss']                     = $this->is_oss();
		$data['is_reverse_charge']          = $this->is_reverse_charge();
		$data['is_eu_cross_border_taxable'] = $this->is_eu_cross_border_taxable();
		$data['taxable_country']            = $this->get_taxable_country();
		$data['taxable_postcode']           = $this->get_taxable_postcode();

		$data['discount_percentage']             = $this->get_discount_percentage();
		$data['formatted_discount_notice']       = $this->get_formatted_discount_notice();
		$data['additional_costs_discount_total'] = $this->get_additional_costs_discount_total();
		$data['additional_costs_discount_tax']   = $this->get_additional_costs_discount_tax();
		$data['additional_costs_discount_net']   = $this->get_additional_costs_discount_net();

		unset( $data['date_custom'] );
		unset( $data['date_custom_extra'] );

		foreach( $this->get_item_types_for_tax_totals() as $item_type ) {
			$data[ $item_type . '_net' ]          = $this->get_total_net( $item_type );
			$data[ $item_type . '_subtotal_net' ] = $this->get_total_net( $item_type . '_subtotal' );

			/**
			 * Explicitly add subtotals for legacy purposes
			 */
			$subtotal_getter     = "get_{$item_type}_subtotal";
			$subtotal_tax_getter = "get_{$item_type}_subtotal_tax";

			if ( is_callable( array( $this, $subtotal_getter ) ) ) {
				$data[ $item_type . '_subtotal' ] = $this->{ $subtotal_getter }();
			}

			if ( is_callable( array( $this, $subtotal_tax_getter ) ) ) {
				$data[ $item_type . '_subtotal_tax' ] = $this->{ $subtotal_tax_getter }();
			}
		}

		return $data;
	}

	/**
	 * Returns supported invoice document item types.
	 *
	 * @return array
	 */
	public function get_item_types() {
		return apply_filters( $this->get_hook_prefix() . 'item_types', array(
			'product',
			'fee',
			'tax',
			'shipping',
			'voucher'
		), $this );
	}

	/**
	 * Returns item types used to calculate totals.
	 *
	 * @return array
	 */
	public function get_item_types_for_totals() {
		$item_types = apply_filters( $this->get_hook_prefix() . 'item_types_for_total', array(
			'product',
			'fee',
			'shipping',
			'voucher'
		), $this );

		if ( $this->stores_vouchers_as_discount() ) {
			$item_types = array_diff( $item_types, array( 'voucher' ) );
		}

		return $item_types;
	}

	/**
	 * Returns item types used to calculate totals.
	 *
	 * @return array
	 * @see SAB_Item_Totalizable
	 */
	public function get_item_types_for_subtotals() {
		return apply_filters( $this->get_hook_prefix() . 'item_types_for_subtotal', array(
			'product',
		), $this );
	}

	/**
	 * Returns item types used to calculate tax totals.
	 * Item types must implement 'SAB_Item_Taxable`.
	 *
	 * @return array
	 * @see SAB_Item_Taxable
	 */
	public function get_item_types_for_tax_totals() {
		return apply_filters( $this->get_hook_prefix() . 'item_types_for_tax_total', array(
			'product',
			'fee',
			'shipping',
		), $this );
	}

	/**
	 * Item types which are treated as additional costs (e.g. shipping, fees).
	 *
	 * @return array
	 * @see SAB_Item_Taxable
	 */
	public function get_item_types_additional_costs() {
		return apply_filters( $this->get_hook_prefix() . 'item_types_additional_costs', array(
			'fee',
			'shipping',
		), $this );
	}

	/**
	 * Returns item types used to calculate tax totals.
	 * Item types must implement 'SAB_Item_Taxable`.
	 *
	 * @return array
	 * @see SAB_Item_Taxable
	 */
	public function get_item_types_for_tax_subtotals() {
		return apply_filters( $this->get_hook_prefix() . 'item_types_for_tax_subtotal', array(
			'product',
		), $this );
	}

	/**
	 * @return Total[]
	 */
	public function get_totals( $type = '' ) {
		$doc_type        = sab_get_document_type( $this->get_type() );
		$total_types     = array_keys( $doc_type->total_types );
		$document_totals = array();

		if ( ! empty( $type ) ) {
			$type        = is_array( $type ) ? $type : array( $type );
			$valid_types = array_intersect( $type, $total_types );

			if ( ! empty( $valid_types ) ) {
				$total_types = $valid_types;
			}
		}

		foreach( $total_types as $total_type ) {

			if ( has_filter( "{$this->get_hook_prefix()}total_type_{$total_type}" ) ) {
				$document_totals = array_merge( apply_filters( "{$this->get_hook_prefix()}total_type_{$total_type}", array(), $this, $total_type ), $document_totals );
			} elseif ( 'nets' === $total_type ) {
				$net_total_zero_rate = $this->get_total_net( 'total', false );
				$taxes               = $this->get_tax_totals();
				$has_zero_rate       = false;

				foreach ( $taxes as $tax_total ) {
					$net_total_tax = $tax_total->get_total_net( false );

					if ( $net_total_tax <= 0 ) {
						$net_total_tax = 0;
					}

					$net_total_zero_rate -= $net_total_tax;

					if ( empty( $tax_total->get_tax_rate()->get_percent() ) ) {
						$has_zero_rate = key( array_slice( $document_totals, -1, 1, true ) );
					}
				}

				$net_total_zero_rate = sab_format_decimal( $net_total_zero_rate, '' );

				/**
				 * Prevent rounding issues while re-calculating zero nets.
				 */
				if ( $net_total_zero_rate <= 0.01 ) {
					$net_total_zero_rate = 0;
				}

				foreach ( $taxes as $tax_total ) {
					$net_inner_total = $tax_total->get_total_net();

					/**
					 * In case only one tax rate is included - use global net total to prevent rounding display issues
					 */
					if ( sizeof( $taxes ) == 1 && $net_total_zero_rate <= 0 ) {
						$net_inner_total = $this->get_total_net();
					}

					$document_totals[] = new Total( $this, array(
						'total'        => $net_inner_total,
						'placeholders' => array(
							'{rate}'           => $tax_total->get_tax_rate()->get_percent(),
							'{formatted_rate}' => $tax_total->get_tax_rate()->get_formatted_percentage(),
						),
						'type'         => 'nets',
					) );
				}

				/**
				 * Seems like zero tax rates or non-taxable products are involved.
				 * Make sure to add the non-taxed total net amount left (e.g. non-taxable products) too.
				 */
				if ( $net_total_zero_rate > 0 ) {
					if ( false === $has_zero_rate || ! isset( $document_totals[ $has_zero_rate ] ) ) {
						$document_totals[] = new Total( $this, array(
							'total'        => $net_total_zero_rate,
							'type'         => 'nets',
							'placeholders' => array(
								'{rate}'           => '0',
								'{formatted_rate}' => sab_format_tax_rate_percentage( 0 ),
							),
						) );
					} else {
						$document_totals[ $has_zero_rate ]->set_total( $document_totals[ $has_zero_rate ]->get_total() + $net_total_zero_rate );
					}
				}
			} if ( 'gross_tax_shares' === $total_type ) {
				$taxes = $this->get_tax_totals();

				foreach ( $taxes as $tax_total ) {
					$total = $tax_total->get_total_net( false ) + $tax_total->get_total_tax( false );

					if ( $total <= 0 ) {
						$total = 0;
					}

					$document_totals[] = new Total( $this, array(
						'total'        => sab_format_decimal( $total, '' ),
						'type'         => 'gross_tax_shares',
						'placeholders' => array(
							'{rate}'           => $tax_total->get_tax_rate()->get_percent(),
							'{formatted_rate}' => $tax_total->get_tax_rate()->get_formatted_percentage(),
						),
					) );
				}
			} elseif( 'taxes' === $total_type || '_taxes' === substr( $total_type, -6 ) ) {
				$taxes = $this->get_tax_totals();

				if ( '_taxes' === substr( $total_type, -6 ) ) {
					$taxes = $this->get_tax_totals( str_replace( '_taxes', '', $total_type ) );
				}

				foreach ( $taxes as $tax_total ) {

					if ( $tax_total->get_total_tax() == 0 && apply_filters( "{$this->get_general_hook_prefix()}hide_zero_taxes", true, $this ) ) {
						continue;
					}

					$document_totals[] = new Total( $this, array(
						'total'        => $tax_total->get_total_tax(),
						'placeholders' => array(
							'{rate}'           => $tax_total->get_tax_rate()->get_percent(),
							'{formatted_rate}' => $tax_total->get_tax_rate()->get_formatted_percentage(),
						),
						'type'         => $total_type,
					) );
				}
			} elseif( 'fees' === $total_type ) {
				$fees = $this->get_items( 'fee' );

				foreach ( $fees as $fee ) {
					$document_totals[] = new Total( $this, array(
						'total'        => $fee->get_total(),
						'placeholders' => array(
							'{name}' => $fee->get_name(),
						),
						'type'         => 'fees',
						'label'        => $fee->get_total() < 0 ? _x( 'Discount: %s', 'storeabill-core', 'woocommerce-germanized-pro' ) : _x( 'Fee: %s', 'storeabill-core', 'woocommerce-germanized-pro' )
					) );
				}
			} elseif( 'vouchers' === $total_type ) {
				$vouchers = $this->get_items( 'voucher' );

				foreach ( $vouchers as $voucher ) {
					$document_totals[] = new Total( $this, array(
						'total'        => $voucher->get_total(),
						'placeholders' => array(
							'{code}' => $voucher->get_code(),
						),
						'type'         => 'vouchers',
						'label'        => _x( 'Voucher: %s', 'storeabill-core', 'woocommerce-germanized-pro' )
					) );
				}
			} elseif( 'line_subtotal_after' === $total_type ) {
				$document_totals[] = new Total( $this, array(
					'total' => $this->get_line_subtotal( false ),
					'type'  => $total_type,
				) );
			} elseif( '_net' === substr( $total_type, -4 ) ) {
				$net_type  = substr( $total_type, 0, - 4 );
				$net_total = 0;
				$getter    = 'get_' . $net_type . '_net';

				if ( 'line_subtotal_after' === $net_type ) {
					$net_total = $this->get_line_subtotal_net( false );
				} elseif ( is_callable( array( $this, $getter ) ) ) {
					$net_total = $this->$getter();
				} else {
					$net_total = $this->get_total_net( $net_type );
				}

				$placeholders = array();

				if ( in_array( $total_type, array( 'discount_net' ) ) ) {
					$placeholders['{notice}']        = $this->get_formatted_discount_notice();
					$placeholders['{discount_type}'] = $this->get_discount_type_title();
				}

				$document_totals[] = new Total( $this, array(
					'total'        => $net_total,
					'type'         => $total_type,
					'placeholders' => $placeholders,
				) );
			} else {
				$getter         = 'get_' . $total_type . '_total';
				$getter_reverse = 'get_total_' . $total_type;
				$total          = false;

				// Support total or subtotal type
				if ( strpos( $total_type, 'total' ) !== false ) {
					$getter = 'get_' . $total_type;
				}

				if ( is_callable( array( $this, $getter ) ) ) {
					$total = $this->$getter();
				} elseif ( is_callable( array( $this, $getter_reverse ) ) ) {
					$total = $this->$getter_reverse();
				}

				$placeholders = array();

				if ( in_array( $total_type, array( 'discount' ) ) ) {
					$placeholders['{notice}']        = $this->get_formatted_discount_notice();
					$placeholders['{discount_type}'] = $this->get_discount_type_title();
				} elseif ( in_array( $total_type, array( 'voucher' ) ) ) {
					$placeholders['{code}']          = $this->get_formatted_voucher_notice();
				}

				if ( false !== $total ) {
					$document_totals[] = new Total( $this, array(
						'total'        => $total,
						'type'         => $total_type,
						'placeholders' => $placeholders
					) );
				}
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}_totals", $document_totals, $this );
	}

	/**
	 * @return bool|Order
	 */
	public function get_reference() {
		if ( is_null( $this->order ) ) {
			$this->order = \Vendidero\StoreaBill\References\Order::get_order( $this->get_order_id(), $this->get_order_type() );
		}

		return $this->order;
	}

	public function get_edit_url() {
		if ( $order = $this->get_order() ) {
			return $order->get_edit_url();
		}

		return false;
	}

	/**
	 * @return bool|Order
	 */
	public function get_order() {
		return $this->get_reference();
	}

	/**
	 * Returns the shipment address email.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_email( $context = 'view' ) {
		$email = parent::get_email( $context );

		/**
		 * Fallback to the current order email (if exists) to make sure
		 * re-sending the document will lead to using "newer" (edited) order email address.
		 */
		if ( 'view' === $context && ( $order = $this->get_order() ) ) {
			$email = $order->get_email();
		}

		return $email;
	}

	/**
	 * Get currency.
	 *
	 * @param string $context
	 *
	 * @return string The currency code.
	 */
	public function get_currency( $context = 'view' ) {
		return $this->get_prop( 'currency', $context );
	}

	public function get_vat_id( $context = 'view' ) {
		$vat_id = $this->get_prop( 'vat_id', $context );

		if ( 'view' === $context && empty( $vat_id ) ) {
			$vat_id = $this->get_shipping_vat_id( $context );

			if ( empty( $vat_id ) ) {
				$vat_id = $this->get_billing_vat_id( $context );
			}
		}

		return $vat_id;
	}

	public function get_billing_vat_id( $context = 'view' ) {
		return $this->get_address_prop( 'vat_id', $context );
	}

	public function get_shipping_vat_id( $context = 'view' ) {
		return $this->get_shipping_address_prop( 'vat_id', $context );
	}

	public function is_oss() {
		return apply_filters( "{$this->get_general_hook_prefix()}is_oss", $this->get_is_oss(), $this );
	}

	public function is_eu_cross_border_taxable() {
		$country = $this->get_taxable_country();

		if ( ! $this->is_reverse_charge() && ( $country !== Countries::get_base_country() && Countries::is_eu_vat_country( $this->get_taxable_country(), $this->get_taxable_postcode() ) ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Returns total invoice amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_total( $context = 'view' ) {
		return $this->get_prop( 'total', $context );
	}

	/**
	 * Returns total paid amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_total_paid( $context = 'view' ) {
		return $this->get_prop( 'total_paid', $context );
	}

	public function get_order_id( $context = 'view' ) {
		return $this->get_reference_id( $context );
	}

	public function get_order_type( $context = 'view' ) {
		return $this->get_reference_type( $context );
	}

	public function get_order_number( $context = 'view' ) {
		return $this->get_reference_number( $context );
	}

	public function is_past_due() {
		$date_due    = $this->get_date_due();
		$is_past_due = false;

		if ( $date_due && ! $this->is_paid() ) {
			$datetime = sab_string_to_datetime( 'now' );

			$datetime->setTime( 0, 0, 0 );
			$date_due->setTime( 0, 0, 0 );

			if ( $datetime > $date_due ) {
				$is_past_due = true;
			}
		}

		return apply_filters( "{$this->get_general_hook_prefix()}is_past_due", $is_past_due, $this );
	}

	public function get_line_subtotal( $before_discount = true ) {
		$subtotal = 0;
		$getter   = 'get_total';

		if ( $before_discount ) {
			$getter = 'get_subtotal';
		}

		foreach ( $this->get_items( $this->get_line_item_types() ) as $item ) {
			if ( is_callable( array( $item, $getter ) ) ) {
				$item_total = sab_add_number_precision( $item->$getter(), false );

				$subtotal += ( ! $this->round_tax_at_subtotal() ) ? Numbers::round( $item_total ) : $item_total;
			}
		}

		$subtotal = sab_remove_number_precision( $subtotal );

		return apply_filters( $this->get_hook_prefix() . 'line_subtotal', sab_format_decimal( $subtotal, '' ), $this, $before_discount );
	}

	public function get_line_subtotal_net( $before_discount = true ) {
		$subtotal = 0;
		$getter   = 'get_total_net';

		if ( $before_discount ) {
			$getter = 'get_subtotal_net';
		}

		foreach ( $this->get_items( $this->get_line_item_types() ) as $item ) {
			if ( is_callable( array( $item, $getter ) ) ) {
				$subtotal += $item->$getter();
			}
		}

		return apply_filters( $this->get_hook_prefix() . 'line_subtotal_net', sab_format_decimal( $subtotal, '' ), $this, $before_discount );
	}

	/**
	 * Returns subtotal invoice amount based on product and discount total.
	 *
	 * @return string
	 */
	public function get_subtotal( $context = 'view' ) {
		if ( ! $this->supports_stored_subtotals() ) {
			$subtotal = 0;

			foreach ( $this->get_items( $this->get_item_types_for_totals() ) as $item ) {
				if ( is_callable( array( $item, 'get_subtotal' ) ) ) {
					$subtotal += $item->get_subtotal();
				}
			}

			return apply_filters( $this->get_hook_prefix() . 'subtotal', sab_format_decimal( $subtotal, '' ), $this );
		}

		return $this->get_prop( 'subtotal', $context );
	}

	/**
	 * Returns subtotal invoice amount based on product and discount total.
	 *
	 * @return string
	 */
	public function get_subtotal_net() {
		if ( ! $this->supports_stored_subtotals() ) {
			$subtotal = 0;

			foreach ( $this->get_items( $this->get_item_types_for_totals() ) as $item ) {
				if ( is_callable( array( $item, 'get_subtotal_net' ) ) ) {
					$subtotal += $item->get_subtotal_net();
				}
			}

			return apply_filters( $this->get_hook_prefix() . 'subtotal_net', sab_format_decimal( $subtotal ), $this );
		}

		return apply_filters( $this->get_hook_prefix() . 'subtotal_net', $this->get_total_net( 'subtotal' ), $this );
	}

	/**
	 * Returns subtotal invoice amount based on product and discount total.
	 *
	 * @return string
	 */
	public function get_subtotal_tax( $context = 'view' ) {
		if ( ! $this->supports_stored_subtotals() ) {
			$subtotal = 0;

			foreach ( $this->get_items( $this->get_item_types_for_totals() ) as $item ) {
				if ( is_callable( array( $item, 'get_subtotal_tax' ) ) ) {
					$subtotal += $item->get_subtotal_tax();
				}
			}

			return apply_filters( $this->get_hook_prefix() . 'subtotal_tax', sab_format_decimal( $subtotal ), $this );
		}

		return $this->get_prop( 'subtotal_tax', $context );
	}

	/**
	 * Returns product total.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_product_total( $context = 'view' ) {
		return $this->get_prop( 'product_total', $context );
	}

	/**
	 * Returns product total.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_product_subtotal( $context = 'view' ) {
		if ( ! $this->supports_stored_subtotals() ) {
			$subtotal = 0;

			foreach ( $this->get_items( 'product' ) as $item ) {
				if ( is_callable( array( $item, 'get_subtotal' ) ) ) {
					$subtotal += $item->get_subtotal();
				}
			}

			return sab_format_decimal( $subtotal );
		}

		return $this->get_prop( 'product_subtotal', $context );
	}

	/**
	 * Returns voucher total incl taxes.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_voucher_total( $context = 'view' ) {
		$voucher_total = $this->get_prop( 'voucher_total', $context );

		if ( 'view' === $context ) {
			$voucher_total = floatval( $voucher_total );
			$voucher_total = $voucher_total < 0 ? $voucher_total * -1 : $voucher_total;
		}

		return $voucher_total;
	}

	/**
	 * Returns voucher total.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_voucher_net_total() {
		return sab_format_decimal( $this->get_voucher_total() - $this->get_voucher_tax() );
	}

	/**
	 * Returns voucher total.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_voucher_tax( $context = 'view' ) {
		$voucher_total = $this->get_prop( 'voucher_tax', $context );

		if ( 'view' === $context ) {
			$voucher_total = floatval( $voucher_total );
			$voucher_total = $voucher_total < 0 ? $voucher_total * -1 : $voucher_total;
		}

		return $voucher_total;
	}

	/**
	 * Returns discount total.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_discount_total( $context = 'view' ) {
		return $this->get_prop( 'discount_total', $context );
	}

	protected function get_item_type_discount( $item_type = 'product' ) {
		$getter          = "get_{$item_type}_total";
		$getter_subtotal = "get_{$item_type}_subtotal";
		$total           = 0;
		$subtotal        = 0;

		if ( is_callable( array( $this, $getter ) ) ) {
			$total = $this->{ $getter }();
		}

		if ( is_callable( array( $this, $getter_subtotal ) ) ) {
			$subtotal = $this->{ $getter_subtotal }();
		}

		if ( 0 == $subtotal ) {
			$subtotal = $total;
		}

		$discount = $subtotal - $total;

		return sab_format_decimal( $discount );
	}

	protected function get_item_type_discount_tax( $item_type = 'product' ) {
		$getter          = "get_{$item_type}_tax";
		$getter_subtotal = "get_{$item_type}_subtotal_tax";
		$total           = 0;
		$subtotal        = 0;

		if ( is_callable( array( $this, $getter ) ) ) {
			$total = $this->{ $getter }();
		}

		if ( is_callable( array( $this, $getter_subtotal ) ) ) {
			$subtotal = $this->{ $getter_subtotal }();
		}

		if ( 0 == $subtotal ) {
			$subtotal = $total;
		}

		$discount = $subtotal - $total;

		return sab_format_decimal( $discount );
	}

	/**
	 * Returns discount tax amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_discount_tax( $context = 'view' ) {
		return $this->get_prop( 'discount_tax', $context );
	}

	public function get_discount_net( $context = 'view' ) {
		$net_total = $this->get_total_net( 'discount' );

		return $net_total;
	}

	public function get_additional_costs_discount_total() {
		$total_discount = $this->get_discount_total();

		if ( $total_discount > 0 ) {
			foreach( $this->get_line_item_types() as $line_item_type ) {
				$total_discount -= $this->get_item_type_discount( $line_item_type );
			}
		}

		return sab_format_decimal( $total_discount, '' );
	}

	public function get_additional_costs_discount_tax() {
		$total_discount = $this->get_discount_tax();

		if ( $total_discount > 0 ) {
			foreach( $this->get_line_item_types() as $line_item_type ) {
				$total_discount -= $this->get_item_type_discount_tax( $line_item_type );
			}
		}

		return sab_format_decimal( $total_discount, '' );
	}

	public function get_additional_costs_discount_net() {
		$net_total = $this->get_total_net( 'additional_costs_discount' );

		return $net_total;
	}

	public function get_discount_notice( $context = 'view' ) {
		return $this->get_prop( 'discount_notice', $context );
	}

	public function get_formatted_discount_notice() {
		$notice = trim( sprintf( _x( '%1$s (%2$s)', 'storeabill-discount-notice', 'woocommerce-germanized-pro' ), $this->get_discount_notice(), $this->get_discount_type_title() ) );

		/**
		 * Clean up empty notices
		 */
		if ( '()' === $notice ) {
			$notice = '';
		}

		return apply_filters( "{$this->get_hook_prefix()}formatted_discount_notice", $notice, $this );
	}

	public function get_formatted_voucher_notice() {
		$codes = array();

		foreach( $this->get_items( 'voucher' ) as $voucher ) {
			if ( ! empty( $voucher->get_code() ) ) {
				$codes[] = $voucher->get_code();
			}
		}

		return apply_filters( "{$this->get_hook_prefix()}formatted_voucher_notice", implode( ', ', $codes ), $this );
	}

	public function get_discount_type_title() {
		$title = '';
		$types = sab_get_invoice_discount_types();

		if ( $this->has_voucher() && $this->stores_vouchers_as_discount() ) {
			$title = $types['multi_purpose'];
		} elseif ( $this->has_discount() ) {
			$title = $types['single_purpose'];
		}

		return apply_filters( "{$this->get_hook_prefix()}discount_type_title", $title, $this );
	}

	public function has_discount() {
		return $this->get_discount_total() > 0;
	}

	public function get_discount_percentage() {
		return sab_calculate_discount_percentage( $this->get_total_before_discount(), $this->get_discount_total() );
	}

	public function get_total_before_discount() {
		return $this->get_subtotal();
	}

	public function get_total_tax_before_discount() {
		return $this->get_subtotal_tax();
	}

	/**
	 * Returns shipping total.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_shipping_total( $context = 'view' ) {
		return $this->get_prop( 'shipping_total', $context );
	}

	protected function supports_stored_subtotals() {
		return ( ! $this->get_version() ) || version_compare( $this->get_version(), '1.2.7', '>' );
	}

	/**
	 * Returns shipping total.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_shipping_subtotal( $context = 'view' ) {
		if ( ! $this->supports_stored_subtotals() ) {
			$subtotal = 0;

			foreach ( $this->get_items( 'shipping' ) as $item ) {
				if ( is_callable( array( $item, 'get_subtotal' ) ) ) {
					$subtotal += $item->get_subtotal();
				}
			}

			return sab_format_decimal( $subtotal );
		}

		return $this->get_prop( 'shipping_subtotal', $context );
	}

	/**
	 * Returns the address properties.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string[]
	 */
	public function get_shipping_address( $context = 'view' ) {
		$address = $this->get_prop( 'shipping_address', $context );

		if ( 'view' === $context && empty( $address ) ) {
			$address = $this->get_address();
		}

		return $address;
	}

	public function has_date_of_service_interval() {
		$start        = $this->get_date_of_service();
		$end          = $this->get_date_of_service_end();
		$has_interval = false;

		if ( $end && $start && ( $end->getTimestamp() > $start->getTimestamp() ) ) {
			$has_interval = true;
		}

		return apply_filters( "{$this->get_general_hook_prefix()}has_date_of_service_interval", $has_interval, $this );
	}

	/**
	 * Returns the formatted shipping address.
	 *
	 * @param  string $empty_content Content to show if no address is present.
	 * @return string
	 */
	public function get_formatted_shipping_address( $empty_content = '' ) {
		$address = Countries::get_formatted_address( $this->get_shipping_address() );

		return apply_filters( "{$this->get_hook_prefix()}formatted_shipping_address", ( $address ? $address : $empty_content ), $this );
	}

	public function has_differing_shipping_address() {
		$fields_excluded  = apply_filters( "{$this->get_general_hook_prefix()}has_shipping_address_comparison_excluded_fields", array( 'title', 'email', 'phone', 'vat_id' ), $this );

		/**
		 * This callback is being used to remove certain data from addresses
		 * before comparing them (and checking if a shipping address exists or not).
		 *
		 * @param $address_data
		 *
		 * @return array
		 */
		$callback = function( $address_data ) use ( $fields_excluded ) {
			$address_data = array_diff_key( $address_data, array_flip( $fields_excluded ) );

			return $address_data;
		};

		add_filter( "{$this->get_hook_prefix()}address", $callback, 1000 );
		add_filter( "{$this->get_hook_prefix()}shipping_address", $callback, 1000 );

		$address          = $this->get_formatted_address();
		$shipping_address = $this->get_formatted_shipping_address();

		remove_filter( "{$this->get_hook_prefix()}address", $callback, 1000 );
		remove_filter( "{$this->get_hook_prefix()}shipping_address", $callback, 1000 );

		return apply_filters( "{$this->get_general_hook_prefix()}has_differing_shipping_address", ( ! empty( $shipping_address ) && $shipping_address != $address ), $this );
	}

	/**
	 * Returns an address prop.
	 *
	 * @param string $prop
	 * @param string $context
	 *
	 * @return null|string
	 */
	protected function get_shipping_address_prop( $prop, $context = 'view' ) {
		$value = '';

		if ( isset( $this->changes['shipping_address'][ $prop ] ) || isset( $this->data['shipping_address'][ $prop ] ) ) {
			$value = isset( $this->changes['shipping_address'][ $prop ] ) ? $this->changes['shipping_address'][ $prop ] : $this->data['shipping_address'][ $prop ];
		}

		if ( 'view' === $context ) {
			/**
			 * Filter to adjust a document's shipping address property e.g. first_name.
			 *
			 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
			 * unique hook for a document type. `$prop` refers to the actual address property e.g. first_name.
			 *
			 * Example hook name: storeabill_document_get_shipping_address_first_name
			 *
			 * @param string   $value The address property value.
			 * @param Document $this The document object.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			$value = apply_filters( "{$this->get_hook_prefix()}shipping_address_{$prop}", $value, $this );
		}

		return $value;
	}

	public function get_taxable_country() {
		$country = $this->get_country();

		if ( '' !== $this->get_shipping_country() ) {
			$country = $this->get_shipping_country();
		}

		return apply_filters( "{$this->get_hook_prefix()}taxable_country", $country, $this );
	}

	public function get_taxable_postcode() {
		$postcode = $this->get_postcode();

		if ( '' !== $this->get_shipping_postcode() ) {
			$postcode = $this->get_shipping_postcode();
		}

		return apply_filters( "{$this->get_hook_prefix()}taxable_postcode", $postcode, $this );
	}

	public function get_shipping_country( $context = 'view' ) {
		return $this->get_shipping_address_prop( 'country', $context );
	}

	public function get_shipping_postcode( $context = 'view' ) {
		return $this->get_shipping_address_prop( 'postcode', $context );
	}

	/**
	 * Returns fee total.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_fee_total( $context = 'view' ) {
		return $this->get_prop( 'fee_total', $context );
	}

	/**
	 * Returns fee total.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_fee_subtotal( $context = 'view' ) {
		if ( ! $this->supports_stored_subtotals() ) {
			$subtotal = 0;

			foreach ( $this->get_items( 'fee' ) as $item ) {
				if ( is_callable( array( $item, 'get_subtotal' ) ) ) {
					$subtotal += $item->get_subtotal();
				}
			}

			return sab_format_decimal( $subtotal );
		}

		return $this->get_prop( 'fee_subtotal', $context );
	}

	/**
	 * Returns total tax amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_total_tax( $context = 'view' ) {
		return $this->get_prop( 'total_tax', $context );
	}

	/**
	 * Returns product tax amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_product_tax( $context = 'view' ) {
		return $this->get_prop( 'product_tax', $context );
	}

	/**
	 * Returns product tax amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_product_subtotal_tax( $context = 'view' ) {
		if ( ! $this->supports_stored_subtotals() ) {
			$subtotal = 0;

			foreach ( $this->get_items( 'product' ) as $item ) {
				if ( is_callable( array( $item, 'get_subtotal_tax' ) ) ) {
					$subtotal += $item->get_subtotal_tax();
				}
			}

			return sab_format_decimal( $subtotal );
		}

		return $this->get_prop( 'product_subtotal_tax', $context );
	}

	public function get_shipping_net( $context = 'view' ) {
		return $this->get_total_net( 'shipping' );
	}

	public function get_shipping_subtotal_net( $context = 'view' ) {
		return $this->get_total_net( 'shipping_subtotal' );
	}

	public function get_product_net( $context = 'view' ) {
		return $this->get_total_net( 'product' );
	}

	public function get_product_subtotal_net( $context = 'view' ) {
		return $this->get_total_net( 'product_subtotal' );
	}

	public function get_fee_net( $context = 'view' ) {
		return $this->get_total_net( 'fee' );
	}

	public function get_voucher_net( $context = 'view' ) {
		return $this->get_voucher_net_total();
	}

	public function get_fee_subtotal_net( $context = 'view' ) {
		return $this->get_total_net( 'fee_subtotal' );
	}

	public function get_voucher_subtotal_net( $context = 'view'  ) {
		return $this->get_voucher_net_total();
	}

	/**
	 * Returns fee tax amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_fee_tax( $context = 'view' ) {
		return $this->get_prop( 'fee_tax', $context );
	}

	/**
	 * Returns fee tax amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_fee_subtotal_tax( $context = 'view' ) {
		if ( ! $this->supports_stored_subtotals() ) {
			$subtotal = 0;

			foreach ( $this->get_items( 'fee' ) as $item ) {
				if ( is_callable( array( $item, 'get_subtotal_tax' ) ) ) {
					$subtotal += $item->get_subtotal_tax();
				}
			}

			return sab_format_decimal( $subtotal );
		}

		return $this->get_prop( 'fee_subtotal_tax', $context );
	}

	/**
	 * Returns shipping tax amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_shipping_tax( $context = 'view' ) {
		return $this->get_prop( 'shipping_tax', $context );
	}

	/**
	 * Returns shipping tax amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_shipping_subtotal_tax( $context = 'view' ) {
		if ( ! $this->supports_stored_subtotals() ) {
			$subtotal = 0;

			foreach ( $this->get_items( 'shipping' ) as $item ) {
				if ( is_callable( array( $item, 'get_subtotal_tax' ) ) ) {
					$subtotal += $item->get_subtotal_tax();
				}
			}

			return sab_format_decimal( $subtotal );
		}

		return $this->get_prop( 'shipping_subtotal_tax', $context );
	}

	/**
	 * Returns whether prices of this invoice include tax or not.
	 *
	 * @param string $context
	 *
	 * @return bool True if prices include tax.
	 */
	public function get_prices_include_tax( $context = 'view' ) {
		return $this->get_prop( 'prices_include_tax', $context );
	}

	/**
	 * Returns whether this invoice has a reverse charge VAT.
	 *
	 * @param string $context
	 *
	 * @return bool True if prices include tax.
	 */
	public function get_is_reverse_charge( $context = 'view' ) {
		return $this->get_prop( 'is_reverse_charge', $context );
	}

	/**
	 * Returns the tax display mode
	 *
	 * @param string $context
	 *
	 * @return string excl or incl
	 */
	public function get_tax_display_mode( $context = 'view' ) {
		return $this->get_prop( 'tax_display_mode', $context );
	}

	/**
	 * Returns whether this invoice is part of the OSS rule.
	 *
	 * @param string $context
	 *
	 * @return bool True if OSS.
	 */
	public function get_is_oss( $context = 'view' ) {
		return $this->get_prop( 'is_oss', $context );
	}

	/**
	 * Returns whether this invoice is taxable or not.
	 *
	 * @param string $context
	 *
	 * @return bool True if prices include tax.
	 */
	public function get_is_taxable( $context = 'view' ) {
		return $this->get_prop( 'is_taxable', $context );
	}

	public function get_stores_vouchers_as_discount( $context = 'view' ) {
		$stores_vouchers_as_discount = $this->get_prop( 'stores_vouchers_as_discount', $context );

		/**
		 * Invoices before 1.8.6 did always store vouchers as discount.
		 */
		if ( 'view' === $context && '' !== $this->get_version() && version_compare( $this->get_version(), '1.8.6', '<' ) ) {
			$stores_vouchers_as_discount = true;
		}

		return $stores_vouchers_as_discount;
	}

	public function stores_vouchers_as_discount() {
		return $this->get_stores_vouchers_as_discount();
	}

	public function has_voucher( $voucher_code = '' ) {
		return ( $this->get_voucher_total() > 0 );
	}

	public function has_voucher_items() {
		return sizeof( $this->get_items( 'voucher' ) ) > 0;
	}

	/**
	 * @param string $code
	 *
	 * @return false|\Vendidero\StoreaBill\Invoice\VoucherItem
	 */
	public function get_voucher( $code ) {
		$vouchers = $this->get_items( 'voucher' );

		foreach( $vouchers as $voucher ) {
			if ( $voucher->get_name() === $code ) {
				return $voucher;
			}
		}

		return false;
	}

	/**
	 * Returns the net total amount excluding vouchers.
	 *
	 * @return float
	 */
	public function get_net_total_ex_voucher() {
		$net_total = $this->get_total_net();

		if ( $this->has_voucher() ) {
			/**
			 * In case the tax display mode is set to excl,
			 */
			if ( 'excl' === $this->get_tax_display_mode() ) {
				$net_total -= ( $this->get_voucher_total() - $this->get_voucher_tax() );
			} else {
				$net_total -= $this->get_voucher_total();
			}
		}

		return $net_total;
	}

	/**
	 * Returns whether taxes are round at subtotal or per line.
	 *
	 * @param string $context
	 *
	 * @return bool True if taxes are round at subtotal.
	 */
	public function get_round_tax_at_subtotal( $context = 'view' ) {
		$round_tax = $this->get_prop( 'round_tax_at_subtotal', $context );

		if ( 'view' === $context && is_null( $round_tax ) ) {
			$round_tax = $this->prices_include_tax() ? true : false;
		}

		return $round_tax;
	}

	/**
	 * Get date_paid.
	 *
	 * @param  string $context
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_due( $context = 'view' ) {
		return $this->get_prop( 'date_custom_extra', $context );
	}

	/**
	 * Get date_paid.
	 *
	 * @param  string $context
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_paid( $context = 'view' ) {
		return $this->get_prop( 'date_custom', $context );
	}

	/**
	 * Get date of service (Leistungsdatum).
	 *
	 * @param  string $context
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_of_service( $context = 'view' ) {
		return $this->get_prop( 'date_of_service', $context );
	}

	/**
	 * Get date of service end (Leistungsdatum).
	 *
	 * @param  string $context
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_of_service_end( $context = 'view' ) {
		return $this->get_prop( 'date_of_service_end', $context );
	}

	public function get_date_of_service_period() {
		$period = null;

		if ( $this->get_date_of_service() ) {
			$date_start = clone $this->get_date_of_service();

			if ( $this->get_date_of_service_end() ) {
				$date_end = clone $this->get_date_of_service_end();
			} else {
				$date_end = clone $this->get_date_of_service();
			}

			$period = new \DatePeriod( $date_start, new \DateInterval( apply_filters( $this->get_hook_prefix() . 'date_of_service_interval', 'P1D' ) ), $date_end );
		}

		return $period;
	}

	/**
	 * Get payment method title.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_payment_method_title( $context = 'view' ) {
		return $this->get_prop( 'payment_method_title', $context );
	}

	/**
	 * Get payment method.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_payment_method_name( $context = 'view' ) {
		return $this->get_prop( 'payment_method_name', $context );
	}

	/**
	 * Get payment transaction id.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_payment_transaction_id( $context = 'view' ) {
		return $this->get_prop( 'payment_transaction_id', $context );
	}

	/**
	 * Return the invoice payment status without internal prefix.
	 *
	 * @param  string $context View or edit context.
	 * @return string
	 */
	public function get_payment_status( $context = 'view' ) {
		$status = $this->get_prop( 'payment_status', $context );

		if ( empty( $status ) && 'view' === $context ) {

			/**
			 * Filters the default invoice payment status used as fallback.
			 *
			 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
			 * unique hook for an invoice type.
			 *
			 * Example hook name: storeabill_invoice_get_default_payment_status
			 *
			 * @param string $status Default fallback status.
			 *
			 * @since 1.0.0
			 * @package Vendidero/StoreaBill
			 */
			$status = apply_filters( "{$this->get_hook_prefix()}default_payment_status", 'pending' );
		}

		return $status;
	}

	/**
	 * Checks whether the invoice has a specific payment status or not.
	 *
	 * @param  string|string[] $status The status to be checked against.
	 * @return boolean
	 */
	public function has_payment_status( $status ) {
		/**
		 * Filter to decide whether a document has a certain status or not.
		 *
		 * @param boolean  $has_status Whether the invoice has a status or not.
		 * @param Invoice  $this The document object.
		 * @param string   $status The status to be checked against.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_general_hook_prefix()}has_payment_status", ( is_array( $status ) && in_array( $this->get_payment_status(), $status, true ) ) || $this->get_payment_status() === $status, $this, $status );
	}

	public function is_paid() {
		/**
		 * Filter to decide whether an invoice is paid or not.
		 *
		 * @param Invoice $this The document object.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( "{$this->get_general_hook_prefix()}is_paid", $this->has_payment_status( 'complete' ) && $this->get_total_paid() >= $this->get_total(), $this );
	}

	/**
	 * Apply a coupon to the order and recalculate totals.
	 */
	public function apply_discount( $amount, $type = 'fixed', $args = array() ) {
		$args = wp_parse_args( $args, array(
			'is_voucher' => false,
			'code'       => '',
			'item_types' => array( 'product' )
		) );

		if ( $this->is_finalized() ) {
			return new WP_Error( _x( 'This invoice is already finalized and cannot be discounted.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		if ( $args['is_voucher'] && ! $this->stores_vouchers_as_discount() ) {
			$this->calculate_totals( true );

			$max_discount        = floatval( $this->get_total() ) * -1;
			$voucher_item_amount = max( ( $amount > 0 ? floatval( $amount ) * -1 : floatval( $amount ) ), $max_discount );

			if ( ( $voucher = $this->get_voucher( $args['code'] ) ) && $voucher_item_amount == $voucher->get_total() ) {
				$voucher->set_quantity( $voucher->get_quantity() + 1 );
				$voucher->calculate_totals();
			} else {
				$voucher = new VoucherItem();

				$voucher->set_document( $this );
				$voucher->set_code( $args['code'] );
				$voucher->set_line_total( $voucher_item_amount );

				$voucher->calculate_totals();

				$this->add_item( $voucher );
			}

			$this->calculate_totals( true );

		} else {
			$discounts = new Discounts( $this );
			$applied   = $discounts->apply_discount( $amount, $type, $args );

			if ( is_wp_error( $applied ) ) {
				return $applied;
			}

			$this->set_item_discount_amounts( $discounts );

			if ( ! empty( $args['code'] ) ) {
				$this->add_discount_notice( $args['code'] );
			}

			/**
			 * Legacy vouchers as discounts
			 */
			if ( $discounts->is_voucher() ) {
				$this->set_voucher_total( $discounts->get_total_discount() );
			}

			$this->calculate_totals( true );

			/**
			 * Legacy vouchers as discounts
			 */
			if ( $discounts->is_voucher() ) {
				$all_discounts = $discounts->get_discounts();
				$discount_tax  = 0;

				foreach ( $all_discounts as $item_id => $item_discount_amount ) {
					if ( $item = $this->get_item( $item_id ) ) {
						if ( ! $item->is_taxable() ) {
							continue;
						}

						$taxes = array_sum( Tax::calc_tax( $item_discount_amount, $item->get_tax_rates(), $this->get_prices_include_tax() ) );

						if ( ! $this->round_tax_at_subtotal() ) {
							$taxes = Numbers::round( $taxes );
						}

						$discount_tax += $taxes;
					}
				}

				$this->set_voucher_tax( $discount_tax );
			}
		}

		return true;
	}

	public function remove_discounts() {
		foreach( $this->get_items( $this->get_item_types_for_totals() ) as $item ) {
			$item->set_line_total( $item->get_line_subtotal() );
			$item->set_total_tax( $item->get_subtotal_tax() );
		}

		$this->calculate_totals( true );
	}

	/**
	 * After applying coupons via the WC_Discounts class, update line items.
	 *
	 * @since 3.2.0
	 * @param Discounts $discounts Discounts class.
	 */
	protected function set_item_discount_amounts( $discounts ) {
		$item_discounts = $discounts->get_discounts_by_item();

		if ( $item_discounts ) {
			foreach ( $item_discounts as $item_id => $amount ) {
				if ( $item = $this->get_item( $item_id ) ) {
					$item->set_line_total( max( 0, $item->get_line_total() - $amount ) );
				}
			}
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Functions for setting invoice data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	*/

	/**
	 * Set currency.
	 *
	 * @param string $currency Currency code.
	 */
	public function set_currency( $currency ) {
		$this->set_prop( 'currency', $currency );
	}

	public function set_vat_id( $vat_id ) {
		$this->set_prop( 'vat_id', $vat_id );
	}

	public function set_billing_vat_id( $vat_id ) {
		$this->set_address_prop( 'vat_id', $vat_id );
	}

	public function set_shipping_vat_id( $vat_id ) {
		$this->set_shipping_address_prop( 'vat_id', $vat_id );
	}

	public function set_address( $address ) {
		$address = empty( $address ) ? array() : (array) $address;

		/**
		 * Getter and setter of billing address vat id are prefixed.
		 * Need to remove the billing VAT ID here to prevent the set_vat_id setter
		 * to be called by the Document class.
		 */
		if ( array_key_exists( 'vat_id', $address ) ) {
			$this->set_billing_vat_id( $address['vat_id'] );

			unset( $address['vat_id'] );
		}

		parent::set_address( $address );
	}

	protected function set_shipping_address_prop( $prop, $data ) {
		$address          = $this->get_shipping_address( 'edit' );
		$address[ $prop ] = $data;

		$this->set_prop( 'shipping_address', $address );
	}

	/**
	 * Set whether invoice prices include tax or not.
	 *
	 * @param bool|string $value Either bool or string (yes/no).
	 */
	public function set_prices_include_tax( $value ) {
		$this->set_prop( 'prices_include_tax', sab_string_to_bool( $value ) );
	}

	/**
	 * Set the tax display mode.
	 *
	 * @param string $value Either incl or excl.
	 */
	public function set_tax_display_mode( $value ) {
		if ( ! in_array( $value, array( 'incl', 'excl' ) ) ) {
			$value = 'incl';
		}

		$this->set_prop( 'tax_display_mode', $value );
	}

	/**
	 * Set whether invoice has a VAT reverse charge.
	 *
	 * @param bool|string $value Either bool or string (yes/no).
	 */
	public function set_is_reverse_charge( $value ) {
		$is_reverse_charge = sab_string_to_bool( $value );

		if ( $is_reverse_charge ) {
			$this->set_is_taxable( false );
		}

		$this->set_prop( 'is_reverse_charge', $is_reverse_charge );
	}

	/**
	 * Set whether invoice is part of the One Stop Shop rule.
	 *
	 * @param bool|string $value Either bool or string (yes/no).
	 */
	public function set_is_oss( $value ) {
		$this->set_prop( 'is_oss', sab_string_to_bool( $value ) );
	}

	/**
	 * Set invoice country.
	 *
	 * @param string $country The country in ISO format.
	 */
	public function set_shipping_country( $country ) {
		$this->set_shipping_address_prop( 'country', substr( $country, 0, 2 ) );
	}

	/**
	 * Set invoice shipping postcode.
	 *
	 * @param string $postcode The postcode.
	 */
	public function set_shipping_postcode( $postcode ) {
		$this->set_shipping_address_prop( 'postcode', $postcode );
	}

	/**
	 * Set shipment address.
	 *
	 * @param string[] $address The address props.
	 */
	public function set_shipping_address( $address ) {
		$address = empty( $address ) ? array() : (array) $address;

		foreach( $address as $prop => $value ) {
			$setter = "set_shipping_{$prop}";

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->{$setter}( $value );
			} else {
				$this->set_shipping_address_prop( $prop, $value );
			}
		}
	}

	/**
	 * Set whether invoice is taxable or not.
	 *
	 * @param bool|string $value Either bool or string (yes/no).
	 */
	public function set_is_taxable( $value ) {
		$this->set_prop( 'is_taxable', sab_string_to_bool( $value ) );
	}

	public function set_stores_vouchers_as_discount( $value ) {
		$this->set_prop( 'stores_vouchers_as_discount', sab_string_to_bool( $value ) );
	}

	/**
	 * Set whether invoice taxes are round at subtotal or not.
	 *
	 * @param bool|string $value Either bool or string (yes/no).
	 */
	public function set_round_tax_at_subtotal( $value ) {
		$this->set_prop( 'round_tax_at_subtotal', sab_string_to_bool( $value ) );
	}

	public function set_order_id( $value ) {
		$this->set_reference_id( $value );
	}

	public function set_order_number( $value ) {
		$this->set_reference_number( $value );
	}

	public function set_order_type( $value ) {
		$this->set_reference_type( $value );
	}

	public function set_reference_id( $reference_id ) {
		parent::set_reference_id( $reference_id );

		$this->order = null;
	}

	/**
	 * Set invoice total amount.
	 *
	 * @param $value
	 */
	public function set_total( $value ) {
		$this->set_prop( 'total', sab_format_decimal( $value, sab_get_price_decimals() ) );
	}

	/**
	 * Set invoice total amount.
	 *
	 * @param $value
	 */
	public function set_subtotal( $value ) {
		$this->set_prop( 'subtotal', sab_format_decimal( $value, sab_get_price_decimals() ) );
	}

	/**
	 * Set invoice total paid amount.
	 *
	 * @param $value
	 */
	public function set_total_paid( $value ) {
		$this->set_prop( 'total_paid', sab_format_decimal( $value, sab_get_price_decimals() ) );
	}

	/**
	 * Set product total amount.
	 *
	 * @param $value
	 */
	public function set_product_total( $value ) {
		$this->set_prop( 'product_total', sab_format_decimal( $value ) );
	}

	/**
	 * Set product total amount.
	 *
	 * @param $value
	 */
	public function set_product_subtotal( $value ) {
		$this->set_prop( 'product_subtotal', sab_format_decimal( $value ) );
	}

	/**
	 * Set voucher total amount.
	 *
	 * @param $value
	 */
	public function set_voucher_total( $value ) {
		$this->set_prop( 'voucher_total', sab_format_decimal( $value ) );
	}

	/**
	 * Set voucher tax amount.
	 *
	 * @param $value
	 */
	public function set_voucher_tax( $value ) {
		$this->set_prop( 'voucher_tax', sab_format_decimal( $value ) );
	}

	/**
	 * Set shipping total amount.
	 *
	 * @param $value
	 */
	public function set_shipping_total( $value ) {
		$this->set_prop( 'shipping_total', sab_format_decimal( $value ) );
	}

	/**
	 * Set shipping total amount.
	 *
	 * @param $value
	 */
	public function set_shipping_subtotal( $value ) {
		$this->set_prop( 'shipping_subtotal', sab_format_decimal( $value ) );
	}

	/**
	 * Set fee total amount.
	 *
	 * @param $value
	 */
	public function set_fee_total( $value ) {
		$this->set_prop( 'fee_total', sab_format_decimal( $value ) );
	}

	/**
	 * Set fee total amount.
	 *
	 * @param $value
	 */
	public function set_fee_subtotal( $value ) {
		$this->set_prop( 'fee_subtotal', sab_format_decimal( $value ) );
	}

	/**
	 * Set discount total amount.
	 *
	 * @param $value
	 */
	public function set_discount_total( $value ) {
		$this->set_prop( 'discount_total', sab_format_decimal( $value ) );
	}

	/**
	 * Set discount notice.
	 *
	 * @param $value
	 */
	public function set_discount_notice( $value ) {
		$this->set_prop( 'discount_notice', $value );
	}

	public function add_discount_notice( $notice ) {
		$notices   = array_filter( explode( ', ', trim( $this->get_discount_notice() ) ) );
		$notices[] = $notice;

		$this->set_discount_notice( implode( ', ', $notices ) );
	}

	/**
	 * Set total tax amount.
	 *
	 * @param $value
	 */
	public function set_total_tax( $value ) {
		$this->set_prop( 'total_tax', sab_format_decimal( $value ) );
	}

	/**
	 * Set total tax amount.
	 *
	 * @param $value
	 */
	public function set_subtotal_tax( $value ) {
		$this->set_prop( 'subtotal_tax', sab_format_decimal( $value ) );
	}

	/**
	 * Set product tax amount.
	 *
	 * @param $value
	 */
	public function set_product_tax( $value ) {
		$this->set_prop( 'product_tax', sab_format_decimal( $value ) );
		$this->update_tax_totals();
	}

	/**
	 * Set product tax amount.
	 *
	 * @param $value
	 */
	public function set_product_subtotal_tax( $value ) {
		$this->set_prop( 'product_subtotal_tax', sab_format_decimal( $value ) );
		$this->update_tax_subtotals();
	}

	/**
	 * Set shipping tax amount.
	 *
	 * @param $value
	 */
	public function set_shipping_tax( $value ) {
		$this->set_prop( 'shipping_tax', sab_format_decimal( $value ) );
		$this->update_tax_totals();
	}

	/**
	 * Set shipping tax amount.
	 *
	 * @param $value
	 */
	public function set_shipping_subtotal_tax( $value ) {
		$this->set_prop( 'shipping_subtotal_tax', sab_format_decimal( $value ) );
		$this->update_tax_subtotals();
	}

	/**
	 * Set fee tax amount.
	 *
	 * @param $value
	 */
	public function set_fee_tax( $value ) {
		$this->set_prop( 'fee_tax', sab_format_decimal( $value ) );
		$this->update_tax_totals();
	}

	/**
	 * Set fee tax amount.
	 *
	 * @param $value
	 */
	public function set_fee_subtotal_tax( $value ) {
		$this->set_prop( 'fee_subtotal_tax', sab_format_decimal( $value ) );
		$this->update_tax_subtotals();
	}

	/**
	 * Set discount tax amount.
	 *
	 * @param $value
	 */
	public function set_discount_tax( $value ) {
		$this->set_prop( 'discount_tax', sab_format_decimal( $value ) );
		$this->update_tax_totals();
	}

	/**
	 * Set date_paid.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 * @throws WC_Data_Exception
	 */
	public function set_date_due( $date = null ) {
		$this->set_date_prop( 'date_custom_extra', $date );
	}

	/**
	 * Set date_paid.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 * @throws WC_Data_Exception
	 */
	public function set_date_paid( $date = null ) {
		$this->set_date_prop( 'date_custom', $date );
	}

	/**
	 * Set date of service.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 * @throws WC_Data_Exception
	 */
	public function set_date_of_service( $date = null ) {
		$this->set_date_prop( 'date_of_service', $date );
	}

	/**
	 * Set date of service.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 * @throws WC_Data_Exception
	 */
	public function set_date_of_service_end( $date = null ) {
		$this->set_date_prop( 'date_of_service_end', $date );
	}

	/**
	 * Set payment method title.
	 *
	 * @param $value
	 */
	public function set_payment_method_title( $title ) {
		$this->set_prop( 'payment_method_title', $title );
	}

	/**
	 * Set payment method name.
	 *
	 * @param $value
	 */
	public function set_payment_method_name( $title ) {
		$this->set_prop( 'payment_method_name', $title );
	}

	/**
	 * Set payment transaction id.
	 *
	 * @param $id
	 */
	public function set_payment_transaction_id( $id ) {
		$this->set_prop( 'payment_transaction_id', $id );
	}

	/**
	 * Set payment status.
	 *
	 * @param string  $new_status Status to change the document to.
	 * @param boolean $manual_update Whether it is a manual status update or not.
	 * @return array  details of change
	 */
	public function set_payment_status( $new_status, $manual_update = false ) {
		$old_status = $this->get_payment_status();
		$new_status = 'sab-' === substr( $new_status, 0, 4 ) ? substr( $new_status, 4 ) : $new_status;

		$this->set_prop( 'payment_status', $new_status );

		$result = array(
			'from' => $old_status,
			'to'   => $new_status,
		);

		if ( true === $this->object_read && ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
			$this->payment_status_transition = array(
				'from'   => ! empty( $this->payment_status_transition['from'] ) ? $this->payment_status_transition['from'] : $result['from'],
				'to'     => $result['to'],
				'manual' => (bool) $manual_update,
			);

			if ( $manual_update ) {
				/**
				 * Action that fires after an invoice payment status has been updated manually.
				 *
				 * @param integer $document_id The document id.
				 * @param string  $status The new document status.
				 *
				 * @since 1.0.0
				 * @package Vendidero/StoreaBill
				 */
				do_action( 'storeabill_invoice_edit_payment_status', $this->get_id(), $result['to'] );
			}
		}

		if ( ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
			/**
			 * In case this is not a new document, make sure to update paid date only
			 * in case the object was successfully read from DB.
			 */
			if ( ( $this->get_id() > 0 && true === $this->object_read ) || $this->get_id() <= 0 ) {
				$this->maybe_set_paid();
			}
		}

		return $result;
	}

	protected function maybe_set_paid() {
		if ( 'complete' === $this->get_payment_status() ) {
			$this->set_date_paid( time() );
			$this->set_total_paid( $this->get_total() );
		} elseif ( 'pending' === $this->get_payment_status() ) {
			$this->set_date_paid( null );
			$this->set_total_paid( 0 );
		}
	}

	/**
	 * Updates payment status of an invoice immediately.
	 *
	 * @param string $new_status    Status to change the invoice to. No internal sab- prefix is required.
	 * @param bool   $manual        Is this a manual order status change?
	 * @return bool
	 */
	public function update_payment_status( $new_status, $manual = false ) {
		if ( ! $this->get_id() ) {
			return false;
		}

		try {
			$this->set_payment_status( $new_status, $manual );
			$this->save();
		} catch ( Exception $e ) {
			$logger = wc_get_logger();
			$logger->error(
				sprintf( 'Error updating payment status for invoice #%d', $this->get_id() ), array(
					'invoice'  => $this,
					'error'    => $e,
				)
			);

			$this->create_notice( _x( 'Update payment status event failed.', 'storeabill-core', 'woocommerce-germanized-pro' ) . ' ' . $e->getMessage(), 'error' );
			return false;
		}
		return true;
	}

	/**
	 * Helper method for get_prices_include_tax.
	 *
	 * @return bool
	 */
	public function prices_include_tax() {
		return $this->get_prices_include_tax();
	}

	/**
	 * Helper method for get_is_reverse_charge.
	 *
	 * @return bool
	 */
	public function is_reverse_charge() {
		return $this->get_is_reverse_charge();
	}

	/**
	 * Helper method for get_is_taxable.
	 *
	 * @return bool
	 */
	public function is_taxable() {
		return $this->get_is_taxable();
	}

	/**
	 * Helper method for get_round_tax_at_subtotal.
	 *
	 * @return bool
	 */
	public function round_tax_at_subtotal() {
		return $this->get_round_tax_at_subtotal();
	}

	/**
	 * Returns an array of cumulated tax items.
	 *
	 * @return TaxItem[]
	 */
	public function get_total_taxes() {
		return $this->get_tax_items( $this->get_item_types_for_tax_totals() );
	}

	/**
	 * Returns an array of cumulated tax items associated with subtotal.
	 *
	 * @return TaxItem[]
	 * @see TaxRate::get_merge_key()
	 */
	public function get_subtotal_taxes() {
		return apply_filters( $this->get_hook_prefix() . 'subtotal_taxes', $this->get_tax_items( 'product' ) + $this->get_tax_items( 'discount' ), $this );
	}

	/**
	 * Returns an array of cumulated tax items associated with products.
	 *
	 * @return TaxItem[]
	 */
	public function get_product_taxes() {
		return $this->get_tax_items( 'product' );
	}

	/**
	 * Returns an array of cumulated tax items associated with fees.
	 *
	 * @return TaxItem[]
	 */
	public function get_fee_taxes() {
		return $this->get_tax_items( 'fee' );
	}

	/**
	 * Returns an array of cumulated tax items associated with shipping.
	 *
	 * @return TaxItem[]
	 */
	public function get_shipping_taxes() {
		return $this->get_tax_items( 'shipping' );
	}

	/**
	 * Returns an array of cumulated tax items associated with discounts.
	 *
	 * @return TaxItem[]
	 */
	public function get_discount_taxes() {
		return $this->get_tax_items( 'discount' );
	}

	/**
	 * Returns a formatted price based on internal options.
	 *
	 * @param string $price
	 *
	 * @return string
	 */
	public function get_formatted_price( $price, $type = '' ) {
		/**
		 * Discounts and vouchers should be negative.
		 */
		if ( ( strstr( $type, 'discount' ) || strstr( $type, 'voucher' ) ) && $price > 0 ) {
			$price *= -1;
		}

		return sab_format_price( $price, array( 'currency' => $this->get_currency() ) );
	}

	/**
	 * Returns invoice tax items.
	 *
	 * @param string|array $types Optionally choose specific tax types to filter the items (e.g. shipping or product).
	 *
	 * @return TaxItem[]
	 */
	public function get_tax_items( $types = '' ) {
		$types     = array_filter( ( is_array( $types ) ? $types : array( $types ) ) );
		$tax_items = array();

		foreach( $this->get_items( 'tax' ) as $key => $item ) {

			if ( ! empty( $types ) && ! in_array( $item->get_tax_type(), $types ) ) {
				continue;
			}

			$tax_items[] = $item;
		}

		return $tax_items;
	}

	public function get_total_net( $total_type = 'total', $round = true ) {
		$getter_total = "get_{$total_type}_total";

		if ( strpos( $total_type, 'total' ) !== false ) {
			$getter_total = 'get_' . $total_type;
		}

		$getter_tax   = 'get_' . $total_type . '_tax';
		$total        = 0;
		$tax_total    = 0;

		if ( 'get_total_tax' === $getter_tax && ! $round ) {
			$getter_tax = 'get_calculated_total_tax';
		} elseif( 'get_subtotal_tax' === $getter_tax && ! $round ) {
			$getter_tax = 'get_calculated_subtotal_tax';
		}

		try {
			if ( is_callable( array( $this, $getter_total ) ) ) {
				$reflection = new ReflectionMethod( $this, $getter_total );

				if ( $reflection->isPublic() ) {
					$total = $this->{$getter_total}();
				}
			}

			if ( is_callable( array( $this, $getter_tax ) ) ) {
				$reflection = new ReflectionMethod( $this, $getter_tax );

				if ( $reflection->isPublic() ) {
					$tax_total = $this->{$getter_tax}();
				}
			}
		} catch ( Exception $e ) {}

		/**
		 * In case the invoice includes a voucher add the voucher amount to certain net types.
		 */
		if ( $this->has_voucher() ) {
			if ( in_array( $getter_total, array( 'get_total' ) ) ) {
				$total += $this->get_voucher_total();
			} elseif ( $this->stores_vouchers_as_discount() ) {
				$item_type_getter = array();

				foreach( $this->get_item_types_for_tax_totals() as $item_type ) {
					$item_type_getter[ "get_{$item_type}_total" ] = $item_type;
				}

				if ( array_key_exists( $getter_total, $item_type_getter ) ) {
					$item_type    = $item_type_getter[ $getter_total ];
					$discount     = $this->get_item_type_discount( $item_type );
					$discount_tax = $this->get_item_type_discount_tax( $item_type );

					$total += $discount;

					if ( ! $this->prices_include_tax() ) {
						$total -= $discount_tax;
					}
				}
			}
		}

		// Total is stored incl tax
		$net_total         = sab_format_decimal( ( $total - $tax_total ) );
		$net_total_rounded = sab_format_decimal( $net_total, '' );

		if ( $net_total_rounded == 0 ) {
			$net_total = 0;
		}

		return $net_total;
	}

	public function calculate_totals( $and_taxes = true ) {

		if ( $and_taxes || ! $this->is_taxable() ) {
			$this->update_taxes();
		}

		$items         = $this->get_items();
		$errors        = new WP_Error();
		$totals        = array_fill_keys( array_keys( array_flip( $this->get_item_types_for_totals() ) ), 0 );
		$subtotals     = $totals;
		$tax_totals    = array_fill_keys( array_keys( array_flip( $this->get_item_types_for_tax_totals() ) ), 0 );
		$tax_subtotals = $tax_totals;

		foreach ( $items as $item ) {
			if ( is_a( $item, '\Vendidero\StoreaBill\Interfaces\Summable' ) && array_key_exists( $item->get_item_type(), $totals ) ) {
				$item->calculate_totals();

				$total    = sab_add_number_precision( $item->get_total(), false );
				$subtotal = sab_add_number_precision( $item->get_subtotal(), false );

				$totals[ $item->get_item_type() ]    += ( ! $item->round_tax_at_subtotal() ) ? Numbers::round( $total ) : $total;
				$subtotals[ $item->get_item_type() ] += ( ! $item->round_tax_at_subtotal() ) ? Numbers::round( $subtotal ) : $subtotal;
			}

			if ( is_a( $item, '\Vendidero\StoreaBill\Interfaces\TaxContainable' ) && array_key_exists( $item->get_tax_type(), $tax_totals ) ) {
				$total    = sab_add_number_precision( $item->get_total_tax(), false );
				$subtotal = sab_add_number_precision( $item->get_subtotal_tax(), false );

				$tax_totals[ $item->get_tax_type() ]    += $total;
				$tax_subtotals[ $item->get_tax_type() ] += $subtotal;
			}
		}

		foreach( $totals as $key => $item_total ) {
			$item_total = sab_remove_number_precision( $item_total );

			try {
				$setter = "set_{$key}_total";
				if ( is_callable( array( $this, $setter ) ) ) {
					$reflection = new ReflectionMethod( $this, $setter );

					if ( $reflection->isPublic() ) {
						$this->{$setter}( $item_total );
					}
				} else {
					// Save as meta data
					$this->update_meta_data( $key . '_total', sab_format_decimal( $item_total ) );
				}
			} catch ( Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		foreach( $subtotals as $key => $item_total ) {
			$item_total = sab_remove_number_precision( $item_total );

			try {
				$setter = "set_{$key}_subtotal";
				if ( is_callable( array( $this, $setter ) ) ) {
					$reflection = new ReflectionMethod( $this, $setter );

					if ( $reflection->isPublic() ) {
						$this->{$setter}( $item_total );
					}
				} else {
					// Save as meta data
					$this->update_meta_data( $key . '_subtotal', sab_format_decimal( $item_total ) );
				}
			} catch ( Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		foreach( $tax_totals as $key => $item_tax_total ) {
			$item_tax_total = sab_remove_number_precision( $item_tax_total );

			try {
				$setter = "set_{$key}_tax";
				if ( is_callable( array( $this, $setter ) ) ) {
					$reflection = new ReflectionMethod( $this, $setter );

					if ( $reflection->isPublic() ) {
						$this->{$setter}( $item_tax_total );
					}
				} else {
					// Save as meta data
					$this->update_meta_data( $key . '_tax', sab_format_decimal( $item_tax_total ) );
				}
			} catch ( Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		foreach( $tax_subtotals as $key => $item_tax_total ) {
			$item_tax_total = sab_remove_number_precision( $item_tax_total );

			try {
				$setter = "set_{$key}_subtotal_tax";
				if ( is_callable( array( $this, $setter ) ) ) {
					$reflection = new ReflectionMethod( $this, $setter );

					if ( $reflection->isPublic() ) {
						$this->{$setter}( $item_tax_total );
					}
				} else {
					// Save as meta data
					$this->update_meta_data( $key . '_subtotal_tax', sab_format_decimal( $item_tax_total ) );
				}
			} catch ( Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		$total        = sab_remove_number_precision( array_sum( $totals ) );
		$subtotal     = sab_remove_number_precision( array_sum( $subtotals ) );

		$total_tax    = sab_remove_number_precision( array_sum( $tax_totals ) );
		$subtotal_tax = sab_remove_number_precision( array_sum( $tax_subtotals ) );

		$discount_total = $subtotal - $total;
		$discount_tax   = $subtotal_tax - $total_tax;

		if ( $this->stores_vouchers_as_discount() && $this->has_voucher() ) {
			/**
			 * Very specific to net based invoices containing vouchers.
			 * Discounts (per items) need additional taxes added on top to
			 * reflect the voucher amount. There is no other way to calculate
			 * the amount based on pure item discounts.
			 */
			if ( ! $this->prices_include_tax() && 'incl' === $this->get_tax_display_mode() ) {
				$discount_item_taxes = 0;

				foreach( $this->get_items( $this->get_item_types_for_totals() ) as $item ) {
					$discount_item_total = $item->get_discount_total();
					$discount_item_tax   = sab_add_number_precision( array_sum( Tax::calc_tax( $discount_item_total, $item->get_tax_rates(), false ) ), false );

					$discount_item_taxes += ( $item->round_tax_at_subtotal() ? $discount_item_tax : Numbers::round( $discount_item_tax ) );
				}

				if ( $discount_item_taxes > 0 ) {
					$discount_item_taxes = sab_remove_number_precision( $discount_item_taxes );

					$discount_total += $discount_item_taxes;

					$voucher_total = $this->get_voucher_total() - $this->get_voucher_tax();
					$voucher_total += $discount_item_taxes;

					$this->set_voucher_total( $voucher_total );
					$this->set_voucher_tax( $discount_item_taxes );
				}
			}
		}

		$this->set_discount_total( $discount_total );
		$this->set_discount_tax( wc_round_tax_total( $discount_tax ) );

		$this->update_tax_totals();
		$this->update_total();

		return sizeof( $errors->get_error_codes() ) ? $errors : true;
	}

	/**
	 * @param ProductItem $item
	 * @param string $for
	 *
	 * @return mixed|void
	 */
	protected function item_is_tax_share_exempt( $item, $for = 'shipping' ) {
		$is_exempt = false;

		if ( 'shipping' === $for ) {
			// Virtual items do not account for shipping.
			if ( $item->is_virtual() ) {
				$is_exempt = true;
			}
		}

		if ( ! $item->is_taxable() || ! $item->has_taxes() ) {
			$is_exempt = true;
		}

		return apply_filters( "{$this->get_general_hook_prefix()}item_is_tax_share_exempt", $is_exempt, $item, $for, $this );
	}

	public function get_tax_shares( $type = 'shipping' ) {
		$taxes      = array();
		$tax_shares = array();
		$total      = 0;

		foreach( $this->get_items( 'product' ) as $item ) {

			if ( $this->item_is_tax_share_exempt( $item, $type ) ) {
				continue;
			}

			$product_tax_rates = $item->get_tax_rates();
			$total            += apply_filters( "{$this->get_general_hook_prefix()}calculate_tax_shares_net_based", true, $item ) ? $item->get_total_net() : $item->get_total();

			foreach( $item->get_tax_rates() as $key => $rate ) {
				if ( ! isset( $taxes[ $key ] ) ) {
					$taxes[ $key ] = 0;
				}

				$taxes[ $key ] += ( $item->get_total_net() / sizeof( $product_tax_rates ) );
			}
		}

		if ( ! empty( $taxes ) ) {
			$default = ( $total <= 0 ? 1 / sizeof( $taxes ) : 0 );

			foreach ( $taxes as $key => $tax_total ) {
				$tax_shares[ $key ] = sab_format_decimal( $total > 0 ? $tax_total / $total : $default );
			}
		}

		return $tax_shares;
	}

	public function add_item( $item ) {
		if ( $this->is_finalized() ) {
			return false;
		}

		/**
		 * Force syncing price taxation mode for certain item types.
		 */
		if ( is_a( $item, '\Vendidero\StoreaBill\Interfaces\Taxable' ) ) {
			if ( ! in_array( $item->get_item_type(), $this->get_item_types_additional_costs() ) ) {
				$item->set_prices_include_tax( $this->prices_include_tax() );
			}
		}

		return parent::add_item( $item );
	}

	public function update_taxes() {

		/**
		 * Mark items as being non-taxable in case this is invoice is not taxable.
		 */
		if ( ! $this->is_taxable() ) {
			foreach( $this->get_items( $this->get_item_types_for_tax_totals() ) as $item ) {
				if ( is_a( $item, '\Vendidero\StoreaBill\Interfaces\Taxable' ) ) {
					$item->set_is_taxable( false );
				}
			}
		}

		$item_types                 = $this->get_item_types_for_tax_totals();
		$item_types_without_product = array_diff( $item_types, array( 'product' ) );
		$item_taxes                 = array();

		// Calculate taxes for products first
		foreach ( $this->get_items( 'product' ) as $item ) {

			if ( is_a( $item, '\Vendidero\StoreaBill\Interfaces\Taxable' ) ) {

				if ( ! in_array( 'product', $this->get_item_types_additional_costs() ) ) {
					$item->set_prices_include_tax( $this->prices_include_tax() );
				}

				$item->calculate_tax_totals();

				$item_taxes = array_merge( $item_taxes, array_keys( $item->get_taxes() ) );
			}
		}

		foreach ( $this->get_items( $item_types_without_product ) as $item ) {

			if ( is_a( $item, '\Vendidero\StoreaBill\Interfaces\Taxable' ) ) {
				/**
				 * Do not force the same tax rule (e.g. incl or excl) for additional costs (such as shipping or fees)
				 * to make sure shipping may be treated as incl tax even though the parent document is treated excl tax.
				 */
				if ( ! in_array( $item->get_item_type(), $this->get_item_types_additional_costs() ) ) {
					$item->set_prices_include_tax( $this->prices_include_tax() );
				}

				$item->calculate_tax_totals();

				$item_taxes = array_merge( $item_taxes, array_keys( $item->get_taxes() ) );
			}
		}

		// Remove unused taxes - e.g. taxes which are not linked to an item.
		foreach( $this->get_items( 'tax' ) as $tax_item ) {
			if ( ! in_array( $tax_item->get_key(), $item_taxes ) ) {
				$this->remove_item( $tax_item->get_key() );
			}
		}

		// Reset tax totals
		$this->tax_totals = null;
	}

	public function get_tax_rate_percentages() {
		$tax_percentages = array();

		foreach( $this->get_tax_totals() as $total ) {
			$percent = $total->get_tax_rate()->get_percent();

			if ( ! in_array( $percent, $tax_percentages ) ) {
				$tax_percentages[] = $percent;
			}
		}

		return $tax_percentages;
	}

	/**
	 * @return TaxTotal[]
	 */
	public function get_tax_totals( $item_type = 'total' ) {
		if ( is_null( $this->tax_totals ) || ! isset( $this->tax_totals[ $item_type ] ) ) {
			$this->tax_totals          = array();
			$this->tax_totals['total'] = array();

			foreach( $this->get_item_types_for_tax_totals() as $tax_item_type ) {
				$this->tax_totals[ $tax_item_type ] = array();
			}

			foreach ( $this->get_items( 'tax' ) as $key => $tax ) {
				// E.g. fee, shipping or product
				$tax_item_type = $tax->get_tax_type();

				if ( ! isset( $this->tax_totals[ $tax_item_type ] ) ) {
					$this->tax_totals[ $tax_item_type ] = array();
				}

				if ( $tax_rate = $tax->get_tax_rate() ) {
					$merge_key = $tax_rate->get_merge_key();

					/**
					 * Document totals
					 */
					if ( isset( $this->tax_totals['total'][ $merge_key ] ) ) {
						$this->tax_totals['total'][ $merge_key ]->add_tax( $tax );
					} else {
						$merged_tax = new TaxTotal();

						$merged_tax->set_tax_rate( $tax_rate );
						$merged_tax->add_tax( $tax );

						$this->tax_totals['total'][ $merge_key ] = $merged_tax;
					}

					/**
					 * Item type specific tax totals (e.g. shipping)
					 */
					if ( isset( $this->tax_totals[ $tax_item_type ][ $merge_key ] ) ) {
						$this->tax_totals[ $tax_item_type ][ $merge_key ]->add_tax( $tax );
					} else {
						$merged_tax = new TaxTotal();

						$merged_tax->set_tax_rate( $tax_rate );
						$merged_tax->add_tax( $tax );

						$this->tax_totals[ $tax_item_type ][ $merge_key ] = $merged_tax;
					}
				}
			}
		}

		return isset( $this->tax_totals[ $item_type ] ) ? $this->tax_totals[ $item_type ] : $this->tax_totals['totals'];
	}

	/**
	 * Makes sure that the total amount paid does not exceed the total amount.
	 */
	protected function update_total_paid() {
		$total_paid = $this->get_total_paid();
		$total      = $this->get_total();

		if ( $total_paid > $total ) {
			$this->set_total_paid( $total );
		}
	}

	public function update_total() {
		$total    = 0;
		$subtotal = 0;
		$errors   = new WP_Error();

		foreach( $this->get_item_types_for_totals() as $item_type ) {
			try {
				$getter          = "get_{$item_type}_total";
				$subtotal_getter = "get_{$item_type}_subtotal";

				if ( is_callable( array( $this, $getter ) ) ) {
					$reflection = new ReflectionMethod( $this, $getter );

					if ( $reflection->isPublic() ) {
						$total += $this->{$getter}( 'total' );
					}
				} else {
					// Try to get total from meta
					$total += $this->get_meta( $item_type . '_total', true, 'total' );
				}

				if ( is_callable( array( $this, $subtotal_getter ) ) ) {
					$reflection = new ReflectionMethod( $this, $subtotal_getter );

					if ( $reflection->isPublic() ) {
						$subtotal += $this->{$subtotal_getter}( 'total' );
					}
				} else {
					// Try to get total from meta
					$subtotal += $this->get_meta( $item_type . '_subtotal', true, 'total' );
				}
			} catch ( Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		if ( $this->stores_vouchers_as_discount() ) {
			/**
			 * Specific to net based invoices containing vouchers.
			 * Lets reduce the total price by the voucher tax added during total calculation.
			 */
			if ( $this->has_voucher() && ! $this->prices_include_tax() ) {
				$total = max( $total - $this->get_voucher_tax(), 0 );
			}
		}

		$this->set_total( Numbers::round( $total, sab_get_price_decimals() ) );
		$this->set_subtotal( Numbers::round( $subtotal, sab_get_price_decimals() ) );

		$this->update_total_paid();

		return sizeof( $errors->get_error_codes() ) ? $errors : true;
	}

	/**
	 * Returns total tax amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_calculated_total_tax() {
		$total  = 0;
		$errors = new WP_Error();

		foreach( $this->get_item_types_for_tax_totals() as $item_type ) {
			try {
				$getter = "get_{$item_type}_tax";
				if ( is_callable( array( $this, $getter ) ) ) {
					$reflection = new ReflectionMethod( $this, $getter );

					if ( $reflection->isPublic() ) {
						$result = $this->{$getter}( 'total' );

						if ( is_numeric( $result ) ) {
							$total += $result;
						}
					}
				} else {
					// Try to get total from meta
					$result = $this->get_meta( $item_type . '_tax', true, 'total' );

					if ( is_numeric( $result ) ) {
						$total += $result;
					}
				}
			} catch ( Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		return $total;
	}

	/**
	 * Returns total tax amount.
	 *
	 * @param string $context
	 *
	 * @return float
	 */
	public function get_calculated_subtotal_tax() {
		$total  = 0;
		$errors = new WP_Error();

		foreach( $this->get_item_types_for_tax_totals() as $item_type ) {
			try {
				$getter = "get_{$item_type}_subtotal_tax";
				if ( is_callable( array( $this, $getter ) ) ) {
					$reflection = new ReflectionMethod( $this, $getter );

					if ( $reflection->isPublic() ) {
						$result = $this->{$getter}( 'total' );

						if ( is_numeric( $result ) ) {
							$total += $result;
						}
					}
				} else {
					// Try to get total from meta
					$result = $this->get_meta( $item_type . '_subtotal_tax', true, 'total' );

					if ( is_numeric( $result ) ) {
						$total += $result;
					}
				}
			} catch ( Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		return $total;
	}

	public function update_tax_totals() {
		$this->set_total_tax( wc_round_tax_total( $this->get_calculated_total_tax() ) );

		return true;
	}

	public function update_tax_subtotals() {
		$this->set_subtotal_tax( wc_round_tax_total( $this->get_calculated_subtotal_tax() ) );

		return true;
	}

	/**
	 * Handle the status transition.
	 */
	protected function payment_status_transition() {
		$status_transition = $this->payment_status_transition;

		// Reset payment status transition variable.
		$this->payment_status_transition = false;

		if ( $status_transition ) {
			try {
				/**
				 * Action that fires before an invoice payment status transition happens.
				 *
				 * @param integer  $invoice_id The invoice id.
				 * @param Invoice  $invoice The invoice object.
				 * @param array    $status_transition The status transition data.
				 *
				 * @since 1.0.0
				 * @package Vendidero/StoreaBill
				 */
				do_action( 'storeabill_invoice_before_payment_status_change', $this->get_id(), $this, $this->payment_status_transition );

				$status_to          = $status_transition['to'];
				$status_hook_prefix = 'storeabill_' . $this->get_type() . '_payment_status';

				/**
				 * Action that indicates invoice payment status change to a specific status.
				 *
				 * The dynamic portion of the hook name, `$status_hook_prefix` constructs a unique prefix
				 * based on the invoice type. `$status_to` refers to the new payment status.
				 *
				 * Example hook name: `storeabill_invoice_payment_status_paid`
				 *
				 * @param integer $invoice_id The invoice id.
				 * @param Invoice $invoice The invoice object.
				 *
				 * @since 1.0.0
				 * @package Vendidero/StoreaBill
				 */
				do_action( "{$status_hook_prefix}_$status_to", $this->get_id(), $this );

				if ( ! empty( $status_transition['from'] ) ) {
					$status_from = $status_transition['from'];

					/* translators: 1: old payment status 2: new payment status */
					$transition_note = sprintf( _x( 'Invoice payment status changed from %1$s to %2$s.', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_invoice_payment_status_name( $status_transition['from'] ), sab_get_invoice_payment_status_name( $status_transition['to'] ) );

					// Note the transition occurred.
					$this->add_status_transition_note( $transition_note, $status_transition );

					/**
					 * Action that indicates invoice payment status change from a specific status to a specific status.
					 *
					 * The dynamic portion of the hook name, `$status_hook_prefix` constructs a unique prefix
					 * based on the invoice type. `$status_from` refers to the old invoice payment status.
					 * `$status_to` refers to the new status.
					 *
					 * Example hook name: `storeabill_invoice_payment_status_pending_to_complete`
					 *
					 * @param integer $invoice_id The invoice id.
					 * @param Invoice $invoice The invoice object.
					 *
					 * @since 1.0.0
					 * @package Vendidero/StoreaBill
					 */
					do_action( "{$status_hook_prefix}_{$status_from}_to_{$status_to}", $this->get_id(), $this );

					/**
					 * Action that indicates invoice payment status change.
					 *
					 * @param integer $invoice_id The invoice id.
					 * @param string  $status_from The old invoice payment status.
					 * @param string  $status_to The new payment status.
					 * @param Invoice $invoice The invoice object.
					 *
					 * @since 1.0.0
					 * @package Vendidero/StoreaBill
					 */
					do_action( 'storeabill_invoice_payment_status_changed', $this->get_id(), $status_from, $status_to, $this );
				} else {
					/* translators: %s: new document status */
					$transition_note = sprintf( _x( 'Invoice payment status set to %s.', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_invoice_payment_status_name( $status_transition['to'] ) );

					// Note the transition occurred.
					$this->add_status_transition_note( $transition_note, $status_transition );
				}
			} catch ( Exception $e ) {
				$logger = wc_get_logger();
				$logger->error(
					sprintf( 'Status transition of invoice #%d errored!', $this->get_id() ), array(
						'invoice' => $this,
						'error'   => $e,
					)
				);
				$this->create_notice( _x( 'Error during payment status transition.', 'storeabill-core', 'woocommerce-germanized-pro' ) . ' ' . $e->getMessage(), 'error' );
			}
		}
	}

	protected function status_transition() {
		parent::status_transition();

		$this->payment_status_transition();
	}

	protected function get_finalize_excluded_props() {
		$excluded = array(
			'payment_status',
			'total_paid',
			'status',
			'date_modified',
			'date_custom',
			'date_custom_extra',
			'date_sent',
			'external_sync_handlers',
			'payment_method_name',
			'payment_method_title',
			'payment_transaction_id'
		);

		$path 	= ! empty( $this->data['relative_path'] ) ? sab_get_absolute_file_path( $this->data['relative_path'] ) : false;
		$exists = false;

		if ( $path && file_exists( $path ) ) {
			$exists = true;
		}

		if ( ! $this->has_file() || ! $exists ) {
			$excluded = array_merge( $excluded, array( 'relative_path' ) );
		}

		return $excluded;
	}

	protected function save_items() {
		/**
		 * Do not allow items to be overridden in case
		 * the invoice is finalized.
		 */
		if ( $this->is_finalized() ) {
			return false;
		}

		return parent::save_items();
	}

	/**
	 * Save data to the database.
	 */
	public function save() {
		/**
		 * In case the invoice is finalized we need to make sure
		 * that updates are only available to specific fields.
		 */
		if ( $this->is_finalized() ) {
			$changes  = $this->get_changes();
			$excluded = $this->get_finalize_excluded_props();

			if ( ! empty( $changes ) ) {
				$this->changes = array_intersect_key( $changes, array_flip( $excluded ) );
			}
		}

		return parent::save();
	}

	public function remove_items( $type = '' ) {
		if ( $this->is_finalized() ) {
			return false;
		}

		return parent::remove_items( $type );
	}

	public function remove_item( $item_key ) {
		if ( $this->is_finalized() ) {
			return false;
		}

		return parent::remove_item( $item_key );
	}

	/**
	 * Delete an object, set the ID to 0, and return result.
	 *
	 * @since  2.6.0
	 * @param  bool $force_delete Should the date be deleted permanently.
	 * @return bool result
	 */
	public function delete( $force_delete = false ) {
		if ( $this->is_finalized() && ! $force_delete ) {
			return false;
		}

		return parent::delete( $force_delete );
	}

	/**
	 * Sets a prop for a setter method.
	 *
	 * This stores changes in a special array so we can track what needs saving
	 * the the DB later.
	 *
	 * @since 3.0.0
	 * @param string $prop Name of prop to set.
	 * @param mixed  $value Value of the prop.
	 */
	protected function set_prop( $prop, $value ) {
		/**
		 * Prevent updates to fields in case the invoice is finalized.
		 */
		if ( true === $this->object_read && $this->is_finalized() && ! in_array( $prop, $this->get_finalize_excluded_props() ) ) {
			return;
		}

		parent::set_prop( $prop, $value );
	}
}