<?php

namespace Vendidero\StoreaBill\Document;

defined( 'ABSPATH' ) || exit;

class FileExporter extends Exporter {

	protected $document_type = '';

    protected $files = array();

    protected $limit = 25;

	/**
	 * Filename to export to.
	 *
	 * @var string
	 */
	protected $filename = 'sab-export.zip';

    public function __construct( $document_type = '' ) {
    	if ( empty( $document_type ) ) {
    		$document_type = 'invoice';
	    }

        $this->document_type = $document_type;
    }

    public function get_title() {
	    return sprintf( _x( 'Export %s', 'storeabill-core', 'woocommerce-germanized-pro' ), sab_get_document_type_label( $this->get_document_type_object(), 'plural' ) );
    }

    public function get_description() {
	    return sprintf( _x( 'This tool allows you to generate and download a ZIP file containing PDF files', 'storeabill-core', 'woocommerce-germanized-pro' ) );
    }

	public function get_file_extension() {
		return 'zip';
	}

	public function get_document_type() {
        return $this->document_type;
    }

	public function prepare_data_to_export() {
		$result = $this->get_documents();

		$this->total = $result['total'];
		$this->files = array();

		foreach ( $result['documents'] as $document ) {
			if ( ! empty( $document['path'] ) && file_exists( $document['path'] ) ) {
				$this->files[ $document['path'] ] = basename( $document['path'] );
            }

			++ $this->total_exported;
		}
    }

    protected function get_file_size() {
	    return @filesize( $this->get_file_path() );
    }

	public function send_headers() {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable(); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.gc_enableFound
		}
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 ); // @codingStandardsIgnoreLine
		}
		ob_clean();
		ob_end_flush();
		@ini_set( 'zlib.output_compression', 'Off' ); // @codingStandardsIgnoreLine
		@ini_set( 'output_buffering', 'Off' ); // @codingStandardsIgnoreLine
		@ini_set( 'output_handler', '' ); // @codingStandardsIgnoreLine
		ignore_user_abort( true );
		wc_set_time_limit( 0 );
		wc_nocache_headers();
		header( 'Content-Type: application/octet-stream; charset=utf-8' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . $this->get_file_size() );
		header( 'Content-Disposition: attachment; filename=' . $this->get_filename() );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
    }

	public function get_type() {
		return 'file';
	}

	protected function send_content() {
		@readfile( $this->get_file_path() );
	}

	public function get_file() {
    	if ( ! class_exists( 'ZipArchive' ) ) {
		    $this->add_error( _x( 'Please make sure to install the PHP zip package. Ask your webhoster for further information.', 'storeabill-core', 'woocommerce-germanized-pro' ) );

		    return false;
	    }

		$file = new \ZipArchive();

		if ( @file_exists( $this->get_file_path() ) ) {
			$result = $file->open( $this->get_file_path() );
		} else {
			$result = $file->open( $this->get_file_path(), \ZipArchive::CREATE );
		}

		if ( true !== $result ) {
			$this->add_error( _x( 'Error while reading or creating ZIP file.', 'storeabill-core', 'woocommerce-germanized-pro' ) );

			return false;
		}

		return $file;
	}

	protected function write_data() {
    	if ( $zip = $this->get_file() ) {

    		foreach( $this->files as $file => $filename ) {
    			$zip->addFile( $file, $filename );
		    }

		    $zip->close();
	    }
	}
}
