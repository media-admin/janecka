<?php

namespace Vendidero\StoreaBill\sevDesk;

use Vendidero\StoreaBill\API\RESTResponse;
use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\ExternalSync\SyncHandler;
use Vendidero\StoreaBill\Interfaces\ExternalSyncable;
use Vendidero\StoreaBill\Invoice\Cancellation;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Invoice\Item;
use Vendidero\StoreaBill\Invoice\ProductItem;
use Vendidero\StoreaBill\Invoice\Simple;
use Vendidero\StoreaBill\References\Product;
use Vendidero\StoreaBill\WooCommerce\Helper;
use Vendidero\StoreaBill\sevDesk\API\Auth;
use Vendidero\StoreaBill\sevDesk\API\Models;

defined( 'ABSPATH' ) || exit;

class Sync extends SyncHandler {

	/**
	 * @var Auth
	 */
	protected $auth_api = null;

	/**
	 * @var string[]
	 */
	protected $accounts = null;

	/**
	 * @var string[]
	 */
	protected $categories = null;

	protected $default_account = false;

	/**
	 * @var Models
	 */
	protected $api = null;

	public static function get_name() {
		return 'sevdesk';
	}

	public static function get_title() {
		return _x( 'sevDesk', 'sevdesk', 'woocommerce-germanized-pro' );
	}

	public static function get_description() {
		return _x( 'Transfer invoices and customer data to your sevDesk account.', 'sevdesk', 'woocommerce-germanized-pro' );
	}

	public static function get_supported_object_types() {
		return array( 'customer', 'invoice', 'invoice_cancellation' );
	}

	public static function get_api_type() {
		return 'REST';
	}

	public static function get_help_link() {
		return 'https://vendidero.de/dokument/sevdesk-schnittstelle';
	}

	public static function get_icon() {
		return trailingslashit( Package::get_url() ) . 'assets/icon.png';
	}

	public function __construct() {
		$this->auth_api = new Auth( $this );
		$this->api      = new Models( $this, $this->get_hook_prefix() );

		parent::__construct();
	}

	public function is_sandbox() {
		return false;
	}

	public function get_auth_token() {
		return $this->get_setting( 'token' );
	}

	/**
	 * @param \Vendidero\StoreaBill\Interfaces\Invoice|\Vendidero\StoreaBill\Interfaces\Customer $object
	 *
	 * @return \WP_Error|boolean
	 */
	public function sync( &$object ) {
		if ( ! $this->is_syncable( $object ) ) {
			return new \WP_Error( 'sync-error', _x( 'This object cannot be synced', 'sevdesk', 'woocommerce-germanized-pro' ) );
		}

		if ( is_a( $object, '\Vendidero\StoreaBill\Interfaces\Customer' ) ) {
			return $this->sync_customer( $object );
		} elseif( is_a( $object, '\Vendidero\StoreaBill\Interfaces\Invoice' ) ) {
			if ( 'simple' === $object->get_invoice_type() ) {
				return $this->sync_invoice( $object );
			} elseif( 'cancellation' === $object->get_invoice_type() ) {
				return $this->sync_cancellation( $object );
			}
		}

		return new \WP_Error( 'sync-error', _x( 'This object type is not supported by sevDesk', 'sevdesk', 'woocommerce-germanized-pro' ) );
	}

	/**
	 * @param \Vendidero\StoreaBill\Interfaces\Customer $customer
	 */
	public function get_customer_details( $customer ) {
		if ( $customer->has_been_externally_synced( self::get_name() ) ) {
			$sync_data = $customer->get_external_sync_handler_data( self::get_name() );
			$result    = $this->get_api()->get_contact( $sync_data->get_id() );

			if ( ! $this->get_api()->has_failed( $result ) ) {
				$customer        = $result['objects'][0];
				$customer_number = ! empty( $customer['customerNumber'] ) ? $customer['customerNumber'] : $customer['id'];

				return array(
					'id'    => $sync_data->get_id(),
					/* translators: 1: customer first name 2: customer last name 3: customer number */
					'label' => sprintf( esc_html_x( '%1$s %2$s (%3$s)', 'sevdesk', 'woocommerce-germanized-pro' ), $customer['surename'], $customer['familyname'], $customer_number ),
					'url'   => $this->get_customer_link( $sync_data->get_id() ),
				);
			}
		}

		return false;
	}

	public function get_customer_link( $id ) {
		return trailingslashit( Package::get_app_url() ) . 'crm/detail/id/' . $id;
	}

	public function search_customers( $search ) {
		$results  = $this->get_api()->search_contacts( $search );
		$response = array();

		if ( ! $this->get_api()->has_failed( $results ) ) {
			foreach( $results['objects'] as $customer ) {
				/**
				 * Exclude companies from searching.
				 */
			    if ( isset( $customer['name'] ) && ! empty( $customer['name'] ) ) {
			        continue;
                }

				$customer_number = ! empty( $customer['customerNumber'] ) ? $customer['customerNumber'] : $customer['id'];
				/* translators: 1: customer first name 2: customer last name 3: customer number */
				$response[ $customer['id'] ] = esc_html( sprintf( _x( '%1$s %2$s (%3$s)', 'sevdesk', 'woocommerce-germanized-pro' ), $customer['surename'], $customer['familyname'], $customer_number ) );
			}
		}

		return $response;
	}

	/**
	 * @param \Vendidero\StoreaBill\Interfaces\Customer $customer
	 */
	protected function sync_customer( $customer ) {
		$data = array(
			'customer'         => $customer,
			'first_name'       => $customer->get_first_name(),
			'last_name'        => $customer->get_last_name(),
			'title'            => $customer->get_formatted_title(),
			'academic_title'   => '',
			'email'            => $customer->get_email(),
			'phone'            => $customer->get_phone(),
			'company'          => $customer->get_company_name(),
			'number'           => $customer->get_id(),
			'vat_id'           => $customer->get_vat_id(),
			'is_vat_exempt'    => $customer->is_vat_exempt(),
			'address'          => array(
				'street'       => $customer->get_billing_address(),
				'zip'          => $customer->get_billing_postcode(),
				'country'      => $customer->get_billing_country(),
				'city'         => $customer->get_billing_city(),
			),
			'shipping_address' => array(
				'street'       => $customer->get_shipping_address(),
				'zip'          => $customer->get_shipping_postcode(),
				'country'      => $customer->get_shipping_country(),
				'city'         => $customer->get_shipping_city(),
			),
		);

		$contact = new Contact( apply_filters( "{$this->get_hook_prefix()}contact_data", $data, $customer, $this ), $this->get_api(), $customer->get_external_sync_handler_data( self::get_name() ) );
		$result  = $contact->save();

		if ( true === $result ) {
			$customer->update_external_sync_handler( self::get_name(), $contact->get_sync_data() );
		}

		return $result;
	}

	protected function sync_cancellation( &$invoice ) {
		return $this->sync_invoice( $invoice );
	}

	/**
	 * @param Simple|Cancellation $invoice
	 *
	 * @return bool|\WP_Error
	 */
	protected function sync_invoice( $invoice ) {
		$customer_id   = false;
		$address_data  = $invoice->get_address();
		$title         = isset( $address_data['title'] ) ? $address_data['title'] : '';

		$customer_data = array(
            'customer'         => $invoice->get_customer(),
			'first_name'       => $invoice->get_first_name(),
			'last_name'        => $invoice->get_last_name(),
			'email'            => $invoice->get_email(),
			'phone'            => $invoice->get_phone(),
			'company'          => $invoice->get_company(),
			'title'            => $title,
            'academic_title'   => '',
			'vat_id'           => $invoice->get_vat_id(),
			'is_vat_exempt'    => $invoice->is_reverse_charge(),
			'address'          => array(
				'street'       => $invoice->get_address_1(),
				'zip'          => $invoice->get_postcode(),
				'country'      => $invoice->get_country(),
				'city'         => $invoice->get_city(),
			)
		);

		if ( $invoice->has_differing_shipping_address() ) {
		    $address = $invoice->get_shipping_address();

		    $customer_data['shipping_address'] = array(
			    'street'       => isset( $address['address_1'] ) ? $address['address_1'] : null,
			    'zip'          => isset( $address['postcode'] ) ? $address['postcode'] : null,
			    'country'      => $invoice->get_shipping_country(),
			    'city'         => isset( $address['city'] ) ? $address['city'] : null,
            );
        }

		$sync_data          = $invoice->get_external_sync_handler_data( self::get_name() );
		$customer_sync_data = array();

		if ( $sync_data ) {
			$customer_sync_data = (array) $sync_data->get( 'customer' );
		}

		/**
		 * If the customer exists, prefer the synced customer data.
		 */
		if ( ( $customer = $invoice->get_customer() ) && $customer->has_been_externally_synced( self::get_name() ) ) {
			$existing_customer_sync_data = $customer->get_external_sync_handler_data( self::get_name() );
			$customer_sync_data          = array_replace_recursive( $customer_sync_data, $existing_customer_sync_data->get_data() );
		}

		$contact = new Contact( apply_filters( "{$this->get_hook_prefix()}invoice_customer_data", $customer_data, $invoice, $this ), $this->api, $customer_sync_data );
		$result  = $contact->save();

		if ( ! is_wp_error( $result ) ) {
			$customer_id = $contact->get_id();

			$invoice->update_external_sync_handler( self::get_name(), array( 'customer' => $contact->get_sync_data() ) );

			/**
			 * Update the customer in case exists.
			 */
			if ( $customer = $invoice->get_customer() ) {
				$customer->update_external_sync_handler( self::get_name(), $contact->get_sync_data() );
			}
		}

		$filename = false;

		if ( $invoice->has_file() ) {
			$file_result = $this->get_api()->upload_voucher_file( $invoice->get_path() );

			if ( ! is_wp_error( $file_result ) ) {
				$filename = $file_result;
			}
		}

		$items         = array();
		$sync_data     = false;
		$exists        = false;
		$remote_status = false;
		$remote_data   = false;

		if ( $this->has_synced( $invoice ) ) {
			$sync_data   = $invoice->get_external_sync_handler_data( static::get_name() );
			$remote_data = $this->get_api()->get_voucher( $sync_data->get_id() );

			/**
			 * In case API does not return a 404 assume an error occurred
             * which should not trigger a re-submit of the voucher.
			 */
			if ( ! $this->get_api()->is_404( $remote_data ) ) {
			    $exists = true;

			    if ( ! $this->get_api()->has_failed( $remote_data ) ) {
				    $remote_status = $remote_data['objects'][0]['status'];
                }
            }
		}

		/**
		 * Build up items array. Items are being merged by category ID and tax rate.
		 */
		foreach( $invoice->get_items( $invoice->get_item_types_for_totals() ) as $item ) {
			$category_id = $this->get_category_id( $item, $invoice );
			$item_total  = $item->get_total();

			$item_data = array(
				'accountingType' => array(
					'id'         => $category_id,
					'objectName' => 'AccountingType'
				),
				'net'            => $invoice->prices_include_tax() ? 'false' : 'true',
				/* translators: 1: quantity 2: item name 3: item type */
				'comment'        => sprintf( _x( '%1$sx %2$s (%3$s)', 'sevdesk voucherPos comment', 'woocommerce-germanized-pro' ), $item->get_quantity(), $item->get_name(), $item->get_type() ),
				'objectName'     => 'VoucherPos',
				'mapAll'         => 'true',
			);

			if ( $exists && $sync_data->get_id() ) {
				$item_data['voucher'] = array(
					'id'         => $sync_data->get_id(),
					'objectName' => 'Voucher'
				);
			}

			if ( $item->get_total_tax() > 0 ) {
				$taxes = $item->get_taxes();

				foreach( $taxes as $tax ) {
					$percentage  = $tax->get_tax_rate()->get_percent();
					$total_gross = $tax->get_total_net() + $tax->get_total_tax();
					$total       = $tax->get_total_net();

					// Vouchers
					if ( $total_gross > $item_total ) {
					    $total_gross = $item_total;
                    }

					$item_tax_data = array_merge( $item_data, array(
						'sum'       => $total,
						'sumNet'    => $tax->get_total_net(),
						'sumTax'    => $tax->get_total_tax(),
						'sumGross'  => $total_gross,
						'taxRate'   => $percentage,
					) );

					if ( sizeof( $taxes ) > 1 ) {
						$item_tax_data['comment'] = $item_tax_data['comment'] . ' | ' . $tax->get_tax_rate()->get_formatted_percentage();
					}

					$items[] = apply_filters( "{$this->get_hook_prefix()}voucher_taxable_item", $item_tax_data, $item, $invoice, $tax );
				}
			} else {
			    $item_data = array_merge( $item_data, array(
				    'sum'     => $item->get_total(),
				    'taxRate' => 0,
			    ) );

				$items[] = apply_filters( "{$this->get_hook_prefix()}voucher_non_taxable_item", $item_data, $item, $invoice );
			}
		}

		$status   = 100;
		$tax_type = 'default';

		if ( ! Countries::is_eu_country( $invoice->get_country() ) ) {
			$tax_type = 'noteu';
		} elseif( $invoice->is_reverse_charge() ) {
			$tax_type = 'eu';
		}

		/**
		 * In case the customer id is not available, voucher status needs to be reset.
		 */
		if ( ! $customer_id ) {
			$status = 100;
		}

		$voucher = array(
			'voucher' => array(
				'objectName'	  => 'Voucher',
				'mapAll'		  => 'true',
				'voucherDate'	  => $invoice->get_date_created()->date_i18n( 'Y-m-d' ),
				'description'     => $invoice->get_formatted_number(),
				'comment'	      => $this->get_invoice_remark( $invoice ),
				'status'		  => $status,
				'total'			  => $invoice->get_total(),
				'payDate'		  => $invoice->get_date_paid() ? $invoice->get_date_paid()->date_i18n( 'Y-m-d' ) : null,
				'deliveryDate'    => $invoice->get_date_of_service() ? $invoice->get_date_of_service()->date_i18n( 'Y-m-d' ) : $invoice->get_date_created()->date_i18n( 'Y-m-d' ),
				'paymentDeadline' => $invoice->get_date_due() ? $invoice->get_date_due()->date_i18n( 'Y-m-d' ) : null,
				'taxType'		  => apply_filters( $this->get_hook_prefix() . 'tax_type', $tax_type, $invoice ),
				'creditDebit'	  => 'cancellation' === $invoice->get_invoice_type() ? 'C' : 'D',
				'voucherType'	  => 'VOU',
				'vatNumber'       => $invoice->get_vat_id(),
				'supplier'        => $customer_id ? array( 'id' => $customer_id, 'objectName' => 'Contact' ) : null,
			),
			'voucherPosSave'   => $items,
			'filename'         => $filename ? $filename : null,
			'voucherPosDelete' => null
		);

		$cost_centre = apply_filters( $this->get_hook_prefix() . 'invoice_cost_centre_id', '', $invoice );

		if ( ! empty( $cost_centre ) ) {
		    $voucher['voucher']['costCentre'] = array(
                'id'         => $cost_centre,
                'objectName' => 'CostCentre'
            );
        }

		if ( $exists ) {
			/**
			 * In case the remote status is draft - allow updating the voucher.
			 */
			if ( 50 == $remote_status ) {
				$voucher['voucher']['id'] = $sync_data->get_id();

				/**
				 * Check for existing items
				 */
				$voucher_items = $this->get_api()->get_voucher_items( $sync_data->get_id() );

				if ( ! $this->get_api()->has_failed( $voucher_items ) ) {
					$voucher['voucherPosDelete'] = array();

					foreach( $voucher_items['objects'] as $item ) {
						$voucher['voucherPosDelete'][] = array(
							'objectName' => 'VoucherPos',
							'id'         => $item['id']
						);
					}
                }

				$result = $this->get_api()->update_voucher( $sync_data->get_id(), $voucher );
			} else {
				if ( ! $invoice->is_paid() ) {
					$this->get_api()->mark_voucher_as_open( $sync_data->get_id() );
				}
			}
		} else {
			$result = $this->get_api()->create_voucher( $voucher );

			if ( ! is_wp_error( $result ) ) {
				$objects    = $result->get( 'objects' );
				$voucher_id = $objects['voucher']['id'];

				$invoice->update_external_sync_handler( self::get_name(), array(
					'id' =>$voucher_id
				) );
			}
		}

		if ( ! is_wp_error( $result ) ) {
			if ( $invoice->is_paid() ) {
				$book_result = $this->book_invoice( $invoice );

				if ( is_wp_error( $book_result ) ) {
                    return $book_result;
				}
			}
        }

		if ( ! is_wp_error( $result ) ) {
			return true;
		}

		return $result;
 	}

	/**
	 * @see
	 *
	 * @param Item $item
	 * @param Invoice $invoice
	 */
	protected function get_category_id( $item, $invoice ) {
		/**
		 * Einnahmen / Erlöse
		 */
		$category_id = 26;

		if ( 'cancellation' === $invoice->get_invoice_type()  ) {
			/**
			 * Erlösminderung
			 */
			$category_id = 27;
		}

		if ( 'product' === $item->get_item_type() ) {
            $product_type     = $this->get_product_type( $item );
            $option_name      = "invoice_" . ( 'simple' !== $invoice->get_invoice_type() ? $invoice->get_invoice_type() . '_' : '' ) . "product_type_{$product_type}_cat_id";
            $type_category_id = $this->get_setting( $option_name );

            if ( ! empty( $type_category_id ) ) {
                $category_id = $type_category_id;
            }
        }

		return apply_filters( $this->get_hook_prefix() . 'item_category_id', $category_id, $item, $invoice );
	}

	/**
	 * @param ProductItem $item
	 */
	protected function get_product_type( $item ) {
	    $type = 'default';

	    if ( $item->is_service() ) {
	        $type = 'service';
        } elseif( $item->is_virtual() ) {
	        $type = 'virtual';
        }

	    return $type;
     }

	/**
	 * @param Invoice $invoice
	 */
 	public function is_booked( $invoice ) {
		$is_booked = false;

	    if ( $invoice->has_been_externally_synced( self::get_name() ) ) {
		    $sync_data   = $invoice->get_external_sync_handler_data( self::get_name() );
		    $remote_data = $this->get_api()->get_voucher( $sync_data->get_id() );
		    $status      = 1000;

		    if ( ! $this->get_api()->has_failed( $remote_data ) ) {
		    	$status = $remote_data['objects'][0]['status'];
		    }

		    /**
		     * If the voucher has been booked - status is set to 1000 (paid).
		     */
		    if ( true === wc_string_to_bool( $sync_data->get( 'is_booked' ) ) && 1000 == $status ) {
		    	$is_booked = true;
		    }
	    }

	    return $is_booked;
    }

	/**
	 * @param Invoice $invoice
	 */
 	public function book_invoice( $invoice ) {
	    /**
		 * Do only book invoice if option is turned on, the invoice was synced before and has not yet been booked.
		 */
		if ( $this->auto_book_vouchers() && $invoice->has_been_externally_synced( self::get_name() ) && $invoice->is_paid() && ! $this->is_booked( $invoice ) ) {

			$sync_data          = $invoice->get_external_sync_handler_data( self::get_name() );
			$amount             = $invoice->get_total();
			$account_id         = $this->get_account_id( $invoice->get_payment_method_name() );
			$purpose            = $invoice->get_order_number();
			$invoice_trans_id   = $invoice->get_payment_transaction_id();
			$transaction_id     = '';
			$is_manual          = $this->is_manual_account( $account_id );
			$accounts           = $this->get_accounts();
			$default_account_id = $this->get_default_account_id();

			/**
			 * No account id was found.
             * Use case: Booking should only happen for certain gateway(s) and no default account has been chosen.
			 */
			if ( empty( $account_id ) ) {
			    return false;
            }

			$start_date_obj = clone $invoice->get_date_created();
			$end_date_obj   = clone ( $invoice->get_date_paid() ? $invoice->get_date_paid() : $invoice->get_date_created() );

			// By default start searching 1 week in the past
			$start_date_obj->modify( '-1 week' );

			$start_date = $start_date_obj->getOffsetTimestamp();
			$end_date   = $end_date_obj->getOffsetTimestamp();

            /**
             * Search the transaction based on amount and date.
             */
            $transaction_result = $this->get_api()->search_transactions( array(
                'amount_from' => $invoice->get_total(),
                'account'     => apply_filters( "{$this->get_hook_prefix()}search_transaction_account_id", $account_id, $invoice, $this ),
                'start_date'  => apply_filters( "{$this->get_hook_prefix()}search_transaction_start_date", $start_date, $invoice, $this ),
                'end_date'    => apply_filters( "{$this->get_hook_prefix()}search_transaction_end_date", $end_date, $invoice, $this ),
            ) );

            if ( ! $this->get_api()->has_failed( $transaction_result ) && ! empty( $transaction_result['objects'] ) ) {
                $is_match = false;

                foreach( $transaction_result['objects'] as $transaction ) {

                    if ( 'Bankeinzug' === $transaction['payeePayerName'] ) {
                        continue;
                    }

	                $remote_purposes       = explode( '/', $transaction['paymtPurpose'] );
	                $remote_main_purpose   = ! empty( $remote_purposes[0] ) ? trim( $remote_purposes[0] ) : '';
	                $remote_transaction_id = '';

	                // Parse remote transaction id
	                foreach( $remote_purposes as $remote_purpose_piece ) {
	                    $remote_purpose_piece = trim( strtolower( $remote_purpose_piece ) );

	                    if ( strstr( $remote_purpose_piece, 'txid' ) ) {
	                        $remote_transaction_id = trim( str_replace( 'txid', '', $remote_purpose_piece ) );
                        }
                    }

	                // Check if transaction id matches
	                if ( ! empty( $invoice_trans_id ) && ! empty( $remote_transaction_id ) && $remote_transaction_id == $invoice_trans_id ) {
		                $is_match = true;
	                } else {
		                if ( ! empty( $remote_main_purpose ) ) {
			                $purpose_numbers = preg_replace( '/[^0-9]/', '', $purpose );

			                /**
			                 * In case the purpose contains numbers (e.g. order id) make sure to check whether numbers from the original purpose
			                 * match remote purpose numbers to prevent that searching for 83718 matches 3718.
			                 */
			                if ( ! empty( $purpose_numbers ) ) {
				                $remote_purpose_numbers = preg_replace( '/[^0-9]/', '', $remote_main_purpose );

				                // Check if numeric representation (e.g. order number) matches
				                if ( $remote_purpose_numbers == $purpose_numbers ) {
					                $is_match = true;
				                }
			                } else {
			                    // Fallback string existence search
				                $is_match = strstr( $remote_main_purpose, $purpose );
			                }
		                }
	                }

	                // Stop if we've found the first match
	                if ( $is_match ) {
		                $transaction_id = $transaction['id'];
		                $account_id     = $transaction['checkAccount']['id'];

		                break;
	                }
                }
            }

			/**
			 * Online accounts to not allow booking vouchers without a matching transaction.
             * Use default account as fallback.
			 */
			if ( ( ! $is_manual && empty( $transaction_id ) ) || ! array_key_exists( $account_id, $accounts ) ) {
				$account_id = apply_filters( "{$this->get_hook_prefix()}book_voucher_failed_default_account_id", $default_account_id, $invoice, $this );
 			}

			if ( ! empty( $account_id ) ) {
				$result = $this->get_api()->book_voucher( $sync_data->get_id(), $amount, array(
					'date'        => $invoice->get_date_paid()->date_i18n( 'Y-m-d' ),
					'transaction' => apply_filters( "{$this->get_hook_prefix()}book_voucher_transaction_id", $transaction_id, $invoice, $this ),
					'account'     => apply_filters( "{$this->get_hook_prefix()}book_voucher_account_id", $account_id, $invoice, $this ),
				) );

				if ( ! is_wp_error( $result ) ) {
					$invoice->update_external_sync_handler( self::get_name(), array( 'is_booked' => 'yes' ) );
					return true;
				} else {
				    return $result;
                }
            }
		}

		return false;
    }

    protected function get_default_account_id() {
	    return $this->get_setting( 'vouchers_book_default_account' );
    }

    protected function is_manual_account( $account_id ) {
 	    $accounts = $this->get_accounts( 'manual' );

 	    return array_key_exists( $account_id, $accounts ) ? true : false;
    }

    protected function get_account_id( $payment_method ) {
 	    $default_account = $this->get_default_account_id();
 	    $account_id      = $default_account;

        if ( 'yes' === $this->get_setting( 'vouchers_book_gateway_specific' ) ) {
	        $gateway_accounts = (array) $this->get_setting( 'vouchers_book_gateway_accounts' );

	        if ( array_key_exists( $payment_method, $gateway_accounts ) && ! empty( $gateway_accounts[ $payment_method ] ) ) {
		        $account_id = $gateway_accounts[ $payment_method ];
	        }
        }

        return $account_id;
    }

	/**
	 * @param ExternalSyncable $object
	 */
	public function has_synced( $object ) {
		return $object->has_been_externally_synced( static::get_name() );
	}

	/**
	 * @param ExternalSyncable $object
	 *
	 * @return \WC_DateTime|null
	 */
	public function get_date_last_synced( $object ) {
		if ( $object->has_been_externally_synced( static::get_name() ) ) {
			if ( $data = $object->get_external_sync_handler_data( static::get_name() ) ) {
				return $data->get_last_updated();
			}
		}

		return null;
	}

	public function get_auth_api() {
		return $this->auth_api;
	}

	/**
	 * @return Models
	 */
	public function get_api() {
		return $this->api;
	}

	public function enable_auto_sync( $object_type ) {
		return 'yes' === $this->get_setting( "{$object_type}_enable_automation" );
	}

	public function auto_book_vouchers() {
	    return 'yes' === $this->get_setting( 'vouchers_book' );
    }

	/**
	 * @param \WP_Error|RESTResponse $response
	 *
	 * @return \WP_Error|RESTResponse
	 */
	public function parse_response( $response ) {
		$error = new \WP_Error();

		if ( ! is_wp_error( $response ) ) {
			if ( $response->is_error() ) {
			    $code = $response->get_code();
			    $body = $response->get_body();

				\Vendidero\StoreaBill\Package::extended_log( sprintf( 'Error (%s) while performing sevDesk request: ' . wc_print_r( $body, true ), $code ) );

				if ( 400 === $code ) {
			        $code = 404;
                }

				if ( $r_error = $response->get( 'error' ) ) {
					if ( isset( $r_error['message'] ) ) {
						$error->add( $code, sprintf( _x( 'The following error occurred during a sevDesk API call: %s', 'sevdesk', 'woocommerce-germanized-pro' ), esc_html( $r_error['message'] ) ) );
					}
				}

				if ( ! sab_wp_error_has_errors( $error ) ) {
					if ( $code >= 500 ) {
						$error->add( $code, _x( 'There seems to be an issue while contacting sevDesk. Seems like the API is currently not available.', 'sevdesk', 'woocommerce-germanized-pro' ) );
					} elseif ( 404 === $code ) {
						$error->add( 404, _x( 'The requested resource could not be found.', 'sevdesk', 'woocommerce-germanized-pro' ) );
					} elseif ( isset( $body['message'] ) ) {
						$error->add( $code, sprintf( _x( 'The following error occurred during a sevDesk API call: %s', 'sevdesk', 'woocommerce-germanized-pro' ), esc_html( $body['message'] ) ) );
					} else {
						$error->add( $code, _x( 'Error while connecting to sevDesk. Please make sure that your host allows outgoing connections to sevDesk.', 'sevdesk', 'woocommerce-germanized-pro' ) );
					}
				}
			}
		}

		return sab_wp_error_has_errors( $error ) ? $error : $response;
	}

	protected function get_default_sevdesk_account() {
		if ( false === $this->default_account ) {
			$this->get_accounts();
		}

		return $this->default_account;
	}

	protected function get_accounts( $type = '' ) {
		if ( is_null( $this->accounts ) ) {
			$this->accounts = array(
                'manual' => array(),
                'auto'   => array()
            );

			foreach( $this->api->get_accounts() as $account ) {
				/* translators: 1: account name  2: account id */
				$title = sprintf( _x( '%1$s (%2$s)', 'sevdesk', 'woocommerce-germanized-pro' ), esc_html( $account['name'] ), esc_html( $account['id'] ) );

				/**
				 * Set default account
				 */
				if ( isset( $account['defaultAccount'] ) && '1' === $account['defaultAccount'] ) {
			        $this->default_account = $account['id'];
                }

                if ( in_array( $account['type'], array( 'offline', 'register' ) ) ) {
                    $this->accounts['manual'][ $account['id'] ] = $title;
                } else {
	                $this->accounts['auto'][ $account['id'] ] = $title;
                }
			}
		}

		$accounts = empty( $type ) ? ( $this->accounts['manual'] + $this->accounts['auto'] ) : $this->accounts[ $type ];

		return $accounts;
	}

	protected function get_categories( $type = '' ) {
		if ( is_null( $this->categories ) ) {
			$this->categories = array();

			foreach( $this->api->get_categories() as $category ) {
			    if ( ! empty( $type ) && $category['type'] !== $type ) {
			        continue;
                }

				/* translators: 1: category name  2: category accounting system number or id */
				$this->categories[ $category['id'] ] = sprintf( _x( '%1$s (%2$s)', 'sevdesk', 'woocommerce-germanized-pro' ), esc_html( $category['name'] ), esc_html( isset( $category['accountingSystemNumber']['number'] ) ? $category['accountingSystemNumber']['number'] : $category['id'] ) );
			}
		}

		return $this->categories;
	}

	protected function get_account_options( $type = '' ) {
	    $accounts = $this->get_accounts( $type );
	    $options  = array( '' => _x( 'None', 'sevdesk accounts', 'woocommerce-germanized-pro' ) ) + $accounts;

	    return $options;
	}

	protected function get_category_options() {
		$categories = $this->get_categories();
		$options    = array( '' => _x( 'None', 'sevdesk categories', 'woocommerce-germanized-pro' ) ) + $categories;

		return $options;
	}

	public function generate_sab_sevdesk_accounts_html( $key, $data ) {
		ob_start();
		$current_settings = (array) $this->get_setting( 'vouchers_book_gateway_accounts' );
		?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <span class="sab-label-wrap"><?php echo esc_html( $data['title'] ); ?></span>
            </th>
            <td class="forminp" id="sab-order-status-payment-method">
                <table class="widefat sab-order-status-payment-method-table sab-settings-table fixed striped page" cellspacing="0">
                    <input type="text" name="sab_settings_hider" style="display: none" data-show_if_sync_handler_sevdesk_vouchers_book_gateway_specific="" />
                    <thead>
                    <tr>
                        <th><?php echo esc_html_x(  'Payment method', 'sevdesk', 'woocommerce-germanized-pro' ); ?></th>
                        <th><?php echo esc_html_x(  'Account', 'sevdesk', 'woocommerce-germanized-pro' ); ?> <?php echo sab_help_tip( _x( 'Choose one or more order statuses. Leave empty to disable automation for the method.', 'sevdesk', 'woocommerce-germanized-pro' ) ); ?></th>
                    </tr>
                    </thead>
                    <tbody class="sab-order-status-payment-methods">
					<?php foreach ( Helper::get_available_payment_methods() as $method_id => $gateway ) : ?>
                        <tr>
                            <td><?php echo $gateway->get_title(); ?></td>
                            <td>
                                <select class="sab-enhanced-select" name="<?php echo $this->get_setting_field_key( 'vouchers_book_gateway_accounts' ); ?>[<?php echo esc_attr( $method_id ); ?>]" data-allow-clear="true" data-placeholder="<?php echo esc_html_x(  'Use default account', 'sevdesk', 'woocommerce-germanized-pro' ); ?>">
                                    <?php foreach( $this->get_account_options() as $id => $title ) : ?>
                                        <option value="<?php echo esc_attr( $id ); ?>" <?php selected( $id, array_key_exists( $method_id, $current_settings ) ? $current_settings[ $method_id ] : false ); ?>><?php echo esc_attr( $title ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
					<?php endforeach; ?>
                    </tbody>
                </table>
            </td>
        </tr>
		<?php
		return ob_get_clean();
	}

	public function sanitize_gateway_accounts( $value ) {
		$value = is_null( $value ) ? array() : (array) $value;

		return wc_clean( array_map( 'stripslashes', $value ) );
	}

	public function get_settings( $context = 'view' ) {
		$settings               = parent::get_settings( $context );
		$account_options        = array();
		$manual_account_options = array();
		$category_options       = array();

		/**
		 * Prevent loops
		 */
		if ( 'edit' === $context && ! empty( $this->settings ) && $this->is_enabled() ) {
		    $account_options         = $this->get_account_options();
			$manual_account_options  = $this->get_account_options( 'manual' );
		    $category_options        = $this->get_category_options();
		}

		$settings['voucher_section_start'] = array(
			'type'  => 'title',
			'title' => _x( 'Vouchers', 'sevdesk', 'woocommerce-germanized-pro' ),
			'id'    => 'invoice_options',
		);

		$settings['invoice_enable_automation'] = array(
			'title'       => _x( 'Invoices', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Automatically transfer invoices to sevDesk.', 'sevdesk', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, invoices are transferred to sevDesk automatically as soon as the invoice is finalized.', 'sevdesk', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'yes',
		);

		foreach( Product::get_product_types() as $product_type => $title ) {
			$settings["invoice_product_type_{$product_type}_cat_id"] = array(
				'title'       => sprintf( _x( '%s Product', 'sevdesk', 'woocommerce-germanized-pro' ), $title ),
				'type'        => 'select',
				'class'       => 'sab-enhanced-select',
				'desc_tip'    => sprintf( _x( 'Choose an accounting type for products of type %s', 'sevdesk', 'woocommerce-germanized-pro' ), $title ),
				'default'     => '',
				'options'     => $category_options,
				'custom_attributes'  => array(
					'data-allow-clear' => true,
					'data-placeholder' => _x( 'Einnahmen / Erlöse (8200)', 'sevdesk accounts', 'woocommerce-germanized-pro' ),
				),
			);
		}

		$settings['invoice_cancellation_enable_automation'] = array(
			'title'       => _x( 'Cancellations', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Automatically transfer cancellations to sevDesk.', 'sevdesk', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, cancellations are transferred to sevDesk automatically as soon as the cancellation is finalized.', 'sevdesk', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'yes',
		);

		foreach( Product::get_product_types() as $product_type => $title ) {
			$settings["invoice_cancellation_product_type_{$product_type}_cat_id"] = array(
				'title'       => sprintf( _x( '%s Products', 'sevdesk', 'woocommerce-germanized-pro' ), $title ),
				'type'        => 'select',
				'class'       => 'sab-enhanced-select',
				'desc_tip'    => sprintf( _x( 'Choose an accounting type for products of type %s', 'sevdesk', 'woocommerce-germanized-pro' ), $title ),
				'default'     => '',
				'options'     => $category_options,
				'custom_attributes'  => array(
					'data-allow-clear' => true,
					'data-placeholder' => _x( 'Erlösminderung (8700)', 'sevdesk accounts', 'woocommerce-germanized-pro' ),
				),
			);
		}

		$settings['vouchers_book'] = array(
			'title'       => _x( 'Book', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Automatically book vouchers.', 'sevdesk', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, paid invoices will be booked to a specific sevDesk account. In case you are choosing an online account (such as PayPal) a specific transaction must be available to book the voucher. As a fallback the voucher will be booked to the default account instead.', 'sevdesk', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'no',
		);

		$settings['vouchers_book_default_account'] = array(
			'title'       => _x( 'Default account', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'select',
			'class'       => 'sab-enhanced-select',
			'desc_tip'    => _x( 'Link voucher to a specific transaction if possible.', 'sevdesk', 'woocommerce-germanized-pro' ),
			'default'     => '',
			'options'     => $manual_account_options,
			'custom_attributes'  => array(
				'data-allow-clear' => true,
                'data-placeholder' => _x( 'None', 'sevdesk accounts', 'woocommerce-germanized-pro' ),
				'data-show_if_sync_handler_sevdesk_vouchers_book' => '',
			),
		);

		$settings['vouchers_book_gateway_specific'] = array(
			'title'       => _x( 'Gateways', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Choose specific booking account per gateway.', 'sevdesk', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, you may choose an account per gateway to make sure vouchers paid via a specific gateway are booked to a specific account.', 'sevdesk', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'no',
			'custom_attributes'  => array(
				'data-show_if_sync_handler_sevdesk_vouchers_book' => '',
			),
		);

		$settings['vouchers_book_gateway_accounts'] = array(
			'title'       => _x( 'Accounts', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_sevdesk_accounts',
			'sanitize_callback' => array( $this, 'sanitize_gateway_accounts' )
		);

		$settings['customer_section_start'] = array(
			'type'  => 'title',
			'title' => _x( 'Customers', 'sevdesk', 'woocommerce-germanized-pro' ),
			'id'    => 'customer_options',
		);

		$settings['customer_enable_automation'] = array(
			'title'       => _x( 'Automation', 'sevdesk', 'woocommerce-germanized-pro' ),
			'type'        => 'sab_toggle',
			'description' => _x( 'Create and/or update contacts as soon as customer data changes.', 'sevdesk', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . _x( 'By enabling this option, customers are transferred to sevDesk automatically as soon as a customer is created or updated.', 'sevdesk', 'woocommerce-germanized-pro' ) . '</div>',
			'default'     => 'yes',
		);

		return $settings;
	}
}