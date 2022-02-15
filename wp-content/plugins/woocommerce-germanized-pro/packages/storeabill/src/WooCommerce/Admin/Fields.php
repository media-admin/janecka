<?php

namespace Vendidero\StoreaBill\WooCommerce\Admin;

use Vendidero\StoreaBill\WooCommerce\Automation;
use Vendidero\StoreaBill\WooCommerce\Helper;

defined( 'ABSPATH' ) || exit;

class Fields {

	/**
	 * Constructor.
	 */
	public static function init() {
		add_action( 'woocommerce_admin_field_sab_toggle', array( __CLASS__, 'toggle_input_field' ), 10 );
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( __CLASS__, 'maybe_sanitize_toggle_input_field' ), 0, 3 );

		add_action( 'woocommerce_admin_field_sab_oauth_connect', array( __CLASS__, 'oauth_connect_field' ), 10 );
		add_action( 'woocommerce_admin_field_sab_oauth_connected', array( __CLASS__, 'oauth_connected_field' ), 10 );

		add_action( 'woocommerce_admin_field_sab_document_templates', array( __CLASS__, 'document_templates_field' ), 10 );
		add_action( 'woocommerce_admin_field_sab_document_journal_field', array( __CLASS__, 'document_journal_field' ), 10 );
		add_action( 'woocommerce_admin_field_sab_woo_payment_method_statuses', array( __CLASS__, 'payment_method_statuses_field' ), 10 );
	}

	public static function payment_method_statuses_field( $value ) {
		// Description handling.
		$field_description = \WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];
		$count             = 0;
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<span class="sab-label-wrap"><?php echo esc_html( $value['title'] ); ?><?php echo $tooltip_html; // WPCS: XSS ok. ?></span>
			</th>
			<td class="forminp" id="sab-order-status-payment-method">
				<table class="widefat sab-order-status-payment-method-table sab-settings-table fixed striped page" cellspacing="0">
					<input type="text" name="sab_settings_hider" style="display: none" data-show_if_storeabill_invoice_woo_order_auto_create="yes" data-show_if_storeabill_invoice_woo_order_auto_create_timing="status_payment_method" />
					<thead>
					<tr>
						<th><?php echo esc_html_x(  'Payment method', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></th>
						<th><?php echo esc_html_x(  'Order status(es)', 'storeabill-core', 'woocommerce-germanized-pro' ); ?> <?php echo sab_help_tip( _x( 'Choose one or more order statuses. Leave empty to disable automation for the method.', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?></th>
					</tr>
					</thead>
					<tbody class="sab-order-status-payment-methods">
					<?php foreach ( Helper::get_available_payment_methods() as $method_id => $gateway ) : ?>
						<tr>
							<td><?php echo $gateway->get_title(); ?></td>
							<td>
								<select class="sab-enhanced-select" multiple name="auto_order_status[<?php echo esc_attr( $method_id ); ?>][]" data-placeholder="<?php echo esc_attr_x( 'After checkout', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>">
									<?php foreach( Helper::get_order_statuses() as $status => $title ) : ?>
										<option value="<?php echo esc_attr( $status ); ?>" <?php selected( true, in_array( $status, Automation::get_invoice_payment_method_statuses( $method_id ) ) ); ?>><?php echo $title; ?></option>
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
	}

	public static function maybe_sanitize_toggle_input_field( $value, $option, $raw_value ) {
		if ( 'sab_toggle' === $option['type'] ) {
			return \Vendidero\StoreaBill\Admin\Fields::sanitize_toggle_input_field( $raw_value );
		}

		return $value;
	}

	public static function oauth_connect_field( $args ) {
		$field_description = \WC_Admin_Settings::get_field_description( $args );
		$tooltip_html      = $field_description['tooltip_html'];
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<span class="sab-label-wrap"><?php echo esc_html( $args['title'] ); ?><?php echo $tooltip_html; // WPCS: XSS ok. ?></span>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $args['type'] ) ); ?>">
				<?php \Vendidero\StoreaBill\Admin\Fields::render_oauth_connect_field( $args ); ?>
			</td>
		</tr>
		<?php
	}

	public static function document_templates_field( $args ) {
		$field_description = \WC_Admin_Settings::get_field_description( $args );
		$tooltip_html      = $field_description['tooltip_html'];
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<span class="sab-label-wrap"><?php echo esc_html( $args['title'] ); ?><?php echo $tooltip_html; // WPCS: XSS ok. ?></span>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $args['type'] ) ); ?>">
				<?php \Vendidero\StoreaBill\Admin\Fields::render_document_templates_field( $args ); ?>
			</td>
		</tr>
		<?php
	}

	public static function document_journal_field( $args ) {
		$field_description = \WC_Admin_Settings::get_field_description( $args );
		$tooltip_html      = $field_description['tooltip_html'];
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<span class="sab-label-wrap"><?php echo esc_html( $args['title'] ); ?><?php echo $tooltip_html; // WPCS: XSS ok. ?></span>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $args['type'] ) ); ?>">
				<?php \Vendidero\StoreaBill\Admin\Fields::render_document_journal_field( $args ); ?>
			</td>
		</tr>
		<?php
	}

	public static function oauth_connected_field( $args ) {
		$field_description = \WC_Admin_Settings::get_field_description( $args );
		$tooltip_html      = $field_description['tooltip_html'];
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<span class="sab-label-wrap"><?php echo esc_html( $args['title'] ); ?><?php echo $tooltip_html; // WPCS: XSS ok. ?></span>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $args['type'] ) ); ?>">
				<?php \Vendidero\StoreaBill\Admin\Fields::render_oauth_connected_field( $args ); ?>
			</td>
		</tr>
		<?php
	}

	public static function toggle_input_field( $value ) {
		// Description handling.
		$field_description = \WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$tooltip_html      = $field_description['tooltip_html'];

		if ( ! isset( $value['value'] ) ) {
			$value['value'] = \WC_Admin_Settings::get_option( $value['id'], $value['default'] );
		}

		$option_value = $value['value'];

		if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
			?>
			<tr valign="top">
			<th scope="row" class="titledesc">
				<span class="sab-label-wrap"><?php echo esc_html( $value['title'] ); ?><?php echo $tooltip_html; // WPCS: XSS ok. ?></span>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
			<?php
		}

		$args                      = $value;
		$args['value']             = $option_value;
		$args['description']       = $description;

		\Vendidero\StoreaBill\Admin\Fields::render_toggle_field( $args );
		?>
		<?php if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
			?>
			</td>
			</tr>
			<?php
		}
	}
}