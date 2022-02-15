<?php

namespace Vendidero\StoreaBill\Fonts;

use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\UploadManager;

defined( 'ABSPATH' ) || exit;

/**
 * Manages the way Google Fonts are enqueued.
 */
final class Embed {

	/**
	 * All the fonts requested.
	 *
	 * @var Font[]
	 */
	protected $fonts_to_embed = array();

	protected $display_types = array();

	/**
	 * @var Font[]
	 */
	protected $fonts = array();

	protected $result_data = null;

	protected $type = 'html';

	protected $font_results = array();

	/**
	 * Constructor.
	 */
	public function __construct( $fonts, $display_types, $type = 'html' ) {
		$this->fonts          = array();
		$this->display_types  = $display_types;
		$this->type           = $type;
		$this->fonts_to_embed = array();
		$this->result_data    = null;

		$this->populate_fonts( $fonts );
	}

	public function populate_fonts( $fonts ) {
		foreach ( $fonts as $display_type => $font_data ) {

			if ( ! is_a( $font_data, '\Vendidero\StoreaBill\Fonts\Font' ) ) {
				$font_data = wp_parse_args( $font_data, array(
					'name' => ''
				) );

				if ( ! $global_font = Fonts::get_font( $font_data['name'] ) ) {
					continue;
				}

				$font = clone $global_font;

				if ( isset( $font_data['variants'] ) ) {
					$font_data['variant_mappings'] = $font_data['variants'];

					unset( $font_data['variants'] );
				}

				$font->set_props( $font_data );
			} else {
				$font = $font_data;
			}

			if ( ! is_a( $font, '\Vendidero\StoreaBill\Fonts\Font' ) || ! ( $global_font = Fonts::get_font( $font->get_name() ) ) ) {
				continue;
			}

			$this->fonts[ $display_type ] = $font;

			if ( ! array_key_exists( $font->get_name(), $this->fonts_to_embed ) ) {
				$this->fonts_to_embed[ $font->get_name() ] = clone $font;
			} else {
				$new_variants = array_merge( $this->fonts_to_embed[ $font->get_name() ]->get_variant_mappings(), $font->get_variant_mappings() );
				$this->fonts_to_embed[ $font->get_name() ]->set_variant_mappings( $new_variants );
			}
		}
	}

	/**
	 * Extract data from Google Fonts CSS.
	 *
	 * @param $font_data
	 *
	 * @return array
	 */
	protected function parse_google_css( $font_data ) {
		preg_match_all( '/(?ims)([a-z0-9\s\.\:#_\-@,]+)\{([^\}]*)\}/', $font_data, $arr );
		$result = array();

		foreach ( $arr[0] as $i => $css ){
			$url       = '';
			$rules     = explode(';', trim( $arr[2][ $i ] ) );
			$rules_arr = array();
			$variant   = false;

			foreach ( $rules as $strRule ) {

				if ( ! empty( $strRule ) ) {
					$rule     = explode( ":", $strRule );
					$rule_key = sab_clean( trim( $rule[0] ) );
					$rule_val = sab_clean( trim( $rule[1] ) );

					if ( 'font-weight' === $rule_key ) {
						$rule_key = 'weight';
					} elseif ( 'font-style' === $rule_key ) {
						$rule_key = 'style';
					} elseif ( 'src' === $rule_key ) {
						preg_match('/(\'.*?\')/', $strRule, $variant_string );

						if ( ! empty( $variant_string ) ) {
							$variant_string = str_replace( "'", "", trim( $variant_string[0] ) );
							$maps           = array(
								'Bold Italic' => 'bold_italic',
								'Regular'     => 'regular',
								'Italic'      => 'italic',
								'Bold'        => 'bold',
							);

							foreach( $maps as $map_key => $map_variant ) {

								if ( substr( $variant_string, strlen( $map_key ) * -1 ) === $map_key ) {
									$variant = $map_variant;
									break;
								}
							}
						}

						preg_match_all('#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $strRule, $matches );
						$matches = array_shift( $matches );

						if ( ! empty( $matches ) && ( 0 === strpos( $matches[0], 'https://fonts.gstatic.com' ) ) ) {
							$url = $matches[0];
						}

						continue;
					} else {
						continue;
					}

					$rules_arr[ $rule_key ] = $rule_val;
				}
			}

			if ( empty( $url ) || ! isset( $rules_arr['weight'] ) ) {
				continue;
			}

			$rules_arr['url']       = $url;
			$ext                    = pathinfo( $url, PATHINFO_EXTENSION );
			$rules_arr['local_url'] = Helper::download_font_file( $url, $ext );

			// Could not download font
			if ( ! $rules_arr['local_url'] ) {
				continue;
			}

			if ( $variant ) {
				$rules_arr['variant'] = $variant;
			}

			$css = str_replace( $rules_arr['url'], $rules_arr['local_url'], $css );

			// Add font-display:swap to improve rendering speed.
			$css = str_replace( '@font-face {', '@font-face{', $css );
			$css = str_replace( '@font-face{', '@font-face{font-display:auto;', $css );

			// Remove blank lines and extra spaces.
			$css = str_replace(
				[ ': ', ';  ', '; ', '  ' ],
				[ ':', ';', ';', ' ' ],
				preg_replace( "/\r|\n/", '', $css )
			);

			// Remove protocol to fix http/https issues.
			$css = str_replace(
				[ 'http://', 'https://' ],
				[ '//', '//' ],
				$css
			);

			$rules_arr['css'] = wp_strip_all_tags( $css );

			$result[] = $rules_arr;
		}

		usort($result, function( $a, $b ) {
			if ( $a['weight'] == $b['weight'] ) return 0;
			return ( $a['weight'] < $b['weight'] ) ? -1 : 1;
		} );

		return $result;
	}

	/**
	 * @param Font $font
	 *
	 * @return array|mixed
	 */
	protected function get_google_font_data( $font ) {
		$type         = $this->type === 'html' ? 'woff' : 'ttf';
		$variants     = join( ',', array_values( $font->get_variant_mappings() ) );
		$family       = str_replace( ' ', '+', trim( $font->get_family() ) );

		$subset       = apply_filters( 'storeabill_googlefonts_font_subset', 'cyrillic,cyrillic-ext,devanagari,greek,greek-ext,khmer,latin,latin-ext,vietnamese,hebrew,arabic,bengali,gujarati,tamil,telugu,thai' );
		$url          = "https://fonts.googleapis.com/css?family={$family}:{$variants}&subset={$subset}";
		$transient_id = 'storeabill_gfonts_' . md5( $url . '_' . $type );
		$result       = get_transient( $transient_id );

		if ( ! $result ) {
			// Get the contents of the remote URL.
			$contents = Helper::get_remote_font( $url, $type );

			if ( ! is_wp_error( $contents ) && $contents ) {
				$result = $this->parse_google_css( $contents );

				// Set the transient for a day.
				set_transient( $transient_id, $result, DAY_IN_SECONDS );
			} else {
				$result = array();
			}
		} else {
			$updated = false;

			foreach( $result as $key => $font ) {
				$font = wp_parse_args( $font, array(
					'local_url' => '',
					'url'       => '',
				) );

				$local_path = self::get_font_path( $font );

				if ( empty( $font['local_url'] ) || ! @file_exists( $local_path ) ) {
					$ext                         = pathinfo( $font['url'], PATHINFO_EXTENSION );
					$result[ $key ]['local_url'] = Helper::download_font_file( $font['url'], $ext );

					// Could not download font
					if ( ! $result[ $key ]['local_url'] ) {
						unset( $result[ $key ] );
					} else {
						$updated = true;
					}
				}
			}

			if ( $updated ) {
				// Set the transient for a day.
				set_transient( $transient_id, $result, DAY_IN_SECONDS );
			}
		}

		return $result;
	}

	protected function get_result_data() {

		if ( ! is_null( $this->result_data ) ) {
			return $this->result_data;
		}

		$global_css = '';

		foreach ( $this->fonts_to_embed as $font_name => $font ) {

			if ( $font->is_google_font() ) {
				$data = $this->get_google_font_data( $font );
			} else {
				$data = $this->get_font_data( $font );
			}

			if ( ! empty( $data ) ) {
				$this->font_results[ $font_name ] = array(
					'name'   => $font->get_name(),
					'family' => $font->get_family(),
					'files'  => $this->map_font_to_variants( $data, $font )
				);
			}

			foreach( $data as $font_data ) {
				$global_css .= $font_data['css'];
			}
		}

		$this->result_data = array(
			'font_facets'   => $global_css,
			'display_types' => array(),
		);

		foreach( $this->fonts as $display_type => $font ) {
			$this->result_data['display_types'][ $display_type ] = $this->generate_css( $font->get_family(), $display_type );
		}

		return $this->result_data;
	}

	public function get_fonts() {
		$result_data = $this->get_result_data();

		return $this->font_results;
	}

	/**
	 * @param Font $font
	 *
	 * @return array
	 */
	protected function get_font_data( $font ) {
		$font_data  = array();

		foreach( $font->get_files( $this->type ) as $variant => $file_name ) {

			if ( 'regular' !== $variant && ! $font->has_variant_mapping( $variant ) ) {
				continue;
			}

			$file = $font->get_local_file( $variant, $this->type );

			if ( $file && file_exists( $file ) ) {
				$data = array(
					'local_url'  => $font->get_local_url( $variant, $this->type ),
					'local_path' => $file,
					'family'     => $font->get_family(),
					'variant'    => $variant,
					'css'        => '',
					'weight'     => ( 'bold' === $variant || 'bold_italic' === $variant ) ? 'bold' : 'normal',
					'style'      => ( $variant === 'italic' || $variant === 'bold_italic' ) ? 'italic' : 'normal'
				);

				$data['css'] = $this->generate_font_face( $data );
				$font_data[] = $data;
			}
		}

		usort($font_data, function( $a, $b ) {
			if ( $a['weight'] == $b['weight'] ) return 0;
			return ( $a['weight'] < $b['weight'] ) ? -1 : 1;
		} );

		return $font_data;
	}

	protected function generate_font_face( $font_data ) {
		$extension     = pathinfo( $font_data['local_url'], PATHINFO_EXTENSION );
		$local_name    = $font_data['family'];
		$explicit_name = $font_data['family'];

		if ( 'normal' !== $font_data['weight'] ) {
			$local_name    .= ' ' . ucfirst( $font_data['weight'] );
			$explicit_name .= '-' . ucfirst( $font_data['weight'] );
		}

		if ( 'normal' !== $font_data['style'] ) {
			$local_name    .= ' ' . ucfirst( $font_data['style'] );
			$explicit_name .= '-' . ucfirst( $font_data['style'] );
		}

		$explicit_name = ( $explicit_name === $font_data['family'] ) ? ( $font_data['family'] . '-Regular' ) : $explicit_name;

		return '@font-face{ font-display:auto; font-family: "' . $font_data['family'] . '"; font-style:' . $font_data['style'] . '; font-weight:' . $font_data['weight'] . '; src:local("' . $local_name . '"), local("' . $explicit_name . '"), url(' . $font_data['local_url'] . ') format("' . $extension. '");}';
	}

	protected function get_variant_identifier( $data ) {
		$variant_string = isset( $data['weight'] ) ? $data['weight'] : $data['style'];

		if ( is_numeric( $variant_string ) ) {
			$variant_string .= $data['style'] === 'italic' ? 'italic' : '';
		} else {
			if ( isset( $data['variant'] ) && strpos( $variant_string, $data['variant'] ) === false ) {
				$variant_string .= $data['variant'];
			}
		}

		if ( 'normalregular' === $variant_string ) {
			$variant_string = 'regular';
		} elseif( 'normalitalic' === $variant_string ) {
			$variant_string = 'italic';
		} elseif( 'boldbold_italic' === $variant_string ) {
			$variant_string = 'bold_italic';
		}

		return $variant_string;
	}

	protected function get_font_path( $data ) {
		return isset( $data['local_path'] ) ? $data['local_path'] : trailingslashit( UploadManager::get_font_path() ) . basename( $data['local_url'] );
	}

	protected function get_matching_variant( $variant, $font_data ) {
		foreach( $font_data as $data ) {
			switch( $variant ) {
				case 'regular':
				case 'bold':
					if ( 'normal' === $data['style'] ) {
						return $this->get_font_path( $data );
					}
					break;
				case 'italic':
				case 'bold_italic':
					if ( 'italic' === $data['style'] ) {
						return $this->get_font_path( $data );
					}
					break;
			}
		}

		return $this->get_font_path( $font_data[0] );
	}

	/**
	 * @param $font_data
	 * @param Font $font
	 *
	 * @return array
	 */
	protected function map_font_to_variants( $font_data, $font ) {
		$font_variants = array();

		foreach( array_keys( sab_get_font_variant_types() ) as $variant ) {
			$font_variants[ $variant ] = '';

			if ( $font->has_variant_mapping( $variant ) ) {
				$variant_mapping = $font->get_variant_mapping( $variant );

				foreach( $font_data as $data ) {
					$variant_string = $this->get_variant_identifier( $data );

					if ( $variant_mapping === $variant_string ) {
						$font_variants[ $variant ] = isset( $data['local_path'] ) ? $data['local_path'] : trailingslashit( UploadManager::get_font_path() ) . basename( $data['local_url'] );
						break;
					}
				}
			}
		}

		$font_data_asc  = $font_data;
		$font_data_desc = $font_data;

		usort( $font_data_asc, function ( $data_1, $data_2 ) {
			if ( is_numeric( $data_1['weight'] ) ) {
				if ( $data_1['weight'] == $data_2['weight'] ) return 0;
				return $data_1['weight'] < $data_2['weight'] ? -1 : 1;
			} else {
				$order = array( 'regular', 'bold' );

				$pos_a = array_search( $data_1['weight'], $order );
				$pos_b = array_search( $data_2['weight'], $order );

				return $pos_a - $pos_b;
			}
		} );

		usort( $font_data_desc, function ( $data_1, $data_2 ) {
			if ( is_numeric( $data_1['weight'] ) ) {
				if ( $data_1['weight'] == $data_2['weight'] ) return 0;
				return $data_1['weight'] > $data_2['weight'] ? -1 : 1;
			} else {
				$order = array( 'bold', 'regular' );

				$pos_a = array_search( $data_1['weight'], $order );
				$pos_b = array_search( $data_2['weight'], $order );

				return $pos_a - $pos_b;
			}
		} );

		/**
		 * Fallback to manually checking matching variants based on weight and font style.
		 */
		foreach( $font_variants as $variant => $url ) {
			if ( empty( $url ) ) {
				if ( 'regular' === $variant ) {
					$font_variants[ $variant ] = $this->get_matching_variant( $variant, $font_data_asc );
				} elseif ( 'italic' === $variant ) {
					$font_variants[ $variant ] = $this->get_matching_variant( $variant, $font_data_asc );
				} elseif ( 'bold' === $variant ) {
					$font_variants[ $variant ] = $this->get_matching_variant( $variant, $font_data_desc );
				} elseif( 'bold_italic' === $variant ) {
					$font_variants[ $variant ] = $this->get_matching_variant( $variant, $font_data_desc );
				}
			}
		}

		return $font_variants;
	}

	/**
	 * Get CSS code to be embedded.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function get_inline_css( $display_type = '' ) {
		$result = $this->get_result_data();
		$css    = '';

		if ( ! empty( $display_type ) ) {
			$css = array_key_exists( $display_type, $result['display_types'][ $display_type ] ) ? $result['display_types'][ $display_type ] : '';
		} else {
			$css = implode(  "\n ", $result['display_types'] );
		}

		return $css;
	}

	/**
	 * Get CSS code to be embedded.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function get_font_facets_css() {
		$result = $this->get_result_data();

		return $result['font_facets'];
	}

	public function get_css() {
		$css = $this->get_font_facets_css();

		if ( ! empty( $css ) ) {
			$css .= "\n" . $this->get_inline_css();
		}

		return $css;
	}

	public function get_data() {
		$result = $this->get_result_data();

		return $result;
	}

	protected function generate_variant_styles( $font_type, $display_type, $default_value = 'normal' ) {
		$display_type_data = array_key_exists( $display_type, $this->display_types ) ? $this->display_types[ $display_type ] : false;
		$current_font      = array_key_exists( $display_type, $this->fonts ) ? $this->fonts[ $display_type ] : false;
		$styles            = array();

		if ( ! $display_type_data ) {
			return '';
		}

		$font_weight = $default_value;

		if ( $current_font && $current_font->has_variant_mapping( $font_type ) ) {
			$font_weight = $current_font->get_variant_mapping( $font_type );
		}

		$selectors = $display_type_data['selectors'][ $this->type ];

		if ( 'bold' === $font_type ) {
			$selectors .= ' strong';
		} elseif( 'italic' === $font_type ) {
			$selectors .= ' em';
		} elseif( 'bold_italic' === $font_type ) {
			$selectors .= ' strong em, ' . $selectors . ' em strong';
		}

		/**
		 * Support numericals as well
		 */
		if ( strpos( $font_weight, 'italic' ) !== false ) {
			$styles['font-style'] = 'italic';
		}

		/**
		 * Remove italic (i) additions from numeric font types e.g. 600i
		 */
		if ( preg_match('/\\d/', $font_weight ) > 0 ) {
			$font_weight = preg_replace("/[^0-9]/", "", $font_weight );
		}

		if ( 'pdf' === $this->type ) {
			$font_weight = is_numeric( $font_weight ) ? $default_value : $font_weight;
		}

		$styles['font-weight'] = is_numeric( $font_weight ) ? $font_weight : $default_value;

		return $selectors . ' {' . sab_print_styles( $styles, false ) . '} ';
	}

	protected function generate_css( $font_family, $display_type ) {
		$display_type_data = array_key_exists( $display_type, $this->display_types ) ? $this->display_types[ $display_type ] : false;

		if ( ! $display_type_data ) {
			return '';
		}

		$selectors   = $display_type_data['selectors'][ $this->type ];
		$font_family = 'pdf' === $this->type ? Fonts::clean_font_family( $font_family ) : $font_family;

		$styles    = array(
			'font-family' => "'" . $font_family . "'",
		);

		$css  = $selectors . ' {' . sab_print_styles( $styles, false ) . '} ';
		$css .= $this->generate_variant_styles( 'regular', $display_type, 'normal' );
		$css .= $this->generate_variant_styles( 'bold', $display_type, 'bold' );
		$css .= $this->generate_variant_styles( 'italic', $display_type, 'normal' );
		$css .= $this->generate_variant_styles( 'bold_italic', $display_type, 'bold' );

		return $css;
	}
}