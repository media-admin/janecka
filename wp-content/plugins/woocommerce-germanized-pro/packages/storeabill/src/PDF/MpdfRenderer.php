<?php
/**
 * Mpdf
 *
 * @package Vendidero/StoreaBill
 * @version 1.0.0
 */
namespace Vendidero\StoreaBill\PDF;

use Vendidero\StoreaBill\Vendor\Mpdf\Mpdf;
use Vendidero\StoreaBill\Vendor\Mpdf\MpdfException;
use Vendidero\StoreaBill\Vendor\Mpdf\Output\Destination;
use Vendidero\StoreaBill\Vendor\Mpdf\Config\ConfigVariables;
use Vendidero\StoreaBill\Vendor\Mpdf\Config\FontVariables;

use Vendidero\StoreaBill\Document\DefaultTemplate;
use Vendidero\StoreaBill\Exceptions\DocumentRenderException;
use Vendidero\StoreaBill\Fonts\Embed;
use Vendidero\StoreaBill\Fonts\Fonts;
use Vendidero\StoreaBill\Interfaces\PDF;
use Vendidero\StoreaBill\Package;
use Vendidero\StoreaBill\UploadManager;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Class.
 */
class MpdfRenderer implements PDF {

	/**
	 * @var Mpdf
	 */
	protected $pdf = null;

	protected $content_parts = array();

	protected $template = false;

	protected $disable_template = false;

	/**
	 * MpdfRenderer constructor.
	 *
	 * @throws DocumentRenderException
	 */
	public function __construct( $args ) {
		$args = wp_parse_args( $args, array(
			'template' => false,
		) );

		$this->set_template( $args['template'] );

		try {
			$this->setup();
		} catch ( \Exception $e ) {
			$message = sprintf( 'mPDF error while setting up: %1$s (%2$s line %3$s)', $e->getMessage(), $e->getFile(), $e->getLine() );
			Package::log( $message );

			throw new DocumentRenderException( _x( 'Unable to setup mPDF.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}
	}

	protected function setup_mpdf( $default_args = array() ) {
		$font_data = $this->get_font_data();
		$args      = wp_parse_args( $default_args, array(
			'setAutoTopMargin'    => 'stretch',
			'setAutoBottomMargin' => 'stretch',
			'tempDir'             => $this->get_tmp_directory(),
			'mode'                => 'utf-8',
			'fontDir'             => $font_data['dir'],
			'fontdata'            => $font_data['data'],
			'fonttrans'           => $font_data['translations'],
			'default_font'        => $this->get_template()->get_default_font()['name'],
			'debug'               => defined( 'SAB_PDF_DEBUG_MODE' ) ? SAB_PDF_DEBUG_MODE : false,
			'autoArabic'          => true,
			'autoLangToFont'      => true,
		) );

		$pdf = new Mpdf( apply_filters( 'storeabill_mpdf_setup_args', $args, $default_args, $this ) );

		return $pdf;
	}

	public function setup( $default_args = array(), $use_template = true ) {
		$this->pdf = $this->setup_mpdf( $default_args );

		if ( $use_template ) {
			$this->setup_document_template();
		}

		if ( $this->pdf->debug ) {
			$this->pdf->showImageErrors = true;
		}

		do_action( 'storeabill_setup_mpdf', $this->pdf, $default_args, $use_template, $this );
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
				Fonts::clean_font_family( $default_font->get_family() ) => $default_font->get_name(),
			);
		}

		/**
		 * Embed template fonts
		 */
		if ( $template = $this->get_template() ) {
			$fonts = $template->get_fonts();

			if ( ! empty( $fonts ) ) {
				$embed = new Embed( $fonts, $template->get_font_display_types(), 'pdf' );
				$files = array();

				foreach( $embed->get_fonts() as $font_name => $embed_font_data ) {

					$files[ $font_name ] = array(
						'R'  => basename( $embed_font_data['files']['regular'] ),
						'I'  => basename( $embed_font_data['files']['italic'] ),
						'B'  => basename( $embed_font_data['files']['bold'] ),
						'BI' => basename( $embed_font_data['files']['bold_italic'] ),
					);

					$result['translations'] += array(
						Fonts::clean_font_family( $embed_font_data['family'] ) => $embed_font_data['name'],
					);
				}

				$result['data'] += $files;
			}
		}

		return $result;
	}

	protected function get_tmp_directory() {
		$upload_dir = UploadManager::get_upload_dir();
		$tmp_dir    = trailingslashit( $upload_dir['basedir'] ) . 'tmp/mpdf';

		return $tmp_dir;
	}

	/**
	 * Setup document template support for mPDF.
	 * In case a differing first page template was chosen we'll need
	 * to create a new file containing 2 pages to support mPDFs logic.
	 */
	protected function setup_document_template() {
		$templates = array(
			'first_page' => $this->get_template()->get_first_page()->get_pdf_template(),
			'default'    => $this->get_template()->get_pdf_template(),
		);

		if ( $templates['first_page'] !== $templates['default'] ) {

			$first_page_filename = $templates['first_page'] ? basename( $templates['first_page'], '.pdf' ) : '';
			$default_filename    = $templates['default'] ? basename( $templates['default'], '.pdf' ) : '';
			$path                = $templates['default'] ? dirname( $templates['default'] ) : dirname( $templates['first_page'] );
			$new_filename        = trailingslashit( $path ) . $first_page_filename . '-' . $default_filename . '.pdf';

			if ( ! file_exists( $new_filename ) ) {
				try {
					$newTemplate = $this->setup_mpdf();

					if ( ! empty( $templates['first_page'] ) ) {
						$newTemplate->setSourceFile( $templates['first_page'] );
						$tplId = $newTemplate->ImportPage( 1 );
						$newTemplate->UseTemplate( $tplId );
					}

					$newTemplate->WriteHTML( '<pagebreak />' );

					if ( ! empty( $templates['default'] ) ) {
						$newTemplate->setSourceFile( $templates['default'] );
						$tplId = $newTemplate->ImportPage( 1 );
						$newTemplate->UseTemplate( $tplId );
					}

					$newTemplate->output( $new_filename, Destination::FILE );

				} catch ( \Exception $e ) {
					Package::log( sprintf( 'Error while merging PDF template %s for differing first page.', $templates['default'] ), 'info', 'render' );
				}
			}

			if ( file_exists( $new_filename ) ) {
				$templates['default'] = $new_filename;
			}
		}

		if ( $templates['first_page'] && ! $templates['default'] ) {
			$this->pdf->SetDocTemplate( $templates['first_page'],false );
		} elseif( $templates['default'] ) {
			$this->pdf->SetDocTemplate( $templates['default'],true );
		}
	}

	public static function supports( $feature ) {

	}

	public static function get_version() {
		return Mpdf::VERSION;
	}

	protected function set_content_part( $part, $html ) {
		$this->content_parts[ $part ] = $html;
	}

	protected function replace_tags( $content, $part ) {
		$html_tags = array(
			'<!--nextpage-->'        => '<pagebreak>',
			'<!--current_page_no-->' => '{PAGENO}',
			'<!--total_pages_no-->'  => '{nb}'
		);

		$content = str_replace( array_keys( $html_tags ), array_values( $html_tags ), $content );

		return $content;
	}

	protected function get_content_part( $part ) {
		$content = array_key_exists( $part, $this->content_parts ) ? $this->content_parts[ $part ] : '';

		return $this->replace_tags( $content, $part );
	}

	public function set_wrapper_before( $html ) {
		$this->set_content_part( 'wrapper_before', $html );
	}

	public function set_wrapper_after( $html ) {
		$this->set_content_part( 'wrapper_after', $html );
	}

	public function set_content( $html ) {
		$this->set_content_part( 'content', $html );
	}

	public function set_header( $html ) {
		$this->set_content_part( 'header', $html );
	}

	public function set_header_first_page( $html ) {
		$this->set_content_part( 'header_first_page', $html );
	}

	public function set_footer( $html ) {
		$this->set_content_part( 'footer', $html );
	}

	public function set_footer_first_page( $html ) {
		$this->set_content_part( 'footer_first_page', $html );
	}

	public function set_template( $template ) {
		$this->template = $template;
	}

	/**
	 * @return bool|DefaultTemplate
	 */
	public function get_template() {
		return $this->template;
	}

	protected function get_styles() {
		$template = $this->get_template();

		$styles = "
			@page {  
			    header: html_headerDefault;
			    footer: html_footerDefault;
			    margin-header: {$template->get_margin( 'top' )}cm;
			    margin-footer: {$template->get_margin( 'bottom' )}cm;
			    margin-left: {$template->get_margin( 'left' )}cm;
			    margin-right: {$template->get_margin( 'right' )}cm;
			    margin-top: 0cm;
			    margin-bottom: 0cm;
			}
			@page :first {    
			    header: html_headerFirstPage;
			    footer: html_footerFirstPage;
			    margin-header: {$template->get_first_page()->get_margin( 'top' )}cm;
			    margin-footer: {$template->get_first_page()->get_margin( 'bottom' )}cm;
			    margin-top: 0cm;
			    margin-bottom: 0cm;
			}
		";

		return $styles;
	}

	protected function get_html() {
		$wrapper_before = $this->get_content_part( 'wrapper_before' );
		$wrapper_before = str_replace( '</head>', "<style>{$this->get_styles()}</style></head>", $wrapper_before );

		$html = "
			{$wrapper_before}
			
			<htmlpageheader name='headerFirstPage' style='display:none'>
				{$this->get_content_part( 'header_first_page' )}
			</htmlpageheader>
			<htmlpageheader name='headerDefault' style='display:none'>
				{$this->get_content_part( 'header' )}
			</htmlpageheader>
			
			{$this->get_content_part( 'content' )}
			
			<htmlpagefooter name='footerFirstPage' style='display:none'>
				{$this->get_content_part( 'footer_first_page' )}
			</htmlpagefooter>
			<htmlpagefooter name='footerDefault' style='display:none'>
				{$this->get_content_part( 'footer' )}
			</htmlpagefooter>
				
			{$this->get_content_part( 'wrapper_after' )}
		";

		/**
		 * Remove empty paragraph tags before rendering to prevent spacings within empty
		 * if_document shortcodes.
		 */
		$html = preg_replace( "/<p[^>]*>(?:\s|&nbsp;)*<\/p>/", '', $html );

		return $html;
	}

	/**
	 * @param string $filename
	 *
	 * @return string|void
	 * @throws DocumentRenderException
	 */
	public function output( $filename ) {
		try {
			$this->render();

			sab_clean_buffers();

			$this->pdf->Output( $filename, Destination::INLINE );
		} catch( \Exception $e ) {
			$this->handle_error( $e, array( $this, 'output' ), array( $filename ) );
		}
	}

	protected function remove_template_pdf_background() {
		$template = $this->get_template();
		$template->set_pdf_template_id( 0 );
		$template->save();

		$template = $this->get_template()->get_first_page();
		$template->set_pdf_template_id( 0 );
		$template->save();
	}

	protected function remove_custom_font() {
		$template = $this->get_template();
		$template->set_fonts( array() );
		$template->save();
	}

	protected function is_preview() {
		return defined( 'SAB_IS_DOCUMENT_PREVIEW' ) && SAB_IS_DOCUMENT_PREVIEW;
	}

	protected function is_editor_preview() {
		return defined( 'SAB_IS_EDITOR_PREVIEW' ) && SAB_IS_EDITOR_PREVIEW;
	}

	/**
	 * @throws MpdfException
	 */
	protected function render() {
		do_action( 'storeabill_mpdf_before_render_pdf', $this, $this->pdf );

		$this->pdf->WriteHTML( $this->get_html() );

		/**
		 * Force at least 2 pages (to show difference between first and second pages) within editor preview.
		 */
		if ( $this->is_editor_preview() ) {
			if ( $this->pdf->page < 2 ) {
				$this->pdf->WriteHTML( '<pagebreak />' );
			}
		}

		/**
		 * Allow encrypting PDF files to prevent editing the files.
		 * Files created with that option cannot be merged into one document.
		 *
		 * By default a editing password will be generated randomly by mPDF.
		 *
		 * @see https://mpdf.github.io/reference/mpdf-functions/setprotection.html
		 */
		if ( apply_filters( 'storeabill_encrypt_pdf_files', false, $this ) ) {
			$this->pdf->setProtection( array(
				'copy',
				'print',
				'print-highres'
			) );
		}

		do_action( 'storeabill_mpdf_render_pdf', $this, $this->pdf );
	}

	/**
	 * @param \Exception $e
	 *
	 * @throws DocumentRenderException
	 */
	private function handle_error( $e, $callback, $args = array() ) {
		$message = $e->getMessage();

		/**
		 * Rerender document without PDF templates (as it seems to use a compression technique unknown to FPDI)
		 */
		if ( is_a( $e, 'setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException' ) ) {
			$this->remove_template_pdf_background();

			Package::log( sprintf( 'PDF template background for %s cannot be used due to compression and/or version error', $this->get_template()->get_id() ) );

			$this->setup( array(), false );
			call_user_func_array( $callback, $args );
		} elseif( is_a( $e, 'Mpdf\Exception\FontException' ) ) {
			$this->remove_custom_font();

			Package::log( sprintf( 'The font is not supported for template %s.', $this->get_template()->get_id() ) );

			$this->setup( array(), false );
			call_user_func_array( $callback, $args );
		} elseif ( is_a( $e, 'Mpdf\MpdfException' ) ) {
			$message = sprintf( 'mPDF error while processing PDF: %1$s (%2$s line %3$s)', $e->getMessage(), $e->getFile(), $e->getLine() );

			Package::log( $message );
		}

		throw new DocumentRenderException( $message );
	}

	public function stream() {
		try {
			$this->render();

			return $this->pdf->Output( 'doc.pdf', Destination::STRING_RETURN );
		} catch( \Exception $e ) {
			$this->handle_error( $e, array( $this, 'stream' ) );

			return false;
		}
	}

	public function set_options( $options = array() ) {

	}

	public function get_option( $key ) {

	}
}