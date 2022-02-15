<?php

$form_id = $settings->form_id;
$search_form = aws_get_search_form( false, array( 'id' => $form_id ) );
$search_form = preg_replace( '/placeholder="([\S\s]*?)"/i', 'placeholder="' . esc_attr( $settings->placeholder ) . '"', $search_form );

echo $search_form;