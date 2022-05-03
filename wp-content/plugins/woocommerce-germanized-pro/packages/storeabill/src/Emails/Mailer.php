<?php

namespace Vendidero\StoreaBill\Emails;

use Vendidero\StoreaBill\Document\ShortcodeManager;
use Vendidero\StoreaBill\Invoice\Invoice;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

class Mailer {

	protected static $emails = null;

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'woocommerce_email_classes', array( __CLASS__, 'register_emails' ), 10 );
		add_filter( 'woocommerce_email_actions', array( __CLASS__, 'register_actions' ), 10 );

		// Change email template path if is a known email template
		add_filter( 'woocommerce_template_directory', array( __CLASS__, 'set_woocommerce_template_dir' ), 10, 2 );

		add_action( 'init', array( __CLASS__, 'email_hooks' ), 10 );

		/**
		 * This hook will only execute after a successful render which
		 * does only happen after the invoice has been finalized.
		 */
		add_action( "storeabill_invoice_rendered", array( __CLASS__, 'maybe_send_invoice' ), 10 );
		add_action( "storeabill_invoice_cancellation_rendered", array( __CLASS__, 'maybe_send_invoice' ), 10 );
	}

	/**
	 * @param Invoice $invoice
	 *
	 * @return boolean
	 */
	public static function invoice_includes_subtotal_before_discounts( $invoice ) {
		return false === sab_invoice_has_line_total_after_discounts( $invoice );
	}

	public static function invoice_table_show_net_prices( $invoice ) {
		if ( $template = $invoice->get_template() ) {
			if ( $item_table = $template->get_block( 'storeabill/item-table' ) ) {
				$attributes = wp_parse_args( $item_table['attrs'], array(
					'showPricesIncludingTax' => true,
				) );

				return $attributes['showPricesIncludingTax'] ? false : true;
			}
		}

		return false;
	}

	public static function get_invoice_table_total_column_name( $document ) {
		$total_column = self::invoice_includes_subtotal_before_discounts( $document ) ? 'subtotal' : 'total';

		if ( self::invoice_table_show_net_prices( $document ) ) {
			$total_column = $total_column . '_net';
		}

		return $total_column;
	}

	/**
	 * @param Invoice $invoice
	 *
	 * @return array
	 */
	public static function get_invoice_total_rows( $invoice ) {
		$rows = array();

		if ( $template = $invoice->get_template() ) {
			if ( $total_block = $template->get_block( 'storeabill/item-totals' ) ) {
				$old_document = false;

				if ( isset( $GLOBALS['document'] ) ) {
					$old_document = $GLOBALS['document'];
				}

				/**
				 * Setup global
				 */
				$GLOBALS['document'] = $invoice;

				/**
				 * Setup shortcodes
				 */
				ShortcodeManager::instance()->setup( $invoice->get_type() );

				foreach( $total_block['innerBlocks'] as $total_row ) {
					$attributes = wp_parse_args( $total_row['attrs'], array(
						'totalType'   => '',
						'hideIfEmpty' => false,
						'heading'     => '',
						'content'     => '{total}',
					) );

					/**
					 * Skip empty total type rows as this would result in
					 * a call to get_totals without parameters which will lead to all the totals being returned instead.
					 */
					if ( empty( $attributes['totalType'] ) ) {
						continue;
					}

					$attributes['totalType'] = sab_map_invoice_total_type( $attributes['totalType'], $invoice );

					$totals        = $invoice->get_totals( $attributes['totalType'] );
					$total_content = $attributes['content'];

					foreach( $totals as $total ) {
						Package::setup_document_total( $total );

						if ( false !== $attributes['heading'] ) {
							$total->set_label( $attributes['heading'] );
						}

						if ( true === $attributes['hideIfEmpty'] && empty( $total->get_total() ) ) {
							continue;
						}

						$rows[] = array(
							'type'            => $total->get_type(),
							'formatted_label' => sab_do_shortcode( $total->get_formatted_label() ),
							'formatted_total' => sab_do_shortcode( str_replace(  '{total}', $total->get_formatted_total(), $total_content ) ),
						);
					}
				}

				if ( $old_document ) {
					$GLOBALS['document'] = $old_document;
				}
			}
		}

		if ( empty( $rows ) ) {
			foreach( $invoice->get_totals( array( 'total' ) ) as $total ) {
				$rows[] = array(
					'type'            => $total->get_type(),
					'formatted_label' => $total->get_formatted_label(),
					'formatted_total' => str_replace(  '{total}', $total->get_formatted_total(), $total_content ),
				);
			}
		}

		return $rows;
	}

	/**
	 * Send the invoice to the customer right after rendering.
	 *
	 * @param Invoice $invoice
	 */
	public static function maybe_send_invoice( $invoice ) {
		$type              = $invoice->get_type();
		$auto_send_enabled = ( 'yes' === Package::get_setting( "{$type}_send_to_customer" ) && 'automation' === $invoice->get_created_via() );

		if ( $invoice->is_finalized() && apply_filters( "storeabill_send_{$invoice->get_type()}_to_customer", $auto_send_enabled, $invoice ) ) {
			$invoice->send_to_customer();
		}
	}

	public static function email_hooks() {
		add_action( 'storeabill_email_document_details', array( __CLASS__, 'details' ), 10, 4 );
		add_action( 'storeabill_email_document_details', array( __CLASS__, 'summary' ), 20, 4 );
	}

	/**
	 * Show the order details table
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document instance.
	 * @param bool     $sent_to_admin If should sent to admin.
	 * @param bool     $plain_text    If is plain text email.
	 * @param string   $email         Email address.
	 */
	public static function details( $document, $sent_to_admin = false, $plain_text = false, $email = '' ) {
		if ( ! apply_filters( "storeabill_{$document->get_type()}_hide_email_details", false, $document, $email ) ) {
			if ( $plain_text ) {
				sab_get_template(
					self::get_template_path( 'emails/plain/document-details.php', $document->get_type() ), array(
						'document'      => $document,
						'sent_to_admin' => $sent_to_admin,
						'plain_text'    => $plain_text,
						'email'         => $email
					)
				);
			} else {
				sab_get_template(
					self::get_template_path( 'emails/document-details.php', $document->get_type() ), array(
						'document'      => $document,
						'sent_to_admin' => $sent_to_admin,
						'plain_text'    => $plain_text,
						'email'         => $email
					)
				);
			}
		}
	}

	/**
	 * Show the order details table
	 *
	 * @param \Vendidero\StoreaBill\Document\Document $document Document instance.
	 * @param bool     $sent_to_admin If should sent to admin.
	 * @param bool     $plain_text    If is plain text email.
	 * @param string   $email         Email address.
	 */
	public static function summary( $document, $sent_to_admin = false, $plain_text = false, $email = '' ) {
		$fields = array(
			array(
				'label' => _x( 'Number', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'value' => $document->get_formatted_number()
			),
		);

		if ( is_a( $document, '\Vendidero\StoreaBill\Interfaces\Invoice' ) ) {
			$fields[] = array(
				'label' => _x( 'Total', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'value' => $document->get_formatted_price( $document->get_total() )
			);

			if ( 'invoice' === $document->get_type() ) {
				$fields[] = array(
					'label' => _x( 'Payment Status', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'value' => sab_get_invoice_payment_status_name( $document->get_payment_status() )
				);
			}
		}

		$fields = array_filter( apply_filters( "storeabill_{$document->get_type()}_email_summary_fields", $fields, $sent_to_admin, $document ), array( __CLASS__, 'field_is_valid' ) );

		if ( ! empty( $fields ) ) {
			if ( $plain_text ) {
				sab_get_template(
					self::get_template_path( 'emails/plain/document-summary.php', $document->get_type() ), array(
						'document'      => $document,
						'sent_to_admin' => $sent_to_admin,
						'plain_text'    => $plain_text,
						'email'         => $email,
						'fields'        => $fields,
					)
				);
			} else {
				sab_get_template(
					self::get_template_path( 'emails/document-summary.php', $document->get_type() ), array(
						'document'      => $document,
						'sent_to_admin' => $sent_to_admin,
						'plain_text'    => $plain_text,
						'email'         => $email,
						'fields'        => $fields,
					)
				);
			}
		}
	}

	/**
	 * Is field valid?
	 *
	 * @param  array $field Field data to check if is valid.
	 * @return boolean
	 */
	public static function field_is_valid( $field ) {
		return isset( $field['label'] ) && ! empty( $field['value'] );
	}

	/**
	 * This method checks whether a typed template is available or not.
	 * E.g. prefer emails/invoice/document-items.php over emails/document-items.php
	 *
	 * @param $template
	 * @param $document_type
	 *
	 * @return string|string[]
	 */
	protected static function get_template_path( $template, $document_type ) {
		$type_path     = str_replace( "_", "-", sanitize_key( $document_type ) );
		$type_template = str_replace( 'emails/', 'emails/' . $type_path . '/', $template );

		if ( file_exists( Package::get_path() . '/templates/' . $type_template ) ) {
			$template = $type_template;
		}

		return $template;
	}

	/**
	 * @param \Vendidero\StoreaBill\Document\Document $document
	 * @param $args
	 */
	public static function get_items_html( $document, $args ) {
		$defaults = array(
			'plain_text'    => false,
			'sent_to_admin' => false,
			'columns'       => array( 'name', 'quantity' ),
		);

		$args     = wp_parse_args( $args, $defaults );
		$template = $args['plain_text'] ? self::get_template_path( 'emails/plain/document-items.php', $document->get_type() ) : self::get_template_path( 'emails/document-items.php', $document->get_type() );

		$html = sab_get_template_html(
			$template,
			apply_filters(
				'storeabill_email_document_items_args',
				array(
					'document'      => $document,
					'items'         => $document->get_items( $document->get_line_item_types() ),
					'plain_text'    => $args['plain_text'],
					'sent_to_admin' => $args['sent_to_admin'],
					'columns'       => $args['columns']
				),
				$document
			)
		);

		return apply_filters( 'storeabill_email_document_items_table', $html, $document );
	}

	public static function set_woocommerce_template_dir( $dir, $template ) {
		if ( file_exists( Package::get_path() . '/templates/' . $template ) ) {
			return untrailingslashit( apply_filters( 'storeabill_email_template_path', Package::get_template_path() ) );
		}

		return $dir;
	}

	public static function get_actions() {
		$actions = array(
			'storeabill_invoice_payment_status_pending_to_paid',
			'storeabill_invoice_payment_status_pending_to_partial',
			'storeabill_invoice_payment_status_paid_to_pending',
			'storeabill_invoice_payment_status_partial_to_paid',
			'storeabill_invoice_payment_status_partial_to_pending',
			'storeabill_invoice_status_closed_to_cancelled'
		);

		foreach( sab_get_document_types() as $type ) {
			$prefix   = 'storeabill_' . $type . '_status_';
			$statuses = sab_get_document_statuses( $type );

			if ( in_array( 'draft', $statuses ) && in_array( 'closed', $statuses ) ) {
				$actions[] = $prefix . 'draft_to_closed';
			}

			if ( in_array( 'archived', $statuses ) && in_array( 'closed', $statuses ) ) {
				$actions[] = $prefix . 'closed_to_archived';
			}
		}

		return apply_filters( 'storeabill_email_actions', $actions );
	}

	public static function register_actions( $actions ) {
		$actions = array_merge( $actions, self::get_actions() );

		return $actions;
	}

	public static function get_emails() {
		if ( is_null( self::$emails ) ) {

			/**
			 * Force document type registering in case the mailer instance
			 * is being called before init hook has been triggered (e.g. Mailpoet)
			 */
			if ( ! did_action( 'storeabill_registered_core_document_types' ) ) {
				Package::register_document_types();
			}

			self::$emails = array();

			foreach( sab_get_document_types() as $type ) {
				$document_type = sab_get_document_type( $type );

				if ( 'accounting' === $document_type->group && ! Package::enable_accounting() ) {
					continue;
				}

				$email_class_sanitized       = self::sanitize_email_class( $document_type->email_class_name );
				$admin_email_class_sanitized = self::sanitize_email_class( $document_type->admin_email_class_name );

				if ( ! array_key_exists( $email_class_sanitized, self::$emails ) ) {
					self::$emails[ $email_class_sanitized ] = new $document_type->email_class_name();
				}

				if ( ! array_key_exists( $admin_email_class_sanitized, self::$emails ) ) {
					self::$emails[ $admin_email_class_sanitized ] = new $document_type->admin_email_class_name();
				}
			}
		}

		return apply_filters( "storeabill_emails", self::$emails );
	}

	protected static function sanitize_email_class( $class ) {
		return 'storeabill_' . sanitize_key( str_replace( __NAMESPACE__ . '\\', '', $class ) );
	}

	public static function get_email( $class ) {
		$class_sanitized = self::sanitize_email_class( $class );
		$woo_mails       = WC()->mailer()->get_emails();

		if ( array_key_exists( $class_sanitized, $woo_mails ) ) {
			return $woo_mails[ $class_sanitized ];
		}

		return false;
	}

	public static function register_emails( $emails ) {
		$emails = array_merge( $emails, self::get_emails() );

		return $emails;
	}

	/**
	 * Sends the document via mail
	 *
	 * @param \Vendidero\StoreaBill\Document\Document|integer $document
	 * @param bool $to_admin
	 */
	public static function send( $document, $to_admin = false ) {
		$errors = new \WP_Error();

		if ( is_numeric( $document ) ) {
			$document = sab_get_document( $document );
		}

		if ( ! $document || ! is_a( $document, '\Vendidero\StoreaBill\Document\Document' ) ) {
			$errors->add( 'email-error', _x( 'Document could not be instantiated.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			return $errors;
		}

		if ( ! $document_type = sab_get_document_type( $document->get_type() ) ) {
			$errors->add( 'email-error', _x( 'Document type not found.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			return $errors;
		}

		$classname = $document_type->email_class_name;

		if ( ! class_exists( $classname ) ) {
			$classname = '\Vendidero\StoreaBill\Emails\Document';
		}

		if ( $to_admin ) {
			$classname = $document_type->admin_email_class_name;

			if ( ! class_exists( $classname ) ) {
				$classname = '\Vendidero\StoreaBill\Emails\DocumentAdmin';
			}
		}

		if ( ! $mail = self::get_email( $classname ) ) {
			$errors->add( 'email-error', sprintf( _x( 'Email class not found for document type %s.', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( $document_type ) ) );
			return $errors;
		}

		if ( ! is_a( $mail, '\Vendidero\StoreaBill\Emails\Email' ) ) {
			$errors->add( 'email-error', _x( 'Email instance not found.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
			return $errors;
		}

		$result = $mail->trigger( $document->get_id(), $document );

		if ( false === $result ) {
			$errors->add( 'email-error', sprintf( _x( 'Email could not be sent. Has the email %s been disabled?', 'storeabill-core', 'woocommerce-germanized-pro' ), $mail->id ) );
		}

		if ( sab_wp_error_has_errors( $errors ) ) {
			return $errors;
		}

		return true;
	}
}