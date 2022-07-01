<?php

$args = array();
$args['id'] = $settings->form_id;
if ( $settings->placeholder ) {
    $args['placeholder'] = $settings->placeholder;
}
$search_form = aws_get_search_form( false, $args );

echo $search_form;