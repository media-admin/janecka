<?php

function pmxe_pmxe_csv_value($value)
{
	$temp = [];
	$pattern = "/^[=\+\-\@](?=[^a-zA-Z]*[a-zA-Z])/";
	$replace = "'$0";

	if( is_array($value) ){
		foreach( $value as $i => $val ){
			if( $val instanceof \WC_Product_Attribute ){
				// Save the element to our temporary array.
				$temp[$i] = $val;

				// Set it to an empty string so we don't lose its location.
				$value[$i] = '';
			}
		}
	}

	// Process the objects separately
	foreach( $temp as $key => $obj ){
		$options = $obj->get_options();
		$options = preg_replace($pattern, $replace, $options);
		$obj->set_options($options);
		$temp[$key] = $obj;
	}

	// Process the original array minus objects.
	$value = preg_replace($pattern, $replace, $value);


	// Merge the arrays into one.
	return array_merge($value, $temp);
}
