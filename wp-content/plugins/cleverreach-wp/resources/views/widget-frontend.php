<?php

$view_parameters = $this->get_frontend_view_parameters();

echo $view_parameters['before_widget'] .
	$view_parameters['before_title'] .
	$view_parameters['title'] .
	$view_parameters['after_title'];

echo '<p>' . $view_parameters['form_body'] . '</p>';
echo $view_parameters[ 'after_widget' ];