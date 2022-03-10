<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Responsive Lightbox Remote Library Wikimedia class.
 *
 * Library: https://commons.wikimedia.org
 * API: https://commons.wikimedia.org/w/api.php?action=help&modules=query%2Ballimages
 *
 * @class Responsive_Lightbox_Remote_Library_Wikimedia
 */
class Responsive_Lightbox_Remote_Library_Wikimedia extends Responsive_Lightbox_Remote_Library_API {

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		// provider slug
		$this->slug = 'wikimedia';

		// provider name
		$this->name = __( 'Wikimedia', 'responsive-lightbox' );

		// default values
		$this->defaults = [
			'active'	=> false
		];

		// setting fields
		$this->fields = [
			'title'		=> $this->name,
			'section'	=> 'responsive_lightbox_remote_library_providers',
			'type'		=> 'custom',
			'callback'	=> [ $this, 'render_field' ]
		];

		// response data
		$this->response_data_args = [
			'continue'
		];

		// add provider
		parent::add_provider( $this );

		// handle last page
		add_filter( 'rl_remote_library_query_last_page', [ $this, 'handle_last_page' ], 10, 3 );
	}

	/**
	 * Render field.
	 *
	 * @return void
	 */
	public function render_field() {
		echo '
		<p><label><input id="rl_wikimedia_active" type="checkbox" name="responsive_lightbox_remote_library[wikimedia][active]" value="1" ' . checked( $this->rl->options['remote_library']['wikimedia']['active'], true, false ) . ' />' . __( 'Enable Wikimedia.', 'responsive-lightbox' ) . '</label></p>';
	}

	/**
	 * Validate settings.
	 *
	 * @param array $input POST data
	 * @return array
	 */
	public function validate_settings( $input ) {
		if ( ! isset( $_POST['responsive_lightbox_remote_library'] ) )
			$input['wikimedia'] = $this->rl->defaults['remote_library']['wikimedia'];
		else {
			// active
			$input['wikimedia']['active'] = isset( $_POST['responsive_lightbox_remote_library']['wikimedia']['active'] );
		}

		return $input;
	}

	/**
	 * Prepare data to run remote query.
	 *
	 * @param string $search_phrase Search phrase
	 * @param array $args Provider arguments
	 * @return void
	 */
	public function prepare_query( $search_phrase, $args = [] ) {
		// check page parameter
		if ( isset( $args['preview_page'] ) )
			$args['preview_page'] = (int) $args['preview_page'];
		else
			$args['preview_page'] = 1;

		if ( $args['preview_page'] < 1 )
			$args['preview_page'] = 1;

		// check limit
		if ( isset( $args['limit'] ) && ( $limit = (int) $args['limit'] ) > 0 )
			$args['preview_per_page'] = $limit;
		else {
			// check per page parameter
			if ( isset( $args['preview_per_page'] ) )
				$args['preview_per_page'] = (int) $args['preview_per_page'];
			else
				$args['preview_per_page'] = 20;

			if ( $args['preview_per_page'] < 5 || $args['preview_per_page'] > 200 )
				$args['preview_per_page'] = 20;
		}

		// set query arguments
		$this->query_args = $args;

		$query_args = [
			'action'	=> 'query',
			'format'	=> 'json',
			'list'		=> 'allimages',
			'aiprefix'	=> urlencode( $search_phrase ),
			'ailimit'	=> $args['preview_per_page'],
			'aisort'	=> 'name',
			'aidir'		=> 'ascending',
			'aiprop'	=> 'url|size|extmetadata|dimensions'
		];

		if ( isset( $args['response_data']['wikimedia']['continue']['aicontinue'] ) )
			$query_args['aicontinue'] = $args['response_data']['wikimedia']['continue']['aicontinue'];

		// set query string
		$this->query = add_query_arg( $query_args, 'https://commons.wikimedia.org/w/api.php' );

		// set query remote arguments
		$this->query_remote_args = [
			'timeout'	=> 30,
			'headers'	=> [
				'User-Agent' => __( 'Responsive Lightbox', 'responsive-lightbox' ) . ' ' . $this->rl->defaults['version']
			]
		];
	}

	/**
	 * Get images from media provider.
	 *
	 * @param mixed $response Remote response
	 * @param array $args Query arguments
	 * @return array|WP_Error
	 */
	public function get_query_results( $response, $args = [] ) {
		$results = [];
		$error = new WP_Error( 'rl_remote_library_wikimedia_get_query_results', __( 'Parsing request error', 'responsive-lightbox' ) );

		// retrieve body
		$response_body = wp_remote_retrieve_body( $response );

		// any data?
		if ( $response_body !== '' ) {
			$response_json = json_decode( $response_body, true );

			// invalid data?
			if ( $response_json === null || ( isset( $response_json['success'] ) && $response_json['success'] === false ) )
				$results = $error;
			else {
				// set response data
				$this->response_data = $response_json;

				// get results
				$results = isset( $response_json['query'] ) && is_array( $response_json['query'] ) && isset( $response_json['query']['allimages'] ) && is_array( $response_json['query']['allimages'] ) ? $response_json['query']['allimages'] : [];

				// sanitize images
				$results = $this->sanitize_results( $results );
			}
		} else
			$results = $error;

		return $results;
	}

	/**
	 * Handle query last page.
	 *
	 * @param bool $last Whether is it last page
	 * @param array $result Query result
	 * @param array $args Query arguments
	 * @return bool
	 */
	public function handle_last_page( $last, $result, $args ) {
		if ( $args['media_provider'] === 'wikimedia' && empty( $result['data']['wikimedia']['continue'] ) )
			return true;

		return $last;
	}

	/**
	 * Sanitize single result.
	 *
	 * @param array $result Single result
	 * @return array|false
	 */
	public function sanitize_result( $result ) {
		// allow only JPG, PNG and GIF images
		if ( preg_match( '/\.(jpe?g|gif|png)$/i', $result['url'] ) !== 1 )
			return false;

		// get part of an URL
		$url = explode( 'https://upload.wikimedia.org/wikipedia/commons/', $result['url'] );

		// set dimensions
		$width = (int) $result['width'];
		$height = (int) $result['height'];

		// calculate ratio
		$ratio = $width / $height;

		// try to get thumbnail url and dimensions
		if ( ! empty( $url[1] ) ) {
			$thumbnail_url = $result['url'];
			$thumbnail_width = 0;
			$thumbnail_height = 0;

			$name = explode( '/', $url[1] );

			if ( ! empty( $name[2] ) ) {
				// standard smallest size
				$thumbnail_width = 240;

				// calculate new height based on original ratio
				$thumbnail_height = (int) floor( $thumbnail_width / $ratio );

				// use larger size if height is less than 150 pixels
				if ( $thumbnail_height < 150 ) {
					$thumbnail_width = 480;

					// calculate new height based on original ratio
					$thumbnail_height = (int) floor( $thumbnail_width / $ratio );
				}

				$thumbnail_url = 'https://upload.wikimedia.org/wikipedia/commons/thumb/' . $url[1] . '/' . $thumbnail_width . 'px-' . $name[2];
			}
		} else {
			$thumbnail_url = $result['url'];
			$thumbnail_width = $width;
			$thumbnail_height = $height;
		}

		$imagedata = [
			'id'					=> 0,
			'link'					=> '',
			'source'				=> $result['descriptionshorturl'],
			'title'					=> $result['title'],
			'caption'				=> $this->get_attribution( 'Wikimedia', $result['descriptionshorturl'] ),
			'description'			=> isset( $result['extmetadata']['ImageDescription']['value'] ) ? $result['extmetadata']['ImageDescription']['value'] : '',
			'alt'					=> isset( $result['extmetadata']['Categories']['value'] ) ? str_replace( '|', ', ', $result['extmetadata']['Categories']['value'] ) : '',
			'url'					=> $result['url'],
			'width'					=> $width,
			'height'				=> $height,
			'orientation'			=> $height > $width ? 'portrait' : 'landscape',
			'thumbnail_url'			=> $thumbnail_url,
			'thumbnail_width'		=> $thumbnail_width,
			'thumbnail_height'		=> $thumbnail_height,
			'thumbnail_orientation'	=> $thumbnail_height > $thumbnail_width ? 'portrait' : 'landscape',
			'media_provider'		=> 'wikimedia',
			'filename'				=> $result['name'],
			'dimensions'			=> $width . ' x ' . $height,
		];

		// create thumbnail link
		$imagedata['thumbnail_link'] = $this->rl->galleries->get_gallery_image_link( $imagedata, 'thumbnail' );

		return $imagedata;
	}
}

new Responsive_Lightbox_Remote_Library_Wikimedia();