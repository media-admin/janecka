<?php

namespace Vendidero\StoreaBill\Admin;

use Vendidero\StoreaBill\Countries;
use Vendidero\StoreaBill\ExternalSync\Helper;
use Vendidero\StoreaBill\Package;

defined( 'ABSPATH' ) || exit;

class Settings {

	public static function get_sections() {
		$sections = array(
			''                      => _x( 'Invoices', 'storeabill-core', 'woocommerce-germanized-pro' ),
			'invoice_cancellations' => _x( 'Cancellations', 'storeabill-core', 'woocommerce-germanized-pro' )
		);

		if ( self::has_sync_handlers() ) {
			$sections['sync_handlers'] = _x( 'External Services', 'storeabill-core', 'woocommerce-germanized-pro' );
 		}

		return apply_filters( 'storeabill_admin_settings_sections', $sections );
	}

	protected static function has_sync_handlers() {
		$sync_handlers = Helper::get_sync_handlers();

		return sizeof( $sync_handlers ) > 0 ? true : false;
	}

	public static function get_settings( $section = '' ) {
		$settings = array();
		$section  = self::get_current_section( $section );

		if ( 'invoices' === $section ) {
			$settings = self::get_invoice_settings();
		} elseif( 'invoice_cancellations' === $section ) {
			$settings = self::get_cancellation_settings();
		}

		return apply_filters( 'storeabill_admin_settings', $settings, $section );
	}

	public static function get_help_link() {
		global $current_section;

		return apply_filters( 'storeabill_accounting_help_link', '', $current_section );
	}

	protected static function get_cancellation_settings() {
		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'cancellation_general_settings' ),
		);

		$settings = array_merge( $settings, \Vendidero\StoreaBill\WooCommerce\Admin\Settings::get_cancellation_settings() );

		$settings = array_merge( $settings, array(
			array(
				'title' 	     => _x( 'Email', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => _x( 'Automatically send cancellations to customers by email.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_invoice_cancellation_send_to_customer',
				'default'	     => 'yes',
				'type' 		     => 'sab_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'cancellation_general_settings' ),

			array( 'title' => _x( 'Layout', 'storeabill-core', 'woocommerce-germanized-pro' ), 'desc' => sprintf( _x( 'Manage your %1$s templates by using the visual editor <a href="%2$s" class="button button-secondary">Learn more</a>', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( 'invoice_cancellation' ), apply_filters( 'storeabill_invoice_cancellation_layout_help_link', '#' ) ), 'type' => 'title', 'id' => 'cancellation_layout_settings' ),

			array(
				'type'          => 'sab_document_templates',
				'document_type' => 'invoice_cancellation',
				'title'         => _x( 'Templates', 'storeabill-core', 'woocommerce-germanized-pro' )
			),

			array( 'type' => 'sectionend', 'id' => 'cancellation_layout_settings' ),
		) );

		$settings = array_merge( $settings, self::get_numbering_options( 'invoice_cancellation' ) );

		return $settings;
	}

	protected static function get_invoice_settings() {
		$settings = array(
			array( 'title' => '', 'type' => 'title', 'id' => 'invoice_general_settings' ),
		);

		$settings = array_merge( $settings, \Vendidero\StoreaBill\WooCommerce\Admin\Settings::get_invoice_settings() );

		$settings = array_merge( $settings, array(
			array(
				'title' 	     => _x( 'Email', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc' 		     => _x( 'Automatically send invoices to customers by email after finalizing.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_invoice_send_to_customer',
				'default'	     => 'yes',
				'type' 		     => 'sab_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'invoice_general_settings' ),

			array( 'title' => _x( 'Layout', 'storeabill-core', 'woocommerce-germanized-pro' ), 'desc' => sprintf( _x( 'Manage your %1$s templates by using the visual editor <a href="%2$s" class="button button-secondary">Learn more</a>', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( 'invoice' ), apply_filters( 'storeabill_invoice_layout_help_link', '#' ) ), 'type' => 'title', 'id' => 'invoice_layout_settings' ),

			array(
				'type'          => 'sab_document_templates',
				'document_type' => 'invoice',
				'title'         => _x( 'Templates', 'storeabill-core', 'woocommerce-germanized-pro' )
			),

			array( 'type' => 'sectionend', 'id' => 'invoice_layout_settings' ),
		) );

		$settings = array_merge( $settings, self::get_numbering_options( 'invoice' ) );

		$settings = array_merge( $settings, array(
			array( 'title' => _x( 'Date due', 'storeabill-core', 'woocommerce-germanized-pro' ), 'type' => 'title', 'id' => 'invoice_due_settings' ),

			array(
				'title' 	     => _x( 'Due until', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc_tip' 		 => _x( 'Choose when invoices are due for payment.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_invoice_due',
				'default'	     => 'immediately',
				'type' 		     => 'select',
				'class'          => 'sab-enhanced-select',
				'options'        => array(
					'immediately' => _x( 'Immediately after finalizing', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'days'        => _x( 'After x days', 'storeabill-core', 'woocommerce-germanized-pro' )
				)
			),

			array(
				'title' 	     => _x( 'Days until due', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc'           => _x( 'days', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc_tip' 		 => _x( 'Choose an amount of days until which the invoice is due for payment.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_invoice_due_days',
				'default'	     => 14,
				'type' 		     => 'number',
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 1,
					'data-show_if_storeabill_invoice_due' => 'days'
				),
			),

			array( 'type' => 'sectionend', 'id' => 'invoice_date_due_settings' ),
		) );

		$settings = array_merge( $settings, array(
			array( 'title' => _x( 'Bank account', 'storeabill-core', 'woocommerce-germanized-pro' ), 'type' => 'title', 'id' => 'invoice_bank_account_settings' ),

			array(
				'title' 	     => _x( 'Holder', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc_tip' 		 => _x( 'Choose an account holder', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_bank_account_holder',
				'default'	     => self::get_default_bank_account_data( 'holder' ),
				'type' 		     => 'text',
			),

			array(
				'title' 	     => _x( 'Bank Name', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc_tip' 		 => _x( 'Choose the name of your bank', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_bank_account_bank_name',
				'default'	     => self::get_default_bank_account_data( 'bank_name' ),
				'type' 		     => 'text',
			),

			array(
				'title' 	     => _x( 'IBAN', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_bank_account_iban',
				'default'	     => self::get_default_bank_account_data( 'iban' ),
				'type' 		     => 'text',
			),

			array(
				'title' 	     => _x( 'BIC', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'id' 		     => 'storeabill_bank_account_bic',
				'default'	     => self::get_default_bank_account_data( 'bic' ),
				'type' 		     => 'text',
			),

			array( 'type' => 'sectionend', 'id' => 'invoice_bank_account_settings' ),
		) );

		return $settings;
	}

	protected static function get_default_bank_account_data( $field = 'iban' ) {
		$default_account = Countries::get_base_bank_account_data();

		if ( array_key_exists( $field, $default_account ) ) {
			return $default_account[ $field ];
		}

		return '';
	}

	public static function get_numbering_options( $document_type ) {
		$settings = array(
			array( 'title' => _x( 'Numbering', 'storeabill-core', 'woocommerce-germanized-pro' ), 'type' => 'title', 'id' => 'numbering_settings' )
		);

		$attributes = array();

		if ( 'invoice_cancellation' === $document_type ) {
			$settings = array_merge( $settings, array(
				array(
					'title' 	     => _x( 'Separate', 'storeabill-core', 'woocommerce-germanized-pro' ),
					'desc' 		     => _x( 'Use separate sequential numbers for cancellations.', 'storeabill-core', 'woocommerce-germanized-pro' ) . '<div class="sab-additional-desc">' . sprintf( _x( 'By default a separate circle of sequential numbers is used for cancellations. By disabling this option the invoice number circle will be used for cancellations too.', 'storeabill-core', 'woocommerce-germanized-pro' ) ) . '</div>',
					'id' 		     => 'storeabill_invoice_cancellation_separate_numbers',
					'default'	     => 'yes',
					'type' 		     => 'sab_toggle',
				)
			) );

			$attributes = array(
				'data-show_if_storeabill_invoice_cancellation_separate_numbers' => 'yes'
			);
		}

		$settings = array_merge( $settings, array(
			array(
				'type'              => 'sab_document_journal_field',
				'field'             => 'last_number',
				'document_type'     => $document_type,
				'title'             => _x( 'Last Number', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'custom_attributes' => $attributes,
			),

			array(
				'type'              => 'sab_document_journal_field',
				'field'             => 'number_format',
				'document_type'     => $document_type,
				'title'             => _x( 'Format', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'custom_attributes' => $attributes,
			),

			array(
				'type'              => 'sab_document_journal_field',
				'field'             => 'number_min_size',
				'document_type'     => $document_type,
				'title'             => _x( 'Minimum Size', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'desc_tip'          => _x( 'Use this option to fill your number with trailing zeros until reaching a minimum size.', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'custom_attributes' => $attributes,
			),

			array(
				'type'              => 'sab_document_journal_field',
				'field'             => 'reset_interval',
				'document_type'     => $document_type,
				'title'             => _x( 'Reset Interval', 'storeabill-core', 'woocommerce-germanized-pro' ),
				'custom_attributes' => $attributes,
			),

			array( 'type' => 'sectionend', 'id' => 'numbering_settings' ),
		) );

		return $settings;
	}

	public static function get_description() {
	    return '';
	}

	public static function get_label() {
		return _x( 'StoreaBill', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public static function get_admin_url() {
		return admin_url( 'admin.php?page=wc-settings&tab=germanized-storeabill' );
	}

	public static function before_save( $settings, $current_section = '' ) {
		$current_section = self::get_current_section( $current_section );

		do_action( 'storeabill_admin_settings_before_save', $current_section, $settings );
	}

	public static function filter_breadcrumb( $breadcrumb ) {
		global $current_section;

		if ( 'sync_handlers' === self::get_current_section( $current_section ) && ( $name = self::get_current_sync_handler_name() ) ) {
			if ( $handler = Helper::get_sync_handler( $name ) ) {
				$breadcrumb[ sizeof( $breadcrumb ) - 1 ]['href'] = remove_query_arg( 'sync_handler_name' );

				$label = $handler::get_title();

				if ( $handler::get_help_link() ) {
					$label = $label . '<a class="page-title-action" href="' . esc_url( $handler::get_help_link() ) . '" target="_blank">' . _x( 'Learn more', 'storeabill-core', 'woocommerce-germanized-pro' ) . '</a>';
				}

				$breadcrumb[] = array(
					'class' => 'section',
					'href'  => '',
					'title' => $label
				);
			}
		}

		return $breadcrumb;
	}

	protected static function get_current_section( $current_section = '' ) {
		if ( empty( $current_section ) || 'invoices' === $current_section ) {
			return 'invoices';
		}

		return $current_section;
	}

	public static function after_save( $settings, $current_section = '' ) {
		$section           = self::get_current_section( $current_section );
		$document_type_str = $section;

		if ( ! empty( $document_type_str ) ) {
			$document_type = sab_clean( str_replace( '-', '_', substr( $document_type_str, 0, -1 ) ) );
			$props         = array();

			if ( sab_get_document_type( $document_type ) ) {

				/**
				 * Save default PDF template
				 */
				if ( isset( $_POST['document_template_default'] ) ) {
					$default_template_id = absint( $_POST['document_template_default'] );

					if ( ! empty( $default_template_id ) && ( $default_template = sab_get_document_template( $default_template_id ) ) ) {
						update_option( 'storeabill_' . $document_type . '_default_template', $default_template_id );
					}
				}

				/**
				 * Save journal data.
				 */
				if ( $journal = sab_get_journal( $document_type ) ) {
					if ( isset( $_POST["journal_{$document_type}_number_format"] ) ) {
						$number_format = sab_clean( $_POST["journal_{$document_type}_number_format"] );

						/**
						 * Force {number} placeholder to be existent
						 */
						if ( strpos( $number_format, '{number}' ) === false ) {
							$number_format .= ' {number}';
						}

						$props['number_format'] = $number_format;
					}

					if ( isset( $_POST["journal_{$document_type}_number_min_size"] ) ) {
						$min_size = absint( sab_clean( $_POST["journal_{$document_type}_number_min_size"] ) );

						$props['number_min_size'] = $min_size;
					}

					if ( isset( $_POST["journal_{$document_type}_reset_interval"] ) ) {
						$interval  = sab_clean( $_POST["journal_{$document_type}_reset_interval"] );
						$intervals = array_keys( sab_get_journal_reset_intervals() );

						if ( in_array( $interval, $intervals ) ) {
							$props['reset_interval'] = $interval;
						} elseif( empty( $interval ) ) {
							$props['reset_interval'] = '';
						}
					}

					if ( ! empty( $props ) ) {
						$journal->set_props( $props );
						$journal->save();
					}

					if ( isset( $_POST["journal_{$document_type}_last_number_unblock"] ) && isset( $_POST["journal_{$document_type}_last_number"] ) && 'yes' === $_POST["journal_{$document_type}_last_number_unblock"] ) {
						$last_number = absint( sab_clean( $_POST["journal_{$document_type}_last_number"] ) );

						$journal->update_last_number( $last_number );
					}
				}
			}
		}

		if ( 'invoices' === $section ) {
			\Vendidero\StoreaBill\WooCommerce\Admin\Settings::after_save_invoices();
		}

		do_action( 'storeabill_admin_settings_after_save', $section, $settings );
	}

	public static function get_current_sync_handler_name() {
		$sync_name = isset( $_GET['sync_handler_name'] ) && ! empty( $_GET['sync_handler_name'] ) ? sab_clean( wp_unslash( $_GET['sync_handler_name'] ) ) : false;

		return $sync_name;
	}

	public static function output_sync_handlers() {
		if ( $sync_handler = self::get_current_sync_handler_name() ) {
			self::sync_handler_edit_screen( $sync_handler );
		} else {
			global $hide_save_button;

			$hide_save_button = true;
			self::sync_handler_screen();
		}
	}

	protected static function sync_handler_edit_screen( $sync_handler ) {
		if ( $handler = Helper::get_sync_handler( $sync_handler ) ) {
			$handler->print_admin_settings();
		}
	}

	protected static function sync_handler_screen() {
		do_action( 'storeabill_admin_before_sync_handlers' );

		include_once Package::get_path() . '/includes/admin/views/html-sync-handlers.php';
	}
}
