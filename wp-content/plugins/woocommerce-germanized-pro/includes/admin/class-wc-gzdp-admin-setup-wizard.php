<?php
/**
 * WC GZDP Setup Wizard Class
 *
 * @package  woocommerce-germanized-pro
 * @since    2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_GZDP_Admin_Setup_Wizard' ) ) :

	/**
	 * The Storefront NUX Admin class
	 */
	class WC_GZDP_Admin_Setup_Wizard {

		/**
		 * Current step
		 *
		 * @var string
		 */
		private $step = '';

		/**
		 * Steps for the setup wizard
		 *
		 * @var array
		 */
		private $steps = array();

		/**
		 * Setup class.
		 *
		 * @since 2.2.0
		 */
		public function __construct() {
		    if ( did_action( 'plugins_loaded' ) ) {
		        $this->load();
		    } else {
		        add_action( 'plugins_loaded', array( $this, 'load' ) );
		    }
		}

		public function load() {
		    if ( current_user_can( 'manage_options' ) ) {
				add_action( 'admin_menu', array( $this, 'admin_menus' ), 20 );
				add_action( 'admin_init', array( $this, 'initialize' ), 10 );
				add_action( 'admin_init', array( $this, 'setup_wizard' ), 20 );
				// Load after base has registered scripts
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 20 );
				add_action( 'admin_post_wc_gzdp_setup', array( $this, 'save' ) );
			}
		}

		protected function is_legacy_import_update() {
		    $is_import = get_option( '_wc_gzdp_needs_legacy_invoice_import' );

		    return $is_import;
		}

		public function initialize() {
			$default_steps = array(
				'activation'  => array(
					'name'    => __( 'Activation', 'woocommerce-germanized-pro' ),
					'view'    => 'activation.php',
					'handler' => array( $this, 'wc_gzdp_setup_activation_save' ),
					'order'   => 1,
					'errors'  => array(
						'helper_install' => sprintf( __( 'Sorry, we were not able to automatically install the Vendidero Helper Plugin. See <a href="%s" target="_blank">manual install instructions</a> for assitance.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/woocommerce-germanized-pro-installieren' ),
						'activation'     => sprintf( __( 'Sorry, Germanized could not be activated. Please see the <a href="%s" target="_blank">install instructions</a> for assistance.', 'woocommerce-germanized-pro' ), 'https://vendidero.de/dokument/woocommerce-germanized-pro-installieren' ),
					),
				),
				'invoice' 	  => array(
					'name'    => __( 'Invoices', 'woocommerce-germanized-pro' ),
					'view'    => $this->is_legacy_import_update() ? 'legacy-import.php' : 'invoice.php',
					'handler' => array( $this, 'wc_gzdp_setup_invoice_save' ),
					'order'   => 2,
					'errors'  => array(),
					'button_next' => $this->is_legacy_import_update() ? __( 'Start import', 'woocommerce-germanized-pro' ) : __( 'Enable Invoicing', 'woocommerce-germanized-pro' ),
				),
				'support' 	  => array(
					'name'    => __( 'Support', 'woocommerce-germanized-pro' ),
					'view'    => 'support.php',
					'handler' => array( $this, 'wc_gzdp_setup_support_save' ),
					'order'   => 3,
					'errors'  => array(),
				),
				'ready' 	           => array(
					'name'             => __( 'Ready', 'woocommerce-germanized-pro' ),
					'view'             => 'ready.php',
					'order'            => 4,
					'errors'  	       => array(),
					'button_next'      => __( 'Go to settings', 'woocommerce-germanized-pro' ),
					'button_next_link' => admin_url( 'admin.php?page=wc-settings&tab=germanized' ),
				),
			);

			if ( $this->is_legacy_import_update() ) {
			    unset( $default_steps['activation'] );
			    $order = 1;

			    foreach( $default_steps as $key => $step ) {
			        $default_steps[ $key ]['order'] = $order++;
			    }
			}

			$this->steps  = $default_steps;
			$this->step   = isset( $_REQUEST['step'] ) ? sanitize_key( $_REQUEST['step'] ) : current( array_keys( $this->steps ) ); // WPCS: CSRF ok, input var ok.

			// Check if a step has been skipped and maybe delete som tmp options
			if ( isset( $_GET['skip'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'wc-gzdp-setup-skip' ) ) {
				$skipped_step = sanitize_key( $_GET['skip'] );
 			}
		}

		/**
		 * Add admin menus/screens.
		 */
		public function admin_menus() {
			add_submenu_page( '', __( 'Setup', 'woocommerce-germanized-pro' ), __( 'Setup', 'woocommerce-germanized-pro' ), 'manage_options', 'wc-gzdp-setup', array( $this, 'none' ) );
		}

		/**
		 * Register/enqueue scripts and styles for the Setup Wizard.
		 *
		 * Hooked onto 'admin_enqueue_scripts'.
		 */
		public function enqueue_scripts() {
			if ( $this->is_setup_wizard() ) {
				wp_enqueue_style( 'wc-gzdp-admin-setup-wizard' );
			}
		}

		private function is_setup_wizard() {
			return ( isset( $_GET['page'] ) && 'wc-gzdp-setup' === $_GET['page'] );
		}

		public function get_error_message( $step = false ) {
			if ( isset( $_GET['error'] ) ) {
				$error_key 	  = sanitize_key( $_GET['error'] );
				$current_step = $this->get_step( $step );

				if ( isset( $current_step['errors'][ $error_key ] ) ) {
					return $current_step['errors'][ $error_key ];
				}
			}

			return false;
		}

		/**
		 * Show the setup wizard.
		 */
		public function setup_wizard() {
			if ( ! $this->is_setup_wizard() ) {
				return;
			}

			ob_start();
			$this->header();
			$this->steps();
			$this->content();
			$this->footer();
			exit;
		}

		public function get_step( $key = false ) {
			if ( ! $key ) {
				$key = $this->step;
			}

			return ( isset( $this->steps[ $key ] ) ? $this->steps[ $key ] : false );
		}

		public function get_step_url( $key ) {
			if ( ! $step = $this->get_step( $key ) ) {
				return false;
			}

			return admin_url( 'admin.php?page=wc-gzdp-setup&step='  . $key );
		}

		public function get_next_step() {
			$current = $this->get_step();
			$next    = $this->step;

			if ( $current['order'] < sizeof( $this->steps ) ) {
				$order_next = $current['order'] + 1;

				foreach( $this->steps as $step_key => $step ) {
					if ( $step['order'] === $order_next ) {
						$next = $step_key;
					}
				}
			}

			return $next;
		}

		protected function header() {
			set_current_screen();
			?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta name="viewport" content="width=device-width" />
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title><?php esc_html_e( 'Germanized Pro &rsaquo; Setup Wizard', 'woocommerce-germanized-pro' ); ?></title>
				<?php do_action( 'admin_enqueue_scripts' ); ?>
				<?php wp_print_scripts( 'wc-gzdp-admin-setup-wizard' ); ?>
				<?php do_action( 'admin_print_styles' ); ?>
				<?php do_action( 'admin_head' ); ?>
			</head>
			<body class="wc-gzdp-setup wp-core-ui wc-gzdp-setup-step-<?php echo esc_attr( $this->step ); ?>">
			    <div class="logo-wrapper"><div class="logo"></div></div>
			<?php
		}

		protected function steps() {
			$output_steps      = $this->steps;
			?>
			<ul class="step wc-gzdp-steps">
				<?php
				foreach ( $output_steps as $step_key => $step ) {
					?>
					<li class="step-item <?php echo $step_key === $this->step ? 'active' : ''; ?>">
						<a href="<?php echo $this->get_step_url( $step_key ) ?>"><?php echo esc_html( $step['name'] ); ?></a>
					</li>
					<?php
				}
				?>
			</ul>
			<?php
		}

		protected function content() {
			?>
			<form class="wc-gzdp-setup-form" method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
				<div class="wc-gzdp-setup-content">

				<?php if ( $error_message = $this->get_error_message() ) : ?>
					<div id="message" class="error inline">
						<p><?php echo $error_message; ?></p>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $this->steps[ $this->step ]['view'] ) ) {
					if ( file_exists( WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/views/setup/' . $this->steps[ $this->step ]['view'] ) ) {

						// Extract the variables to a local namespace
						extract( array(
							'steps'  => $this->steps,
							'step'   => $this->step,
							'wizard' => $this
						) );

						include( 'views/setup/' . $this->steps[ $this->step ]['view'] );
					}
				}

				echo '</div>';
		}

		protected function footer() {
			$current = $this->get_step( $this->step );
			?>
			<div class="wc-gzdp-setup-footer">
				<div class="wc-gzdp-setup-links">
					<input type="hidden" name="action" value="wc_gzdp_setup" />
					<input type="hidden" name="step" value="<?php echo esc_attr( $this->step ); ?>" />

					<?php wp_nonce_field( 'wc-gzdp-setup' ); ?>

					<?php if ( $current['order'] < sizeof( $this->steps ) ) : ?>
						<a class="wc-gzdp-setup-link wc-gzdp-setup-link-skip" href="<?php echo wp_nonce_url( add_query_arg( array( 'skip' => esc_attr( $this->step ) ), $this->get_step_url( $this->get_next_step() ) ), 'wc-gzdp-setup-skip' ); ?>"><?php esc_html_e( 'Skip Step', 'woocommerce-germanized-pro' ); ?></a>
					<?php endif; ?>

					<?php if ( isset( $current['button_next_link'] ) && ! empty( $current['button_next_link'] ) ) : ?>
						<a class="button button-primary wc-gzdp-setup-link" href="<?php echo esc_url( $current['button_next_link'] ); ?>"><?php echo isset( $current['button_next'] ) ? esc_attr( $current['button_next'] ) : esc_attr__( 'Continue', 'woocommerce-germanized-pro' ); ?></a>
					<?php else: ?>
						<button class="button button-primary wc-gzdp-setup-link" type="submit"><?php echo isset( $current['button_next'] ) ? esc_attr( $current['button_next'] ) : esc_attr__( 'Continue', 'woocommerce-germanized-pro' ); ?></button>
					<?php endif; ?>

				</div>

				<div class="escape">
					<a href="<?php echo admin_url(); ?>"><?php _e( 'Return to WP Admin', 'woocommerce-germanized-pro' ); ?></a>
				</div>
			</div>
			</form>
			</body>
			</html>
			<?php
		}

		/**
		 * Get slug from path and associate it with the path.
		 *
		 * @param array  $plugins Associative array of plugin files to paths.
		 * @param string $key Plugin relative path. Example: woocommerce/woocommerce.php.
		 */
		private function associate_plugin_file( $plugins, $key ) {
			$path                 = explode( '/', $key );
			$filename             = end( $path );
			$plugins[ $filename ] = $key;
			return $plugins;
		}

		private function install_plugin( $plugin_to_install_id, $plugin_to_install, $network_wide = true ) {

			if ( ! empty( $plugin_to_install['repo-slug'] ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
				require_once ABSPATH . 'wp-admin/includes/plugin.php';

				WP_Filesystem();

				$skin              = new Automatic_Upgrader_Skin();
				$upgrader          = new WP_Upgrader( $skin );
				$installed_plugins = array_reduce( array_keys( get_plugins() ), array( $this, 'associate_plugin_file' ), array() );
				$plugin_slug       = $plugin_to_install['repo-slug'];
				$plugin_file       = isset( $plugin_to_install['file'] ) ? $plugin_to_install['file'] : $plugin_slug . '.php';
				$installed         = false;
				$activate          = false;

				// See if the plugin is installed already.
				if ( isset( $installed_plugins[ $plugin_file ] ) ) {
					$installed = true;
					$activate  = ! is_plugin_active( $installed_plugins[ $plugin_file ] );
				}

				// Install this thing!
				if ( ! $installed ) {
					// Suppress feedback.
					ob_start();

					try {
						$plugin_information = plugins_api(
							'plugin_information',
							array(
								'slug'   => $plugin_slug,
								'fields' => array(
									'short_description' => false,
									'sections'          => false,
									'requires'          => false,
									'rating'            => false,
									'ratings'           => false,
									'downloaded'        => false,
									'last_updated'      => false,
									'added'             => false,
									'tags'              => false,
									'homepage'          => false,
									'donate_link'       => false,
									'author_profile'    => false,
									'author'            => false,
								),
							)
						);

						if ( is_wp_error( $plugin_information ) ) {
							throw new Exception( $plugin_information->get_error_message() );
						}

						$package  = $plugin_information->download_link;
						$download = $upgrader->download_package( $package );

						if ( is_wp_error( $download ) ) {
							throw new Exception( $download->get_error_message() );
						}

						$working_dir = $upgrader->unpack_package( $download, true );

						if ( is_wp_error( $working_dir ) ) {
							throw new Exception( $working_dir->get_error_message() );
						}

						$result = $upgrader->install_package(
							array(
								'source'                      => $working_dir,
								'destination'                 => WP_PLUGIN_DIR,
								'clear_destination'           => false,
								'abort_if_destination_exists' => false,
								'clear_working'               => true,
								'hook_extra'                  => array(
									'type'   => 'plugin',
									'action' => 'install',
								),
							)
						);

						if ( is_wp_error( $result ) ) {
							throw new Exception( $result->get_error_message() );
						}

						$activate = true;

					} catch ( Exception $e ) {
						return false;
					}

					// Discard feedback.
					ob_end_clean();
				}

				wp_clean_plugins_cache();

				// Activate this thing.
				if ( $activate ) {
					try {
						$result = activate_plugin( $installed ? $installed_plugins[ $plugin_file ] : $plugin_slug . '/' . $plugin_file, '', $network_wide );

						if ( is_wp_error( $result ) ) {
							throw new Exception( $result->get_error_message() );
						}
					} catch ( Exception $e ) {
						return false;
					}
				}
			}

			return true;
		}

		public function save() {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wc-gzdp-setup' ) ) {
				wp_die();
			} elseif ( ! current_user_can( 'manage_options' ) ) {
				wp_die();
			}

			$current_step = isset( $_POST['step'] ) ? sanitize_key( $_POST['step'] ) : $this->step;

			if ( ! $step = $this->get_step( $current_step ) ) {
				wp_die();
			}

			call_user_func( $step['handler'] );
		}

		public function wc_gzdp_setup_activation_save() {
			$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( $_POST['license_key'] ) : '';
			$error 	     = false;
			$redirect 	 = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );

			if ( isset( $_POST['license_key'] ) && empty( $license_key ) ) {
				$error = 'activation';
			}

			if ( ! vendidero_helper_activated() ) {
				// Install vendidero helper
				$result = $this->install_plugin(
					'vendidero-helper',
					array(
						'name'      => __( 'Vendidero Helper', 'woocommerce-germanized-pro' ),
						'repo-slug' => 'vendidero-helper',
					)
				);

				if ( ! $result ) {
					$error = 'helper_install';
				}
			}

			$gzdp = WC_germanized_pro();

			if ( function_exists( 'VD' ) && ! $gzdp->is_registered() ) {
				$success = false;

				add_filter( 'vendidero_updateable_products', array( $gzdp, 'register_updates' ) );
				VD()->load();

				if ( $plugin = $gzdp->get_vd_product() ) {
					if ( VD()->api->register( $plugin, $license_key ) ) {
						$success = true;
					}
				}

				if ( ! $success ) {
					$error = 'activation';
				}
			}

			if ( $error ) {
				$redirect = add_query_arg( array( 'error' => $error ), $current_url );
			} else {
				$redirect = add_query_arg( array( 'success' => true ), $redirect );
			}

			wp_safe_redirect( $redirect );
			exit();
		}

		public function wc_gzdp_setup_invoice_save() {
			$redirect 	 = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );

			if ( $this->is_legacy_import_update() ) {
			    $import_after = isset( $_POST['import_after'] ) ? absint( $_POST['import_after'] ) : date_i18n( 'Y' );
		        WC_GZDP_Install::start_legacy_invoice_import( $import_after );
			} else {
			    update_option( 'woocommerce_gzdp_invoice_enable', 'yes' );
			}

			wp_safe_redirect( $redirect );
			exit();
		}

		public function wc_gzdp_setup_support_save() {
			$redirect 	 = $this->get_step_url( $this->get_next_step() );
			$current_url = $this->get_step_url( $this->step );

			wp_safe_redirect( $redirect );
			exit();
		}
	}

endif;

return new WC_GZDP_Admin_Setup_Wizard();
