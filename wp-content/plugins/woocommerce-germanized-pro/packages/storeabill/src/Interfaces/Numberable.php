<?php

namespace Vendidero\StoreaBill\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Numberable Interface
 *
 * This interface makes sure that an object is summable.
 */
interface Numberable {

	public function get_number();

	public function get_formatted_number();

	public function has_number();

	public function format_number( $number );

	public function get_journal_type();

	public function get_journal();
}