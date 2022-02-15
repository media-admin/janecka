<?php

namespace Vendidero\StoreaBill\PDF;

use Vendidero\StoreaBill\Vendor\Mpdf\Config\ConfigVariables;
use Vendidero\StoreaBill\Vendor\Mpdf\Config\FontVariables;
use Vendidero\StoreaBill\Vendor\Mpdf\Mpdf;
use Vendidero\StoreaBill\Vendor\Mpdf\MpdfException;
use Vendidero\StoreaBill\Vendor\Mpdf\Output\Destination;
use Vendidero\StoreaBill\Fonts\Embed;
use Vendidero\StoreaBill\Fonts\Fonts;
use Vendidero\StoreaBill\Interfaces\PDFMerge;
use Vendidero\StoreaBill\UploadManager;

class MpdfMerger implements PDFMerge {

	/**
	 * Mpdf instance
	 *
	 * @var null|Mpdf
	 */
	protected $_pdf = null;

	/**
	 * Pdf constructor
	 *
	 */
	public function __construct() {
		$font_data = $this->get_font_data();

		$this->_pdf = new Mpdf( array(
			'tempDir'      => $this->get_tmp_directory(),
			'default_font' => Fonts::get_default_font()->get_name(),
			'mode'         => 'utf-8',
			'fontDir'      => $font_data['dir'],
			'fontdata'     => $font_data['data'],
			'fonttrans'    => $font_data['translations'],
			'debug'        => defined( 'SAB_PDF_DEBUG_MODE' ) ? SAB_PDF_DEBUG_MODE : false,
		) );
	}

	public static function get_version() {
		return Mpdf::VERSION;
	}

	protected function get_font_data() {
		$defaultConfig     = ( new ConfigVariables() )->getDefaults();
		$font_dirs         = $defaultConfig['fontDir'];
		$defaultFontConfig = ( new FontVariables() )->getDefaults();
		$font_data         = $defaultFontConfig['fontdata'];
		$font_translations = $defaultFontConfig['fonttrans'];

		$result = array(
			'dir'          => array_merge( $font_dirs, array(
				UploadManager::get_font_path(),
			) ),
			'data'         => array(),
			'translations' => $font_translations,
		);

		/**
		 * Include standard fonts as fallback
		 */
		if ( $default_font = Fonts::get_default_font() ) {
			foreach( $default_font->get_files( 'pdf' ) as $variant => $file_name ) {
				$path = $default_font->get_local_file( $variant, 'pdf' );

				if ( file_exists( $path ) ) {
					if ( ! array_key_exists( $default_font->get_name(), $result['data'] ) ) {
						$result['data'][ $default_font->get_name() ] = array();
					}

					$result['data'][ $default_font->get_name() ][ $this->get_font_variant_shortcode( $variant ) ] = $file_name;
				}
			}

			$result['translations'] += array(
				Fonts::clean_font_family( $default_font->get_family() ) => $default_font->get_name()
			);
		}

		return $result;
	}

	protected function get_font_variant_shortcode( $variant ) {
		$mappings = array(
			'regular'     => 'R',
			'bold'        => 'B',
			'italic'      => 'I',
			'bold_italic' => 'BI'
		);

		return array_key_exists( $variant, $mappings ) ? $mappings[ $variant ] : 'R';
	}

	protected function get_tmp_directory() {
		$upload_dir = UploadManager::get_upload_dir();
		$tmp_dir    = trailingslashit( $upload_dir['basedir'] ) . 'tmp/mpdf';

		return $tmp_dir;
	}

	/**
	 * Add file to this pdf
	 *
	 * @param string $filename Filename of the source file
	 * @param mixed $pages Range of files (if not set, all pages where imported)
	 */
	public function add( $path, $pages = array(), $width = 210 ) {
		if ( file_exists( $path ) ) {
			$pageCount = $this->_pdf->setSourceFile( $path );

			for ( $i = 1; $i <= $pageCount; $i ++ ) {
				if ( $this->_isPageInRange( $i, $pages ) ) {
					$this->addPage( $i, $width );
				}
			}
		}

		return $this;
	}

	/**
	 * Output merged pdf
	 *
	 * @param string $type
	 */
	protected function get( $filename, $type = Destination::INLINE ) {
		return $this->_pdf->Output( $filename, $type );
	}

	/**
	 * Force download merged pdf as file
	 *
	 * @param $filename
	 *
	 * @return string
	 */
	public function output( $filename ) {
		return $this->get( $filename, Destination::INLINE );
	}

	/**
	 * Force download merged pdf as file
	 *
	 * @param $filename
	 *
	 * @return string
	 */
	public function stream() {
		return $this->get( 'doc.pdf', Destination::STRING_RETURN );
	}

	/**
	 * Add single page
	 *
	 * @param $pageNumber
	 *
	 * @throws MpdfException
	 */
	private function addPage( $pageNumber, $width = 210 ) {
		$pageId      = $this->_pdf->importPage( $pageNumber );
		$size        = $this->_pdf->getTemplateSize( $pageId );
		$orientation = isset( $size['orientation'] ) ? $size['orientation'] : '';

		$this->_pdf->AddPage( $orientation );

		if ( ! isset( $size['width'] ) || empty( $size['width'] ) ) {
			$this->_pdf->useImportedPage( $pageId, 0, 0, $width, null, true );
		} else {
			$this->_pdf->useImportedPage( $pageId, 0, 0, $size['width'], $size['height'] );
		}
	}

	/**
	 * Check if a specific page should be merged.
	 * If pages are empty, all pages will be merged
	 *
	 * @return bool
	 */
	private function _isPageInRange( $pageNumber, $pages = [] ) {
		if ( empty( $pages ) ) {
			return true;
		}

		foreach ( $pages as $range ) {
			if ( in_array( $pageNumber, $this->_getRange( $range ) ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Get range by given value
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	private function _getRange( $value = null ) {
		$value = preg_replace( '/[^0-9\-.]/is', '', $value );

		if ( $value == '' ) {
			return false;
		}

		$value = explode( '-', $value );
		if ( count( $value ) == 1 ) {
			return $value;
		}

		return range( $value[0] > $value[1] ? $value[1] : $value[0], $value[0] > $value[1] ? $value[0] : $value[1] );
	}
}