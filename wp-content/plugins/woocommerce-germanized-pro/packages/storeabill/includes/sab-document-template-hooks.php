<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'storeabill_document_head',                         'sab_document_enqueue_scripts', 1 );
// Make sure that sab_print_styles does not receive the document type as a parameter
add_action( 'storeabill_document_head',                         'sab_document_print_styles', 8, 0 );
add_action( 'storeabill_document_enqueue_scripts',              'sab_document_register_styles', 9 );