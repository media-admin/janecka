<?php

namespace Vendidero\StoreaBill\Admin;

use Vendidero\StoreaBill\ExternalSync\Helper;
use Vendidero\StoreaBill\References\Customer;

defined( 'ABSPATH' ) || exit;

class Users {

	/**
	 * Constructor.
	 */
	public static function init() {
		/**
		 * Render bulk progress
		 */
		add_action( 'manage_users_extra_tablenav', array( __CLASS__, 'render_bulk_actions' ), 150, 1 );
		add_filter( 'bulk_actions-users', array( __CLASS__, 'add_bulk_actions' ) );

		add_action( 'show_user_profile', array( __CLASS__, 'add_sync_fields' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'add_sync_fields' ) );

		add_action( 'personal_options_update', array( __CLASS__, 'save_sync_fields' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_sync_fields' ) );
	}

	public static function save_sync_fields( $user_id ) {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return false;
		}

		$handlers = Helper::get_available_sync_handlers();

		if ( empty( $handlers ) ) {
			return false;
		}

		if ( ! $customer = Customer::get_customer( $user_id, 'woocommerce' ) ) {
		    return false;
        }

		foreach( $handlers as $handler ) {

		    if ( ! $handler::is_object_type_supported( 'customer' ) ) {
		        continue;
            }

		    $input_name  = 'customer_id_' . $handler::get_name();
		    $customer_id = isset( $_POST[ $input_name ] ) ? sab_clean( $_POST[ $input_name ] ) : '';

		    if ( empty( $customer_id ) ) {
                $customer->remove_external_sync_handler( $handler::get_name() );
            } else {
			    $customer->update_external_sync_handler( $handler::get_name(), array( 'id' => $customer_id ) );
            }
        }

		return true;
    }

	public static function add_sync_fields( $user ) {
	    $handlers = Helper::get_available_sync_handlers();

	    if ( empty( $handlers ) ) {
	        return;
        }
	    ?>
        <h3><?php _ex( 'External sync', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></h3>
        <table class="form-table">
            <?php foreach( $handlers as $handler ) :
                if ( ! $handler::is_object_type_supported( 'customer' ) ) {
                    continue;
                }

                $customer_id     = '';
                $customer_string = '';
                $customer_url    = '';

                if ( $customer = Customer::get_customer( $user->ID, 'woocommerce' ) ) {
	                if ( $details = $handler->get_customer_details( $customer ) ) {
		                $customer_id     = $details['id'];
		                $customer_string = $details['label'];
		                $customer_url    = $details['url'];
	                }
                }
            ?>
            <tr>
                <th><label for="customer_id_<?php echo esc_attr( $handler::get_name() ); ?>"><?php printf( _x( '%s customer', 'storeabill-core', 'woocommerce-germanized-pro' ), $handler::get_title() ); ?></label></th>
                <td>
                    <select class="sab-external-customer-search" id="customer_id_<?php echo esc_attr( $handler::get_name() ); ?>" name="customer_id_<?php echo esc_attr( $handler::get_name() ); ?>" data-handler="<?php echo esc_attr( $handler::get_name() ); ?>" data-placeholder="<?php echo esc_attr_x( 'Filter by customer', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>" data-allow_clear="true" style="width: 25em;">
                        <option value="<?php echo esc_attr( $customer_id ); ?>" selected="selected"><?php echo htmlspecialchars( wp_kses_post( $customer_string ) ); // htmlspecialchars to prevent XSS when rendered by selectWoo. ?><option>
                    </select>
                    <span class="description" style="margin-left: 5px;">
                        <?php if ( ! empty( $customer_url ) ) : ?>
                            <a href="<?php echo esc_url( $customer_url ); ?>" target="_blank"><?php _ex( 'View customer', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                        <?php endif; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

	public static function add_bulk_actions( $actions ) {
		foreach( \Vendidero\StoreaBill\Admin\Admin::get_bulk_actions_handlers( 'customer' ) as $handler ) {
			$actions[ $handler->get_action() ] = $handler->get_title();
		}

		return $actions;
	}

	public static function render_bulk_actions( $which ) {
		if ( 'top' === $which ) {
			$finished    = ( isset( $_GET['bulk_action_handling'] ) && 'finished' === $_GET['bulk_action_handling'] ) ? true : false;
			$bulk_action = ( isset( $_GET['current_bulk_action'] ) ) ? sab_clean( $_GET['current_bulk_action'] ) : '';

			if ( $finished && ( $handler = \Vendidero\StoreaBill\Admin\Admin::get_bulk_action_handler( $bulk_action, 'customer' ) ) && check_admin_referer( $handler->get_done_nonce_action() ) ) {
				$handler->finish();
			}
			?>
			<div class="sab-bulk-action-wrapper">
				<h4 class="bulk-title"><?php _ex(  'Processing bulk actions...', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></h4>
				<div class="sab-bulk-notice-wrapper"></div>
				<progress class="sab-bulk-progress sab-progress-bar" max="100" value="0"></progress>
			</div>
			<?php
		}
	}
}