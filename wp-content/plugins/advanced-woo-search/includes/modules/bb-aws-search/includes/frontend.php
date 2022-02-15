<?php

$search_form = aws_get_search_form( false );
$search_form = preg_replace( '/placeholder="([\S\s]*?)"/i', 'placeholder="' . esc_attr( $settings->placeholder ) . '"', $search_form );

echo $search_form;
