<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

class WC_GZDP_Multistep_Checkout {

	/**
	 * Single instance of WooCommerce Germanized Main Class
	 *
	 * @var object
	 */
	protected static $_instance = null;

	public $steps = array();

	public $compatibility = array();

	private $posted_data = array();

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {

		if ( is_admin() )  {
			$this->admin_hooks();
		}

		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( ! did_action( 'wp_loaded' ) ) {
			add_action( 'wp_loaded', array( $this, 'init' ) );
		} else {
			$this->init();
		}

		if ( ! did_action( 'woocommerce_loaded' ) ) {
			add_action( 'woocommerce_loaded', array( $this, 'template_hooks' ), 6 );
		} else {
			$this->template_hooks();
		}
	}

	public function init() {

		$this->init_steps();

		$this->compatibility = array(
			'woo-paypal-plus/woo-paypal-plus.php' => 'Woo_Paypal_Plus',
			'woocommerce-gateway-amazon-payments-advanced/amazon-payments-advanced.php' => 'Amazon_Payments_Advanced',
			'woocommerce-gateway-amazon-payments-advanced/woocommerce-gateway-amazon-payments-advanced.php' => 'Amazon_Payments_Advanced',
		);

		$this->plugin_compatibility();

		add_action( 'get_header', array( $this, 'refresh_step_numbers' ) );
		// Some themes (e.g. flatsome) may not load the header via get_header within checkout - using wp_head as fallback.
		add_action( 'wp_head', array( $this, 'refresh_step_numbers' ), 1 );

		add_action( 'woocommerce_before_checkout_form', array( $this, 'print_steps' ), 0, 1 );
		add_action( 'woocommerce_checkout_process', array( $this, 'check_step_submit' ), 25 );
		
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'refresh_order_fragments' ), 1 );
		
		// Load checkout assets
		add_action( 'woocommerce_gzdp_frontend_styles', array( $this, 'set_styles' ) );
		add_action( 'woocommerce_gzdp_frontend_scripts', array( $this, 'set_scripts' ) );
		add_filter( 'body_class', array( $this, 'set_body_class' ), 0 );
		
		// Override form-checkout.php template if necessary
		add_filter( 'woocommerce_gzdp_filter_template', array( $this, 'stop_checkout_theme_override' ), 0, 3 );

		// AJAX Step refresh
        add_action( 'wp_ajax_woocommerce_gzdp_multistep_refresh_step', array( $this, 'ajax_refresh_step' ) );
        add_action( 'wp_ajax_nopriv_woocommerce_gzdp_multistep_refresh_step', array( $this, 'ajax_refresh_step' ) );

		if ( get_option( 'woocommerce_gzdp_checkout_privacy_policy_first_step' ) === 'yes' ) {
			$this->insert_privacy_policy();
		}

		/**
		 * Do not allow disabling checkout adjustments when using multistep checkout
		 */
		add_filter( 'woocommerce_gzd_allow_disabling_checkout_adjustments', '__return_false', 1000 );
	}

	public function insert_privacy_policy() {
		add_action( 'woocommerce_gzdp_before_step_submit_content', array( $this, 'maybe_insert_privacy_checkbox' ), 10, 1 );
	}

	public function maybe_insert_privacy_checkbox( $step ) {
		if ( ! wp_doing_ajax() && 'address' === $step->get_id() ) {
		    wc_get_template( 'checkout/multistep/privacy.php' );
		}
	}

	public function ajax_refresh_step() {

	    if ( ! wp_verify_nonce( $_POST['wc_gzdp_multistep_refresh_step'], 'wc_gzdp_multistep_refresh_step' ) ) {
            wp_send_json_error( 'bad_nonce' );
            wp_die();
        }

        $step_id = wc_clean( $_POST['step'] );
        $exists  = false;

        foreach( $this->steps as $step ) {
            if ( $step->get_id() === $step_id ) {
                $exists = true;
                break;
            }
        }

        if ( $exists ) {
            WC()->session->set( 'checkout_step', $step_id );

            ob_start();
            wc_get_template( 'checkout/multistep/steps.php', array( 'multistep' => $this ) );
            $step_data = ob_get_clean();

            wp_send_json( array(
                'step'      => $step_id,
                'fragments' => array(
                    '.step-nav' => $step_data,
                )
            ) );
        }
    }

	public function plugin_compatibility() {
		foreach ( $this->compatibility as $plugin => $suffix ) {
			if ( WC_GZDP_Dependencies::instance()->is_plugin_activated( $plugin ) ) {
				$classname = 'WC_GZDP_Checkout_Compatibility_' . $suffix;
				$this->compatibility[ $plugin ] = new $classname();
			}
		}
	}

	public function is_enabled() {
		return ( get_option( 'woocommerce_gzdp_checkout_enable' ) == 'yes' ? true : false );
	}

	public function set_body_class( $classes ) {

		if ( is_checkout() )
			$classes[] = 'woocommerce-multistep-checkout';

		return $classes;

	}

	public function set_styles( $assets ) {
		if ( ! is_checkout() ) {
			return;
		}

		$suffix = $assets->suffix;

		if ( 'navigation' !== $this->get_layout_style() ) {
			$suffix = '-' . sanitize_key( $this->get_layout_style() ) . $suffix;
		}

		wp_register_style( 'wc-gzdp-checkout', WC_germanized_pro()->plugin_url() . '/assets/css/checkout-multistep' . $suffix . '.css', array(), WC_GERMANIZED_PRO_VERSION );
		wp_enqueue_style( 'wc-gzdp-checkout' );

		wp_add_inline_style( 'wc-gzdp-checkout', $this->get_custom_css() );
	}

	protected function get_layout_style() {
		return get_option( 'woocommerce_gzdp_checkout_layout_style', 'plain' );
	}

	public function get_custom_css() {
		$layout_style = $this->get_layout_style();

		if ( 'navigation' === $layout_style ) {
			$colors = apply_filters( 'woocommerce_gzdp_checkout_step_colors', array(
				'bg'				=> get_option( 'woocommerce_gzdp_checkout_bg', '#f9f9f9' ),
				'border'			=> get_option( 'woocommerce_gzdp_checkout_border_color', '#d4d4d4' ),
				'color'				=> get_option( 'woocommerce_gzdp_checkout_font_color', '#468847' ),
				'active_bg' 		=> get_option( 'woocommerce_gzdp_checkout_active_bg', '#d9edf7' ),
				'active_color' 		=> get_option( 'woocommerce_gzdp_checkout_active_font_color', '#3a87ad' ),
				'disabled_bg'		=> get_option( 'woocommerce_gzdp_checkout_disabled_bg', '#ededed' ),
				'disabled_color'	=> get_option( 'woocommerce_gzdp_checkout_disabled_font_color', '#999999' ),
			) );

			$css = '

			.woocommerce-multistep-checkout ul.nav-wizard {
			    background-color: ' . $colors[ 'bg' ] . ';
			    border-color: ' . $colors[ 'border' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard:before {
			    border-top-color: ' . $colors[ 'border' ] . ';
			    border-bottom-color: ' . $colors[ 'border' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard:after {
			    border-top-color: ' . $colors[ 'border' ] . ';
			    border-bottom-color: ' . $colors[ 'border' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard li a {
			    color: ' . $colors[ 'color' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard li:before {
			    border-left-color: ' . $colors[ 'border' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard li:after {
			    border-left-color: ' . $colors[ 'bg' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard li.active {
			    color: ' . $colors[ 'active_color' ] . ';
			    background: ' . $colors[ 'active_bg' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard li.active:after {
			    border-left-color: ' . $colors[ 'active_bg' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard li.active a,
			.woocommerce-multistep-checkout ul.nav-wizard li.active a:active,
			.woocommerce-multistep-checkout ul.nav-wizard li.active a:visited,
			.woocommerce-multistep-checkout ul.nav-wizard li.active a:focus {
			    color: ' . $colors[ 'active_color' ] . ';
			    background: ' . $colors[ 'active_bg' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard .active ~ li {
			    color: ' . $colors[ 'disabled_color' ] . ';
			    background: ' . $colors[ 'disabled_bg' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard .active ~ li:after {
			    border-left-color: ' . $colors[ 'disabled_bg' ] . ';
			}
	
			.woocommerce-multistep-checkout ul.nav-wizard .active ~ li a,
			.woocommerce-multistep-checkout ul.nav-wizard .active ~ li a:active,
			.woocommerce-multistep-checkout ul.nav-wizard .active ~ li a:visited,
			.woocommerce-multistep-checkout ul.nav-wizard .active ~ li a:focus {
			    color: ' . $colors[ 'disabled_color' ] . ';
			    background: ' . $colors[ 'disabled_bg' ] . ';
			}
			';
		} elseif ( 'plain' === $layout_style ) {
			$colors = apply_filters( 'woocommerce_gzdp_checkout_step_colors', array(
				'primary'   => get_option( 'woocommerce_gzdp_checkout_plain_primary_color', '#3a87ad' ),
				'secondary' => get_option( 'woocommerce_gzdp_checkout_plain_secondary_color', '#d4d4d4' ),
			) );

			$css = '
			.woocommerce-multistep-checkout ul.nav-wizard li a, .woocommerce-multistep-checkout ul.nav-wizard li.active ~ li a {
			    color: ' . $colors['primary'] . ';
			}
			.woocommerce-multistep-checkout ul.nav-wizard li.active a {
			    color: ' . $colors['primary'] . ';
			}
			.woocommerce-multistep-checkout ul.nav-wizard li:not(:first-child)::before, .woocommerce-multistep-checkout ul.nav-wizard li a::before {
			    background: ' . $colors['primary'] . ';
			}
			
			.woocommerce-multistep-checkout ul.nav-wizard li.active a::before {
			    border-color: ' . $colors['primary'] . ';
			}
			
			.woocommerce-multistep-checkout ul.nav-wizard li.active ~ li::before {
			    background: ' . $colors['secondary'] . ';
			}
			
			.woocommerce-multistep-checkout ul.nav-wizard li.active ~ li a {
			    color: ' . $colors['secondary'] . ';
			}
			
			.woocommerce-multistep-checkout ul.nav-wizard li.active ~ li a::before {
			    background: ' . $colors['secondary'] . ';
			}
			';
		}
		
		return apply_filters( 'woocommerce_gzdp_checkout_custom_css', $css, $layout_style );
	}

	public function set_scripts( $assets ) {
		
		if ( ! is_checkout() || is_wc_endpoint_url() ) {
			return;
		}

		// Multistep Checkout
		wp_register_script( 'wc-gzdp-checkout-multistep', WC_germanized_pro()->plugin_url() . '/assets/js/checkout-multistep' . $assets->suffix . '.js', array( 'wc-checkout' ), WC_GERMANIZED_PRO_VERSION, true );
		
		wp_localize_script( 'wc-gzdp-checkout-multistep', 'wc_gzd_multistep_checkout_params', array(
		    'wrapper'            => 'step-wrapper',
            'ajax_url'           => admin_url( 'admin-ajax.php' ),
            'refresh_step_nonce' => wp_create_nonce( 'wc_gzdp_multistep_refresh_step' ),
            'steps'              => $this->get_step_data(),
        ) );

		wp_enqueue_script( 'wc-gzdp-checkout-multistep' );

		$needs_compatibility = array(
            'payone-woocommerce-3/payone-woocommerce-3.php',
			'mollie-payments-for-woocommerce/mollie-payments-for-woocommerce.php',
			'woo-stripe-payment/stripe-payments.php',
			'woocommerce-gateway-stripe/woocommerce-gateway-stripe.php',
			'woocommerce-payments/woocommerce-payments.php'
        );

		$enable_payment_compatibility_script = false;

		foreach( $needs_compatibility as $plugin ) {
		    if ( WC_GZDP_Dependencies::instance()->is_plugin_activated( $plugin ) ) {
                $enable_payment_compatibility_script = true;
                break;
            }
        }

		if ( apply_filters( 'woocommerce_gzdp_multistep_checkout_enable_payment_compatibility_mode', $enable_payment_compatibility_script ) ) {
			wp_register_script( 'wc-gzdp-checkout-multistep-payment-compatibility', WC_germanized_pro()->plugin_url() . '/assets/js/checkout-multistep-payment-compatibility' . $assets->suffix . '.js', array( 'wc-gzdp-checkout-multistep' ), WC_GERMANIZED_PRO_VERSION, true );

			wp_localize_script( 'wc-gzdp-checkout-multistep-payment-compatibility', 'wc_gzdp_multistep_checkout_payment_compatibility_params', array(
				'gateways' => apply_filters( 'woocommerce_gzdp_multistep_checkout_payment_compatibility_gateways', array(
					'mollie_wc_gateway_creditcard',
					'payone',
					'stripe_cc',
					'stripe_eps',
					'stripe',
					'stripe_sepa',
					'stripe_sofort',
					'stripe_klarna',
					'woocommerce_payments'
				) ),
				'force_enable' => has_filter( 'woocommerce_gzdp_multistep_checkout_enable_payment_compatibility_mode' ) ? true : false
			) );

			wp_enqueue_script( 'wc-gzdp-checkout-multistep-payment-compatibility' );
		}

        // Payment method compatibility: Paymill
		if ( WC_GZDP_Dependencies::instance()->is_plugin_activated( 'paymill/paymill.php' ) ) {
			
			// Change submit id
			wp_register_script( 'wc-gzdp-paymill-multistep-helper', WC_germanized_pro()->plugin_url() . '/assets/js/checkout-multistep-paymill-helper.js', array( 'wc-gzdp-checkout-multistep' ), WC_GERMANIZED_PRO_VERSION, true );
			wp_enqueue_script( 'wc-gzdp-paymill-multistep-helper' );

			// Handle payment step
			wp_register_script( 'wc-gzdp-paymill-multistep-helper-submit', WC_germanized_pro()->plugin_url() . '/assets/js/checkout-multistep-paymill-submit-helper.js', array( 'paymill_bridge_custom' ), WC_GERMANIZED_PRO_VERSION, true );
			wp_enqueue_script( 'wc-gzdp-paymill-multistep-helper-submit' );

		}

		do_action( 'woocommerce_gzdp_checkout_scripts', $this, $assets );
	}

	public function stop_checkout_theme_override( $template, $template_name, $template_path ) {
		if ( $template_name == 'checkout/form-checkout.php' ) {

			// Maybe force template override
			if ( 'yes' === get_option( 'woocommerce_gzdp_checkout_force_template_override' ) || apply_filters( 'woocommerce_gzdp_multistep_checkout_force_template_override', false ) ) {
				$template = WC()->plugin_path() . '/templates/' . $template_name;
			}
			
			// Allow theme override by using woocommerce-germanized-pro/checkout/multistep/form-checkout.php template
			if ( $loc = locate_template( WC_germanized_pro()->template_path() . 'checkout/multistep/form-checkout.php' ) ) {
				$template = $loc;
			} else {
				$template = apply_filters( 'woocommerce_gzdp_checkout_template_not_found', $template, 'checkout/multistep/form-checkout.php', $template_path );
			}
		}

		return $template;
	}

	public function set_posted_data( $data ) {
		$this->posted_data = $data;
		WC()->session->set( 'checkout_posted', $this->posted_data );
	}

	public function get_posted_data() {
		return ( empty( $this->posted_data ) ? (array) WC()->session->get( 'checkout_posted' ) : $this->posted_data );
	}

	public function get_current_step() {
		$current = WC()->session->get( 'checkout_step', 'address' );

		return $this->get_step( $current );
	}

	public function refresh_order_fragments( $fragments ) {
		$this->refresh_step_numbers();

		ob_start();
		$this->order_step_data();
		$data = ob_get_clean();

		ob_start();
		wc_get_template( 'checkout/multistep/steps.php', array( 'multistep' => $this ) );
		$step_data = ob_get_clean();

		// Unset woocommerce checkout payment refresh for step 2 (so that the data inserted by the user is not lost)
		if ( WC()->session->get( 'checkout_step' ) && 'payment' === WC()->session->get( 'checkout_step' ) ) {
			unset( $fragments['.woocommerce-checkout-payment' ]);
			$fragments = array_filter( $fragments );
		}

		$fragments['.woocommerce-gzdp-checkout-verify-data'] = $data;
		$fragments['.step-nav'] = $step_data;

		foreach( $this->steps as $checkout_step ) {
			ob_start();
			wc_get_template( 'checkout/multistep/submit.php', array( 'step' => $checkout_step ) );
			$submit_data = ob_get_clean();

			$fragments['.step-buttons-' . $checkout_step->get_id() ] = $submit_data;
		}

		return $fragments;
	}

	public function template_hooks() {
		if ( get_option( 'woocommerce_gzdp_checkout_verify_data_output' ) === 'yes' ) {
			$hook_priority = function_exists( 'wc_gzd_get_hook_priority' ) ? ( wc_gzd_get_hook_priority( 'checkout_legal' ) - 5 ) : 1;

            add_action( 'woocommerce_review_order_after_payment', array( $this, 'order_step_data' ), $hook_priority );
        }
	}

	public function order_step_data() {
		wc_get_template( 'checkout/multistep/data.php', array( 'multistep' => $this, 'checkout' => WC()->checkout ) );
	}

	public function check_step_submit() {

		// Init payment methods so that their hooks are registered before checking
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		if ( isset( $_POST[ 'wc_gzdp_step_submit' ] ) && ! empty( $_POST[ 'wc_gzdp_step_submit' ] ) ) {
			$action = ( isset( $_POST[ 'wc_gzdp_step_refresh' ] ) ? 'refresh' : 'submit' );

			if ( $step = $this->get_step( sanitize_text_field( $_POST[ 'wc_gzdp_step_submit' ] ) ) ) {
				do_action( 'woocommerce_gzdp_checkout_step_' . $step->get_id() . '_' . $action );
			}
		}
	}

	public function get_step_names() {
		return apply_filters( 'woocommerce_gzdp_checkout_steps', array(
			'address'	=> get_option( 'woocommerce_gzdp_checkout_step_title_address', _x( 'Personal Data', 'multistep', 'woocommerce-germanized-pro' ) ),
			'payment'	=> get_option( 'woocommerce_gzdp_checkout_step_title_payment', _x( 'Payment', 'multistep', 'woocommerce-germanized-pro' ) ),
			'order'		=> get_option( 'woocommerce_gzdp_checkout_step_title_order', _x( 'Order', 'multistep', 'woocommerce-germanized-pro' ) ),
		) );
	}

	public function init_steps() {
		$steps = $this->get_step_names();

		foreach ( $steps as $key => $step ) {
			$classname = 'WC_GZDP_Checkout_Step_' . ucfirst( $key );

			if ( class_exists( $classname ) ) {
				array_push( $this->steps, new $classname( $key, $step ) );
            }
		}

		$count = 0;

		foreach( $this->steps as $key => $step ) {
			if ( isset( $this->steps[ $key - 1 ] ) ) {
				$this->steps[ $key ]->prev = $this->steps[ $key - 1 ];
			}

			if ( isset( $this->steps[ $key + 1 ] ) ) {
				$this->steps[ $key ]->next = $this->steps[ $key + 1 ];
            }

			$this->steps[ $key ]->number = ++$count;
		}
	}

	public function refresh_step_numbers() {

		$current = '';

		if ( is_checkout() ) {
			// Init cart and calculate totals to see whether cart needs payment
			if ( ! empty( WC()->cart ) ) {
				WC()->cart->calculate_totals();
			}

			$current = WC()->session->get( 'checkout_step' );
		}
		
		$count = 0;

		$steps = $this->steps;

		foreach ( $steps as $key => $step ) {
			if ( ! $step->is_activated() ) {

				// Make sure we are able to skip a step (e.g. payment) if step is not available. Set the checkout_step session to next id.
				if ( $current === $step->get_id() && $step->has_next() ) {
					WC()->session->set( 'checkout_step', $step->next->get_id() );
				}

				unset( $steps[ $key ] );
			} else {
				$steps[ $key ]->next = null;
				$steps[ $key ]->prev = null;
				$steps[ $key ]->number = 0;
			}
		}

		$steps = array_values( $steps );

		foreach( $steps as $key => $step ) {

			if ( isset( $steps[ $key - 1 ] ) )
				$step->prev = $steps[ $key - 1 ];

			if ( isset( $steps[ $key + 1 ] ) )
				$step->next = $steps[ $key + 1 ];

			$step->number = ++$count;

			foreach ( $this->steps as $org_step_key => $org_step ) {

				if ( $org_step->get_id() === $step->get_id() ) {
					$this->steps[ $org_step_key ] = $step;
					break;
				}

			}

		}

		// If we reach this line make sure we are not double-checking steps
		// Seems like YOAST causes issues when using 0 as priority - use 1 instead
		remove_action( 'wp_head', array( $this, 'refresh_step_numbers' ), 1 );
	}

	public function get_step( $id ) {

		foreach ( $this->steps as $step ) {

			if ( $step->get_id() == $id )
				return $step;

		}

		return false;

	}

	public function get_step_data() {

		$return = array();

		foreach ( $this->steps as $step ) {

			$tmp = array(
				'id' 				=> $step->get_id(),
				'title' 			=> $step->get_title(),
				'number' 			=> $step->get_number(),
				'selector'			=> $step->get_selector(),
				'submit_html'		=> $step->get_template( 'submit' ),
				'wrapper_classes'	=> $step->get_wrapper_classes(),
				'hide'				=> false,
			);

			array_push( $return, $tmp );

		}

		return $return;

	}

	public function admin_hooks() {
		add_filter( 'woocommerce_gzd_settings_sections', array( $this, 'register_section' ), 4 );
		add_filter( 'woocommerce_gzd_get_settings_checkout', array( $this, 'get_settings' ) );
	}

	public function print_steps() {

		// On first visit: Set to first checkout step
		WC()->session->set( 'checkout_step', 'address' );

		wc_get_template( 'checkout/multistep/steps.php', array( 'multistep' => $this ) );
	}

	public function register_section( $sections ) {
		$sections[ 'checkout' ] = _x( 'Multistep Checkout', 'multistep', 'woocommerce-germanized-pro' );
		return $sections;
	}

	protected function get_layout_style_options() {
		return array(
			'plain'      => _x( 'Plain', 'multistep', 'woocommerce-germanized-pro' ),
			'navigation' => _x( 'Navigation', 'multistep', 'woocommerce-germanized-pro' ),
		);
	}

	public function get_settings() {
		
		$settings = array(

			array( 'title' => '', 'type' => 'title', 'id' => 'checkout_general_options' ),

			array(
				'title' 	=> _x( 'Enable', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Enable Multistep Checkout.', 'multistep', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_checkout_enable',
				'type' 		=> 'gzd_toggle',
				'default'	=> 'no',
			),

			array(
				'title' 	=> _x( 'Style', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a layout style.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_layout_style',
				'type' 		=> 'select',
				'options'   => $this->get_layout_style_options(),
				'default'	=> 'plain',
			),

			array(
				'title' 	=> _x( 'Address step title', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a title for the first step (addresses).', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_step_title_address',
				'type' 		=> 'text',
				'default'	=> _x( 'Personal Data', 'multistep', 'woocommerce-germanized-pro' ),
			),

			array(
				'title' 	=> _x( 'Payment step title', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a title for the second step (payment).', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_step_title_payment',
				'type' 		=> 'text',
				'default'	=> _x( 'Payment', 'multistep', 'woocommerce-germanized-pro' ),
			),

			array(
				'title' 	=> _x( 'Verify step title', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a title for the second step (order verify).', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_step_title_order',
				'type' 		=> 'text',
				'default'	=> _x( 'Order', 'multistep', 'woocommerce-germanized-pro' ),
			),

			array(
				'title' 	=> __( 'First step', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Insert a privacy policy notice right before the first step submit button.', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_checkout_privacy_policy_first_step',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> __( 'Use Checkbox', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Insert a checkbox to validate the privacy policy acceptance.', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_checkout_privacy_policy_checkbox',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_privacy_policy_first_step' => '',
				),
			),

			array(
				'title' 	=> __( 'Privacy Policy Text', 'woocommerce-germanized-pro' ),
				'desc' 		=> __( 'Choose a Plain Text which will be shown right above first step submit button. Use {data_security_link}{/data_security_link} as placeholder for the link to the privacy policy page.', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'default'   =>  __( 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our {data_security_link}Privacy Policy{/data_security_link}.', 'woocommerce-germanized-pro' ),
				'css' 		=> 'width:100%; height: 65px;',
				'id' 		=> 'woocommerce_gzdp_checkout_privacy_policy_text',
				'type' 		=> 'textarea',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_privacy_policy_first_step' => '',
				),
			),

			array(
				'title' 	=> _x( 'Payment Validation', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Enable field validation for payment step.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> _x( 'This option will enable field validation before continuing to step 3. Some Payment Plugins may require disabling this option.', 'multistep', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_checkout_payment_validation',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Force Template override', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Force to override your theme\'s form-checkout.php.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> sprintf( _x( 'Enable this mode if you are facing serious layout issues within checkout. To enable a custom multistep template add a template to your theme folder [%s]', 'multistep', 'woocommerce-germanized-pro' ), 'woocommerce-germanized-pro/checkout/multistep/form-checkout.php' ),
				'id' 		=> 'woocommerce_gzdp_checkout_force_template_override',
				'default'	=> 'no',
				'type' 		=> 'gzd_toggle',
			),

			array(
				'title' 	=> _x( 'Data review', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Enable data review within the last step.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> _x( 'This option will add billing + shipping address as well as payment method to the last step right before order table to let customers review their data.', 'multistep', 'woocommerce-germanized-pro' ),
				'id' 		=> 'woocommerce_gzdp_checkout_verify_data_output',
				'default'	=> 'yes',
				'type' 		=> 'gzd_toggle',
			),

			array( 'type' => 'sectionend', 'id' => 'checkout_general_options' ),

			array( 'title' => _x( 'Colors', 'multistep', 'woocommerce-germanized-pro' ), 'type' => 'title', 'id' => 'checkout_color_options' ),

			array(
				'title' 	=> _x( 'Background Color', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a background color for the step panel.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_bg',
				'type' 		=> 'color',
				'default'	=> '#f9f9f9',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_layout_style' => 'navigation',
				),
			),

			array(
				'title' 	=> _x( 'Border Color', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a border color for the step panel.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_border_color',
				'type' 		=> 'color',
				'default'	=> '#d4d4d4',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_layout_style' => 'navigation',
				),
			),

			array(
				'title' 	=> _x( 'Font Color', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a font color for the step panel.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_font_color',
				'type' 		=> 'color',
				'default'	=> '#468847',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_layout_style' => 'navigation',
				),
			),

			array(
				'title' 	=> _x( 'Active Background Color', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a background color for the current step.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_active_bg',
				'type' 		=> 'color',
				'default'	=> '#d9edf7',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_layout_style' => 'navigation',
				),
			),

			array(
				'title' 	=> _x( 'Active Font Color', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a font color for the current step.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_active_font_color',
				'type' 		=> 'color',
				'default'	=> '#3a87ad',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_layout_style' => 'navigation',
				),
			),

			array(
				'title' 	=> _x( 'Disabled Background Color', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a background color for a disabled step.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_disabled_bg',
				'type' 		=> 'color',
				'default'	=> '#ededed',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_layout_style' => 'navigation',
				),
			),

			array(
				'title' 	=> _x( 'Disabled Font Color', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a font color for a disabled step.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_disabled_font_color',
				'type' 		=> 'color',
				'default'	=> '#999999',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_layout_style' => 'navigation',
				),
			),

			array(
				'title' 	=> _x( 'Primary color', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a primary color.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_plain_primary_color',
				'type' 		=> 'color',
				'default'	=> '#3a87ad',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_layout_style' => 'plain',
				),
			),

			array(
				'title' 	=> _x( 'Secondary color', 'multistep', 'woocommerce-germanized-pro' ),
				'desc' 		=> _x( 'Choose a secondary color.', 'multistep', 'woocommerce-germanized-pro' ),
				'desc_tip'	=> true,
				'id' 		=> 'woocommerce_gzdp_checkout_plain_secondary_color',
				'type' 		=> 'color',
				'default'	=> '#d4d4d4',
				'custom_attributes' => array(
					'data-show_if_woocommerce_gzdp_checkout_layout_style' => 'plain',
				),
			),

			array( 'type' => 'sectionend', 'id' => 'checkout_color_options' ),

		);

		return $settings;

	}

	public function get_address_data( $type ) {

        $fields = apply_filters( 'woocommerce_gzdp_checkout_formatted_' . $type . '_address_fields', array(
            'first_name',
            'last_name',
            'company',
            'parcelshop_post_number',
            'address_1',
            'address_2',
            'city',
            'state',
            'postcode',
            'country',
	        'vat_id',
        ), $this );

        $address = array();
        $data = $this->get_posted_data();

		foreach( $fields as $field ) {
			$address[ $field ] = ( isset( $data[ $type . '_' . $field ] ) ? wc_clean( $data[ $type . '_' . $field ] ) : '' );
		}

        return $address;
    }

	public function get_formatted_billing_address() {

		// Formatted Addresses
		$address = apply_filters( 'woocommerce_gzdp_checkout_billing_address_data', $this->get_address_data( 'billing' ), $this );

		return apply_filters( 'woocommerce_gzdp_checkout_formatted_billing_address', WC()->countries->get_formatted_address( $address ), $address );
	}

	public function supports_shipping_address() {
		if ( $cart = WC()->cart ) {
			if ( $cart->needs_shipping() || $cart->needs_shipping_address() ) {
				return true;
			}
		}

		return false;
	}

	public function get_formatted_shipping_address() {

		$data = $this->get_posted_data();
		
		if ( ! isset( $data[ 'ship_to_different_address' ] ) || ! $data[ 'ship_to_different_address' ] ) {
			return false;
		}

		// Formatted Addresses
		$address = apply_filters( 'woocommerce_gzdp_checkout_shipping_address_data', $this->get_address_data( 'shipping' ), $this );

		return apply_filters( 'woocommerce_gzdp_checkout_formatted_shipping_address', WC()->countries->get_formatted_address( $address ), $address );
	}

}

return WC_GZDP_Multistep_Checkout::instance();