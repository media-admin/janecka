<?php
/**
 * Loads Germanized packages from the /packages directory. These are packages developed outside of core.
 *
 * @package Vendidero/Germanized
 */

namespace Vendidero\Germanized\Pro;

defined( 'ABSPATH' ) || exit;

/**
 * Packages class.
 *
 * @since 3.0.0
 */
class Packages {

	/**
	 * Static-only class.
	 */
	private function __construct() {}

	/**
	 * Array of package names and their main package classes.
	 *
	 * @var array Key is the package name/directory, value is the main package class which handles init.
	 */
	protected static $packages = [
		'storeabill'                 => '\\Vendidero\\StoreaBill\\Package',
		'storeabill-lexoffice'       => '\\Vendidero\\StoreaBill\\Lexoffice\\Package',
        'storeabill-sevdesk'         => '\\Vendidero\\StoreaBill\\sevDesk\\Package',
		'woocommerce-germanized-dpd' => '\\Vendidero\\Germanized\\DPD\\Package',
	];

	/**
	 * Init the package loader.
	 *
	 * @since 3.7.0
	 */
	public static function init() {
		add_action( 'plugins_loaded', array( __CLASS__, 'on_init' ) );
	}

	public static function get_packages() {
		return self::$packages;
	}

	/**
	 * Callback for WordPress init hook.
	 */
	public static function on_init() {
		/**
		 * Do not load packages in case the main version is not loadable.
		 */
	    if ( ! \WC_GZDP_Dependencies::instance()->is_loadable() ) {
	        return;
        }

		self::load_packages();
	}

	/**
	 * Checks a package exists by looking for it's directory.
	 *
	 * @param string $package Package name.
	 *
	 * @return boolean
	 */
	public static function package_exists( $package ) {
		return file_exists( dirname( __DIR__ ) . '/packages/' . $package );
	}

	/**
	 * Loads packages after plugins_loaded hook.
	 *
	 * Each package should include an init file which loads the package so it can be used by core.
	 */
	protected static function load_packages() {
		foreach ( self::$packages as $package_name => $package_class ) {
			if ( ! self::package_exists( $package_name ) ) {
				self::missing_package( $package_name );
				continue;
			}

			/**
			 * Prevent calling init twice in case feature plugin is installed
			 */
			if ( ! has_action( 'plugins_loaded', array( $package_class, 'init' ) ) ) {
				call_user_func( [ $package_class, 'init' ] );
			}
		}
	}

	/**
	 * If a package is missing, add an admin notice.
	 *
	 * @param string $package Package name.
	 */
	protected static function missing_package( $package ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log(  // phpcs:ignore
				sprintf(
				/* Translators: %s package name. */
					esc_html__( 'Missing the Germanized Pro %s package', 'woocommerce-germanized-pro' ),
					'<code>' . esc_html( $package ) . '</code>'
				) . ' - ' . esc_html__( 'Your installation of Germanized Pro is incomplete. If you installed Germanized from GitHub, please refer to this document to set up your development environment: https://github.com/vendidero/woocommerce-germanized/wiki/How-to-set-up-a-Germanized-development-environment', 'woocommerce-germanized-pro' )
			);
		}
		add_action(
			'admin_notices',
			function () use ( $package ) {
				?>
                <div class="notice notice-error">
                    <p>
                        <strong>
							<?php
							printf(
							/* Translators: %s package name. */
								esc_html__( 'Missing the Germanized Pro %s package', 'woocommerce-germanized-pro' ),
								'<code>' . esc_html( $package ) . '</code>'
							);
							?>
                        </strong>
                        <br>
						<?php
						printf(
						/* translators: 1: is a link to a support document. 2: closing link */
							esc_html__( 'Your installation of Germanized Pro is incomplete. If you installed Germanized from GitHub, %1$splease refer to this document%2$s to set up your development environment.', 'woocommerce-germanized-pro' ),
							'<a href="' . esc_url( 'https://github.com/woocommerce/woocommerce/wiki/How-to-set-up-WooCommerce-development-environment' ) . '" target="_blank" rel="noopener noreferrer">',
							'</a>'
						);
						?>
                    </p>
                </div>
				<?php
			}
		);
	}
}