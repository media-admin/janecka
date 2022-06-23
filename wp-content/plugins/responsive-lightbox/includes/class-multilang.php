<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

new Responsive_Lightbox_Multilang();

/**
 * Responsive Lightbox Multilang class.
 *
 * @class Responsive_Lightbox_Multilang
 */
class Responsive_Lightbox_Multilang {
	public $multilang = false;
	public $languages = array();
	public $default_lang = '';
	public $current_lang = '';
	public $active_plugin = '';

	/**
	 * Class constructor.
	 *
	 * @global object $sitepress
	 *
	 * @return void
	 */
	public function __construct() {
		// set instance
		Responsive_Lightbox()->multilang = $this;

		// check if WPML or Polylang is active
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

		// Polylang support
		if ( ( is_plugin_active( 'polylang/polylang.php' ) || is_plugin_active( 'polylang-pro/polylang.php' ) ) && function_exists( 'PLL' ) ) {
			$this->multilang = true;
			$this->active_plugin = 'polylang';

			// get registered languages
			$registered_languages = PLL()->model->get_languages_list();

			if ( ! empty( $registered_languages ) ) {
				foreach ( $registered_languages as $language )
					$this->languages[$language->slug] = $language->name;
			}

			// get default language
			$this->default_lang = pll_default_language();

			// filters
			add_filter( 'rl_count_attachments', array( $this, 'count_attachments' ), 9 );
		// WPML support
		} elseif ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) && class_exists( 'SitePress' ) ) {
			$this->multilang = true;
			$this->active_plugin = 'wpml';

			global $sitepress;

			// get registered languages
			$registered_languages = icl_get_languages();

			if ( ! empty( $registered_languages ) ) {
				foreach ( $registered_languages as $language )
					$this->languages[$language['code']] = $language['display_name'];
			}

			// get default language
			$this->default_lang = $sitepress->get_default_language();

			// actions
			add_action( 'admin_init', array( $this, 'hide_thumbnail' ) );
		}

		// multilang?
		if ( $this->multilang ) {
			// ations
			add_action( 'admin_init', array( $this, 'media_url_redirect' ) );

			// filters
			add_filter( 'setup_theme', array( $this, 'get_current_admin_language' ), 11 );
			add_filter( 'rl_root_folder_query_args', array( $this, 'root_folder_query_args' ) );
			add_filter( 'rl_gallery_query_args', array( $this, 'gallery_featured_query_args' ) );
			add_filter( 'rl_folders_query_args', array( $this, 'gallery_folders_query_args' ) );
			add_filter( 'rl_get_gallery_images_attachments', array( $this, 'update_gallery_images_attachments' ) );
			add_filter( 'rl_folders_media_folder_url', array( $this, 'media_folder_url' ) );
		}
	}

	/**
	 * Avoid duplicating hidden internal thumbnail.
	 *
	 * @return void
	 */
	public function hide_thumbnail() {
		// get thumbnail
		$thumbnail_id = Responsive_Lightbox()->galleries->maybe_generate_thumbnail();

		add_post_meta( $thumbnail_id, 'wpml_media_processed', 1, true );
	}

	/**
	 * Get current admin language.
	 *
	 * @return void
	 */
	public function get_current_admin_language() {
		if ( $this->active_plugin === 'polylang' )
			$this->current_lang = (string) pll_current_language( 'slug' );
		else
			$this->current_lang = ICL_LANGUAGE_CODE === 'all' ? '' : ICL_LANGUAGE_CODE;
	}

	/**
	 * Update gallery attachments.
	 *
	 * @param array $attachments Attachment IDs
	 * @return array
	 */
	public function update_gallery_images_attachments( $attachments ) {
		$new_attachments = array();

		foreach ( $attachments as $attachment_id ) {
			if ( $this->active_plugin === 'polylang' )
				$new_attachments[] = pll_get_post( $attachment_id, $this->current_lang );
			else
				$new_attachments[] = (int) apply_filters( 'wpml_object_id', $attachment_id, 'attachment', true, $this->current_lang );
		}

		return $attachments;
	}

	/**
	 * Root folder WP Query arguments.
	 *
	 * @param array args
	 * @return void
	 */
	public function root_folder_query_args( $args ) {
		$args['lang'] = $this->current_lang;

		return $args;
	}

	/**
	 * Get the number of attachments per language.
	 * Based on count_posts function from Polylang plugin (/include/model.php)
	 *
	 * @global object $wpdb
	 *
	 * @param int number
	 * @return int
	 */
	public function count_attachments( $number ) {
		// active language?
		if ( $this->current_lang !== '' ) {
			// remove internal WP counter to avoid unwanted query
			remove_filter( 'rl_count_attachments', array( Responsive_Lightbox()->folders, 'count_attachments' ), 10 );
		// if not let internal WP counter do the job
		} else
			return $number;

		// get taxonomies
		$taxonomies = PLL()->model->get_filtered_taxonomies_query_vars();

		// prepare defaults
		$defaults = array(
			'author'		=> '',
			'author_name'	=> '',
			'monthnum'		=> '',
			'day'			=> '',
			'year'			=> '',
			'm'				=> ''
		);

		// add additional taxonomies
		foreach ( $taxonomies as $tax ) {
			$defaults[$tax] = '';
		}

		// prepare data
		$args = array_intersect_key( array_merge( $defaults, wp_unslash( $_REQUEST ) ), $defaults );

		global $wpdb;

		$select = "SELECT pll_tr.term_taxonomy_id, COUNT( * ) AS count FROM " . $wpdb->posts;
		$join = PLL()->model->post->join_clause();
		$where = " WHERE post_status = 'inherit'";
		$where .= " AND " . $wpdb->posts . ".post_type = 'attachment'";
		$where .= PLL()->model->post->where_clause( $this->current_lang );
		$groupby = ' GROUP BY pll_tr.term_taxonomy_id';

		if ( ! empty( $args['m'] ) ) {
			$args['m'] = '' . preg_replace( '|[^0-9]|', '', $args['m'] );
			$where .= $wpdb->prepare( " AND YEAR( " . $wpdb->posts . ".post_date ) = %d", substr( $args['m'], 0, 4 ) );

			if ( strlen( $args['m'] ) > 5 )
				$where .= $wpdb->prepare( " AND MONTH( " . $wpdb->posts . ".post_date ) = %d", substr( $args['m'], 4, 2 ) );

			if ( strlen( $args['m'] ) > 7 )
				$where .= $wpdb->prepare( " AND DAYOFMONTH( " . $wpdb->posts . ".post_date ) = %d", substr( $args['m'], 6, 2 ) );
		}

		if ( ! empty( $args['year'] ) )
			$where .= $wpdb->prepare( " AND YEAR( " . $wpdb->posts . ".post_date ) = %d", $args['year'] );

		if ( ! empty( $args['monthnum'] ) )
			$where .= $wpdb->prepare( " AND MONTH( " . $wpdb->posts . ".post_date ) = %d", $args['monthnum'] );

		if ( ! empty( $args['day'] ) )
			$where .= $wpdb->prepare( " AND DAYOFMONTH( " . $wpdb->posts . ".post_date ) = %d", $args['day'] );

		if ( ! empty( $args['author_name'] ) ) {
			$author = get_user_by( 'slug', sanitize_title_for_query( $args['author_name'] ) );

			if ( $author )
				$args['author'] = $author->ID;
		}

		if ( ! empty( $args['author'] ) )
			$where .= $wpdb->prepare( " AND " . $wpdb->posts . ".post_author = %d", $args['author'] );

		// filtered taxonomies ( post_format )
		foreach ( $taxonomies as $tax_qv ) {
			if ( ! empty( $args[ $tax_qv ] ) ) {
				$join .= " INNER JOIN " . $wpdb->term_relationships . " AS tr ON tr.object_id = " . $wpdb->posts . ".ID";
				$join .= " INNER JOIN " . $wpdb->term_taxonomy . " AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id";
				$join .= " INNER JOIN " . $wpdb->terms . " AS t ON t.term_id = tt.term_id";
				$where .= $wpdb->prepare( ' AND t.slug = %s', $args[ $tax_qv ] );
			}
		}

		// get result
		$result = $wpdb->get_row( $select . $join . $where . $groupby, ARRAY_A );

		return empty( $result['count'] ) ? 0 : (int) $result['count'];
	}

	/**
	 * Featured gallery query arguments.
	 *
	 * @param array $args
	 * @return array
	 */
	public function gallery_featured_query_args( $args ) {
		// set active language
		$args['lang'] = $this->current_lang;

		return $args;
	}

	/**
	 * Folders gallery query arguments.
	 *
	 * @param array $args
	 * @return array
	 */
	public function gallery_folders_query_args( $args ) {
		// set active language
		$args['lang'] = $this->current_lang;

		return $args;
	}

	/**
	 * Update media folders URLs.
	 *
	 * @param string $url
	 * @return string
	 */
	public function media_folder_url( $url ) {
		// active language?
		if ( $this->current_lang !== '' )
			$url = add_query_arg( 'lang', $this->current_lang, $url );

		return $url;
	}

	/**
	 * Redirect to equivalent media folder in specified language.
	 *
	 * @global string $pagenow
	 *
	 * @return void
	 */
	public function media_url_redirect() {
		global $pagenow;

		// get main instance
		$rl = Responsive_Lightbox();

		// only for media with selected language
		if ( $pagenow === 'upload.php' && $this->current_lang !== '' && $rl->options['folders']['active'] ) {
			// get taxonomy
			$taxonomy = $rl->options['folders']['media_taxonomy'];

			// parse URL
			$params = parse_url( html_entity_decode( urldecode( add_query_arg( '', '' ) ) ) );

			if ( isset( $params['query'] ) ) {
				// parse query string
				parse_str( $params['query'], $args );

				if ( isset( $args['lang'], $args[$taxonomy] ) ) {
					// cast term ID
					$term_id = (int) $args[$taxonomy];

					if ( $this->active_plugin === 'polylang' )
						$new_term_id = pll_get_term( $term_id, $args['lang'] );
					else
						$new_term_id = apply_filters( 'wpml_object_id', $term_id, $taxonomy, true, $args['lang'] );

					// different ID?
					if ( $term_id !== $new_term_id ) {
						wp_safe_redirect( add_query_arg( $taxonomy, $new_term_id ) );

						exit;
					}
				}
			}
		}
	}
}