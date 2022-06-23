<?php
/**
 * Responsive Lightbox public functions
 *
 * Functions available for users and developers. May not be replaced.
 *
 * @since 2.0
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Display gallery using shortcode.
 *
 * @param array $args Shortcode arguments
 * @return void
 */
function rl_gallery( $args = array() ) {
	$defaults = array(
		'id' => 0
	);

	// merge defaults with arguments
	$args = array_merge( $defaults, $args );

	// parse ID
	$args['id'] = (int) $args['id'];

	// is it gallery?
	if ( get_post_type( $args['id'] ) === 'rl_gallery' )
		echo do_shortcode( '[rl_gallery id="' . $args['id'] . '"]' );
	else
		echo '[rl_gallery id="' . $args['id'] . '"]';
}

/**
 * Get gallery shortcode images - wrapper.
 *
 * @param array $args Gallery arguments
 * @return array Gallery images
 */
function rl_get_gallery_shortcode_images( $args ) {
	return Responsive_Lightbox()->frontend->get_gallery_shortcode_images( $args );
}

/**
 * Get gallery fields - wrapper.
 *
 * @param string $type Gallery type
 * @return array Gallery fields
 */
function rl_get_gallery_fields( $type ) {
	return Responsive_Lightbox()->frontend->get_gallery_fields( $type );
}

/**
 * Get gallery fields combined with shortcode attributes - wrapper.
 *
 * @param array $fields Gallery fields
 * @param array $shortcode_atts Gallery shortcode attributes
 * @param bool $gallery Whether is it rl_gallery shortcode
 * @return array All combined field attributes
 */
function rl_get_gallery_fields_atts( $fields, $shortcode_atts, $gallery = true ) {
	return Responsive_Lightbox()->frontend->get_gallery_fields_atts( $fields, $shortcode_atts, $gallery );
}

/**
 * Get gallery images - wrapper.
 *
 * @param int $gallery_id Gallery ID
 * @param array $args Gellery args
 * @return array Gallery images
 */
function rl_get_gallery_images( $gallery_id, $args ) {
	if ( did_action( 'init' ) )
		return Responsive_Lightbox()->galleries->get_gallery_images( $gallery_id, $args );
	else
		return [];
}

/**
 * Add lightbox to images, galleries and videos.
 *
 * @param string $content HTML content
 * @return string
 */
function rl_add_lightbox( $content ) {
	return Responsive_Lightbox()->frontend->add_lightbox( $content );
}

/**
 * Get attachment id by url.
 * 
 * @param string $url
 * @return int
 */
function rl_get_attachment_id_by_url( $url ) {
	return Responsive_Lightbox()->frontend->get_attachment_id_by_url( $url );
}

/**
 * Get image size by url.
 *
 * @param string $url Image url
 * @return string
 */
function rl_get_image_size_by_url( $url ) {
	return Responsive_Lightbox()->frontend->get_image_size_by_url( $url );
}

/**
 * Get current lightbox script.
 *
 * @return string
 */
function rl_get_lightbox_script() {
	return Responsive_Lightbox()->get_lightbox_script();
}

/**
 * Set current lightbox script.
 *
 * @return bool
 */
function rl_set_lightbox_script( $script ) {
	return Responsive_Lightbox()->set_lightbox_script( $script );
}

/**
 * Check whether lightbox supports specified type.
 *
 * @param string|array $type Lightbox support type(s), leave empty to get all supported features
 * @param string $compare_mode
 * @return bool|array
 */
function rl_current_lightbox_supports( $type = '', $compare_mode = 'AND' ) {
	// get main instance
	$rl = Responsive_Lightbox();

	// get current script
	$script = $rl->get_lightbox_script();

	// get scripts
	$scripts = $rl->settings->scripts;

	// valid script?
	if ( array_key_exists( $script, $scripts ) && array_key_exists( 'supports', $scripts[$script] ) ) {
		if ( ! empty( $type ) ) {
			// multitype?
			if ( is_array( $type ) ) {
				// filter types
				$type = array_filter( $type );

				if ( empty( $type ) )
					return false;

				$supports = ( $compare_mode === 'AND' );

				foreach ( $type as $_type ) {
					// single type required
					if ( $compare_mode === 'OR' ) {
						if ( in_array( $_type, $scripts[$script]['supports'], true ) )
							return true;
					// all types required
					} else {
						if ( ! in_array( $_type, $scripts[$script]['supports'], true ) )
							return false;
					}
				}

				return $supports;
			// single type
			} else
				return in_array( $type, $scripts[$script]['supports'], true );
		// return all supported features
		} else
			return $scripts[$script]['supports'];
	}

	return false;
}