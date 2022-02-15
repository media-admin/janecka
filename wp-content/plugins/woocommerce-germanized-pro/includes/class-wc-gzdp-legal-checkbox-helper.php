<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Legal_Checkbox_Helper {

	protected static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
	    add_action( 'woocommerce_checkout_create_order', array( $this, 'save_checkout_checkboxes' ), 10, 2 );
		add_action( 'woocommerce_created_customer', array( $this, 'save_register_checkboxes' ), 10, 3 );
		add_action( 'woocommerce_before_pay_action', array( $this, 'save_pay_for_order_checkboxes' ), 30, 1 );
		add_action( 'wp_insert_comment', array( $this, 'save_reviews_checkboxes' ), 10, 1 );

		if ( is_admin() ) {
			$this->admin_hooks();
		}
	}

	public function admin_hooks() {
		add_filter( 'woocommerce_gzd_admin_legal_checkbox', array( $this, 'maybe_add_checkbox' ), 10, 2 );
		add_action( 'woocommerce_gzd_before_save_legal_checkbox', array( $this, 'adjust_new_saving' ), 10, 1 );
		add_filter( 'woocommerce_gzd_admin_new_legal_checkbox_link', array( $this, 'adjust_new_link' ), 10 );
		add_filter( 'woocommerce_gzd_legal_checkbox_fields_before_titles', array( $this, 'additional_fields' ), 10, 2 );

		/** Display */
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_checkout_checkboxes' ), 10, 1 );
		add_action( 'show_user_profile', array( $this, 'display_register_checkboxes' ) );
		add_action( 'edit_user_profile', array( $this, 'display_register_checkboxes' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_review_meta_box' ), 30 );
	}

	/**
	 * @param $checkbox
	 * @param WC_Order|WP_User|WP_Comment $object
	 *
	 * @return bool
	 */
	public function checkbox_is_checked( $checkbox, $object ) {
	    $meta_key = "_checkbox_{$checkbox->get_id()}";

		if ( is_a( $object, 'WC_Order' ) ) {
			return $object->get_meta( $meta_key ) === 'yes' ? true : false;
		} elseif( is_a( $object, 'WP_User' ) ) {
			return get_user_meta( $object->ID, $meta_key, true ) === 'yes' ? true : false;
		} elseif ( is_a( $object, 'WP_Comment' ) ) {
			return get_comment_meta( $object->comment_ID, $meta_key, true ) === 'yes' ? true : false;
        }

		return false;
	}

	public function add_review_meta_box() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$checkboxes = $this->get_checkboxes( 'reviews' );

		if ( ! empty( $checkboxes ) ) {
			// Comment rating.
			if ( 'comment' === $screen_id && isset( $_GET['c'] ) && metadata_exists( 'comment', $_GET['c'], 'rating' ) ) {
				add_meta_box( 'woocommerce-gzdp-rating-checkboxes', __( 'Checkboxes', 'woocommerce-germanized-pro' ), array( $this, 'display_reviews_checkboxes' ), 'comment', 'normal', 'high' );
			}
		}
	}

	public function display_checkout_checkboxes( $order ) {
		$checkboxes      = $this->get_checkboxes( 'checkout' );
		$checkboxes      = array_merge( $checkboxes, $this->get_checkboxes( 'pay_for_order' ) );
		$checkbox_object = $order;

		if ( ! empty( $checkboxes ) ) {
			include_once( WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/views/html-admin-table-checkboxes.php' );
        }
	}

	public function display_reviews_checkboxes( $comment ) {
		$checkboxes      = $this->get_checkboxes( 'reviews' );
		$checkbox_object = $comment;

		if ( ! empty( $checkboxes ) ) {
			include_once( WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/views/html-admin-table-checkboxes.php' );
        }
	}

	public function display_register_checkboxes( $user ) {
		$checkboxes      = $this->get_checkboxes( 'register' );
		$checkbox_object = $user;

		if ( empty( $checkboxes ) )
		    return;

		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th><label for="woocommerce-gzdp-checkboxes"><?php _e( 'Register Form', 'woocommerce-germanized-pro' ); ?></label></th>
					<td>
						<?php include_once( WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/views/html-admin-table-checkboxes.php' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * @param WC_Order $order
	 * @param $posted
	 */
	public function save_checkout_checkboxes( $order, $posted ) {
		$checkboxes = $this->get_checkboxes( 'checkout' );

		foreach( $checkboxes as $checkbox ) {
		    $checked = false;

			if ( isset( $_POST[ $checkbox->get_html_name() ] ) ) {
			    $checked = true;
			} elseif( $checkbox->hide_input() ) {
			    $checked = true;
			}

			$order->update_meta_data(  "_checkbox_{$checkbox->get_id()}", $checked ? 'yes' : 'no' );

			do_action( "woocommerce_gzdp_legal_checkbox_stored", $checkbox, $checked );
		}
	}

	/**
	 * @param WC_Order $order
	 */
	public function save_pay_for_order_checkboxes( $order ) {
		$checkboxes = $this->get_checkboxes( 'pay_for_order' );

		foreach( $checkboxes as $checkbox ) {
			$checked = false;

			if ( isset( $_POST[ $checkbox->get_html_name() ] ) ) {
				$checked = true;
			} elseif( $checkbox->hide_input() ) {
				$checked = true;
			}

			$order->update_meta_data( "_checkbox_{$checkbox->get_id()}", $checked ? 'yes' : 'no' );

			do_action( "woocommerce_gzdp_legal_checkbox_stored", $checkbox, $checked );
		}
	}

	public function save_register_checkboxes( $customer_id, $new_customer_data, $password_generated ) {
		foreach( $this->get_checkboxes( 'register' ) as $checkbox ) {
			$checked = false;

			if ( isset( $_POST[ $checkbox->get_html_name() ] ) ) {
				$checked = true;
			} elseif( $checkbox->hide_input() ) {
				$checked = true;
			}

			update_user_meta( $customer_id, "_checkbox_{$checkbox->get_id()}", $checked ? 'yes' : 'no' );

			do_action( "woocommerce_gzdp_legal_checkbox_stored", $checkbox, $checked );
		}
	}

	public function save_reviews_checkboxes( $comment_id ) {
		$checkboxes = $this->get_checkboxes( 'reviews' );

		foreach( $checkboxes as $checkbox ) {
			$checked = false;

			if ( isset( $_POST[ $checkbox->get_html_name() ] ) ) {
				$checked = true;
			} elseif( $checkbox->hide_input() ) {
				$checked = true;
			}

			update_comment_meta( $comment_id, "_checkbox_{$checkbox->get_id()}", $checked ? 'yes' : 'no' );

			do_action( "woocommerce_gzdp_legal_checkbox_stored", $checkbox, $checked );
		}
	}

	public function get_checkboxes( $location = 'checkout' ) {
		WC_GZD_Legal_Checkbox_Manager::instance()->do_register_action();
		$checkboxes = WC_GZD_Legal_Checkbox_Manager::instance()->get_checkboxes( array( 'locations' => $location, 'store_status' => 'yes', 'is_enabled' => true ) );

		return $checkboxes;
	}

	protected function to_assoc( $arr ) {
		foreach( $arr as $key => $value ) {
			unset( $arr[ $key ] );
			$arr[ $value ] = $value;
		}

		return $arr;
	}

	/**
	 * @param $fields
	 * @param WC_GZD_Legal_Checkbox $checkbox
	 *
	 * @return mixed
	 */
	public function additional_fields( $fields, $checkbox ) {

		$classes         = $checkbox->get_option( 'html_classes', array() );
		$wrapper_classes = $checkbox->get_option( 'html_wrapper_classes', array() );

		$additional_fields = array(
			array(
				'title'             => __( 'Save Status', 'woocommerce-germanized-pro' ),
				'type'              => 'gzd_toggle',
				'id'                => $checkbox->get_form_field_id( 'store_status' ),
				'desc'              => __( 'Register checkbox value within meta data.', 'woocommerce-germanized-pro' ) . '<div class="wc-gzd-additional-desc">' . sprintf( __( 'Store the checkbox status chosen by the user within meta data and display the status in the admin panel (e.g. order data). Find out more about possible customizations %s.', 'woocommerce-germanized-pro' ), '<a href="https://vendidero.de/dokument/status-der-checkbox-dokumentieren" target="_blank">' . __( 'here', 'woocommerce-germanized-pro' ) . '</a>' ) . '</div>',
				'default'           => 'no',
			),

			array(
				'title'             => __( 'Template', 'woocommerce-germanized-pro' ),
				'type'              => 'text',
				'id'                => $checkbox->get_form_field_id( 'template_name' ),
				'desc'              => sprintf( __( 'Override the template within your (child) theme: %s', 'woocommerce-germanized-pro' ), '<br/><code>child-theme/woocommerce-germanized/' . $checkbox->get_template_name() . '</code>' ),
				'desc_tip'          => __( 'Adjust the PHP template being loaded for this checkbox.', 'woocommerce-germanized-pro' ),
				'default'           => $checkbox->get_template_name(),
			),

			array(
				'title'             => __( 'HTML Id', 'woocommerce-germanized-pro' ),
				'type'              => 'text',
				'id'                => $checkbox->get_form_field_id( 'html_id' ),
				'desc_tip'          => __( 'Adjust the PHP template being loaded for this checkbox.', 'woocommerce-germanized-pro' ),
				'default'           => $checkbox->get_html_id(),
			),

			array(
				'title'             => __( 'HTML Name', 'woocommerce-germanized-pro' ),
				'type'              => 'text',
				'id'                => $checkbox->get_form_field_id( 'html_name' ),
				'desc_tip'          => __( 'Adjust the HTML name attribute.', 'woocommerce-germanized-pro' ),
				'default'           => $checkbox->get_html_name(),
			),
			array(
				'title'             => __( 'HTML Classes', 'woocommerce-germanized-pro' ),
				'type'              => 'multiselect',
				'class'             => 'wc-gzd-enhanced-tags',
				'id'                => $checkbox->get_form_field_id( 'html_classes' ),
				'desc_tip'          => __( 'Add or edit classes for the input checkbox. Add classes by typing in a term and then select that term from the dropdown list.', 'woocommerce-germanized-pro' ),
				'default'           => $this->to_assoc( $classes ),
				'options'           => $this->to_assoc( $classes ),
			),

			array(
				'title'             => __( 'HTML Wrapper Classes', 'woocommerce-germanized-pro' ),
				'type'              => 'multiselect',
				'class'             => 'wc-gzd-enhanced-tags',
				'id'                => $checkbox->get_form_field_id( 'html_wrapper_classes' ),
				'desc_tip'          => __( 'Add or edit classes for the wrapper p-tag. Add classes by typing in a term and then select that term from the dropdown list.', 'woocommerce-germanized-pro' ),
				'default'           => $this->to_assoc( $wrapper_classes ),
				'options'           => $this->to_assoc( $wrapper_classes ),
			),
		);

		$fields = array_merge( $fields, $additional_fields );

        return $fields;
	}

	public function adjust_new_link() {
		return admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&checkbox_id=new' );
	}

	public function adjust_new_saving( $checkbox ) {
		if ( $checkbox->is_new() ) {
			// Parse admin name
			$name = ( isset( $_POST['woocommerce_gzd_checkboxes_new_admin_name'] ) ? wc_clean( $_POST['woocommerce_gzd_checkboxes_new_admin_name'] ) : 'new' );
			$id   = $this->generate_id_by_name( $name );
			$checkbox->set_id( $id );

			// Replace $_POST keys with new id
			foreach( $_POST as $key => $value ) {
				if ( strpos( $key, 'woocommerce_gzd_checkboxes_new' ) !== false ) {
					$new_key = str_replace( 'woocommerce_gzd_checkboxes_new', 'woocommerce_gzd_checkboxes_' . $id, $key );
					$_POST[ $new_key ] = $value;
					unset( $_POST[ $key ] );
				}
			}

			add_action( 'woocommerce_gzd_after_save_legal_checkbox', array( $this, 'redirect_new' ), 10, 1 );
		}
	}

	public function redirect_new( $checkbox ) {
		$url = admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&&checkbox_id=' . $checkbox->get_id() );

        wp_safe_redirect( $url );
	}

	public function redirect_new_js() {
		$checkbox = isset( $GLOBALS['checkbox'] ) ? $GLOBALS['checkbox'] : false;

		if ( $checkbox ) {
			$url = admin_url( 'admin.php?page=wc-settings&tab=germanized-checkboxes&checkbox_id=' . $checkbox->get_id() );
			echo "<script type='text/javascript'>\n";
			echo 'window.location.replace("' . $url . '")';
			echo "\n</script>";
		}
	}

	public function maybe_add_checkbox( $checkbox, $checkbox_id ) {

		if ( ! $checkbox ) {
			$checkbox = new WC_GZD_Legal_Checkbox( 'new', array(
				'admin_name' => __( 'New', 'woocommerce-germanized-pro' ),
			) );

			$locations = array_keys( WC_GZD_Legal_Checkbox_Manager::instance()->get_locations() );
			$checkbox->set_supporting_locations( $locations );
		}

		return $checkbox;
	}

	protected function generate_id_by_name( $name = '', $postfix = '' ) {
		$id = str_replace( '-', '_', sanitize_title( ( $name === '' ? 'new' : $name ) ) );

		if ( ! empty( $postfix ) ) {
			$postfix = absint( $postfix );
			$id = $id . '_' . $postfix;
		}

		if ( $exists = WC_GZD_Legal_Checkbox_Manager::instance()->get_checkbox( $id ) || 'new' === $id ) {
			if ( empty( $postfix ) ) {
				$postfix = 0;
			}

			$id = $this->generate_id_by_name( $id, ++$postfix );
		}

		return $id;
	}

}

return WC_GZDP_Legal_Checkbox_Helper::instance();