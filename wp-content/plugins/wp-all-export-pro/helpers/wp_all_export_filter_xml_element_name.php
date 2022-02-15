<?php
/*
 * This helper is added now so that it can be used in the future throughout WPAE and the export add-ons.
 */

if ( ! function_exists( 'wp_all_export_filter_xml_element_name' ) ) {
	function wp_all_export_filter_xml_element_name( $fieldName, $ID ) {

		// XML 1.0 Fifth Edition allowed characters
		$startCharRegEx = '~^[^:A-Z_a-z\\xC0-\\xD6\\xD8-\\xF6\\xF8-\\x{2FF}\\x{370}-\\x{37D}\\x{37F}-\\x{1FFF}\\x{200C}-\\x{200D}\\x{2070}-\\x{218F}\\x{2C00}-\\x{2FEF}\\x{3001}-\\x{D7FF}\\x{F900}-\\x{FDCF}\\x{FDF0}-\\x{FFFD}\\x{10000}-\\x{EFFFF}]+~u';

		$charRegEx      = '~[^:A-Z_a-z\\xC0-\\xD6\\xD8-\\xF6\\xF8-\\x{2FF}\\x{370}-\\x{37D}\\x{37F}-\\x{1FFF}\\x{200C}-\\x{200D}\\x{2070}-\\x{218F}\\x{2C00}-\\x{2FEF}\\x{3001}-\\x{D7FF}\\x{F900}-\\x{FDCF}\\x{FDF0}-\\x{FFFD}\\x{10000}-\\x{EFFFF}.\\-0-9\\xB7\\x{0300}-\\x{036F}\\x{203F}-\\x{2040}]+~u';

		// Remove all invalid characters from element name
		$element_name = ( ! empty( $fieldName ) ) ? preg_replace( $charRegEx, '', preg_replace( $startCharRegEx, '', $fieldName ) ) : 'untitled_' . $ID;

		return empty( $element_name ) ? 'untitled_' . $ID : $element_name;
	}
}
