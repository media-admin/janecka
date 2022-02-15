<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

try {
	spl_autoload_register( 'cleverreach_wp_namespace_autoload' );
} catch ( Exception $e ) {
	wp_die( esc_html( $e->getMessage() ) );
}

/**
 * Dynamically loads the class attempting to be instantiated elsewhere in the
 * plugin by looking at the $class_name parameter being passed as an argument.
 *
 * @param string $class_name Name of the class.
 */
function cleverreach_wp_namespace_autoload( $class_name ) {
	if ( false === strpos( $class_name, 'CleverReach\\WordPress\\' ) ) {
		return;
	}

	// Split the class name into an array to read the namespace and class.
	$path      = explode( '\\', $class_name );
	$namespace = '';
	$file_name = '';

	for ( $i = count( $path ) - 1; $i > 0; $i -- ) {
		$current = $path[ $i ];
		$current = str_ireplace( '_', '-', $current );

		if ( count( $path ) - 1 === $i ) {
			$current   = strtolower( $current );
			$file_name = "class-$current.php";
		} else {
			if ( 'WordPress' === $current ) {
				$current = 'cleverreach-wp';
			}

			$namespace = '/' . $current . $namespace;
		}
	}

	$file_path  = trailingslashit( dirname( dirname( __DIR__ ) ) . $namespace );
	$file_path .= $file_name;

	// If the file exists in the specified path, then include it.
	if ( file_exists( $file_path ) ) {
		include_once $file_path;
	}
}
