jQuery( function ( $ ) {

	function wc_gzd_is_variable() {
		return $( '#variable_product_options' ).is( ':visible' );
	}

	function wc_gzd_format_price( price ) {
	    return wc_gzd_round_price( price ).toString().replace( '.', ',' );
	}

	function wc_gzd_round_price( price ) {
		var d = parseInt(2,10),
	    dx = Math.pow(10,d),
	    n = parseFloat(price),
	    f = Math.round(Math.round(n * dx * 10) / 10) / dx;

	    return f.toFixed(2);
	}

	function wc_gzd_recalculate_unit_prices( element ) {

		var fields = [ 
			'input[name=_regular_price], input[name*=variable_regular_price]',
			'input[name=_unit_base]',
		];

		var error = false;

		$.each( fields, function( index, value ) {

			$( element ).find( value ).removeClass( 'wc_input_error' );

			if ( ! $( element ).find( value ).val() && ! $( '#general_product_data' ).find( value ).val() ) {
				error = true;
				$( element ).find( value ).addClass( 'wc_input_error' );
			}

		});

		if ( ! error ) {

			$( element ).find( '#_unit_price_regular, input[name*=variable_unit_price_regular]' ).attr( 'readonly', 'readonly' );
			$( element ).find( '#_unit_price_sale, input[name*=variable_unit_price_sale]' ).attr( 'readonly', 'readonly' );

			var base = $( '#general_product_data input[name=_unit_base]' ).val();
			var price = parseFloat( $( element ).find( 'input[name=_regular_price], input[name*=variable_regular_price]' ).val().replace( ',', '.' ) );
			var base_product = base;

			if ( $( element ).find( 'input[name*=variable_unit_product]' ).val() ) {
				// First take variation product units
				base_product = parseFloat( $( element ).find( 'input[name*=variable_unit_product]' ).val().replace( ',', '.' ) );
			} else if ( $( '#general_product_data' ).find( 'input[name=_unit_product]' ).val() ) {
				// Check parent or simple product
				base_product = parseFloat( $( '#general_product_data' ).find( 'input[name=_unit_product]' ).val().replace( ',', '.' ) );
			} else {
				base = 1;
			}

			var old_price = $( element ).find( 'input[name=_unit_price_regular], input[name*=variable_unit_price_regular]' ).val();
			var new_price = wc_gzd_format_price( ( price / base_product ) * base );
			
			$( element ).find( 'input[name=_unit_price_regular], input[name*=variable_unit_price_regular]' ).val( new_price );

			// Tell WooCommerce to save variations if prices have changed
			if ( old_price != new_price ) {
				$( element ).find( 'input[name=_unit_price_regular], input[name*=variable_unit_price_regular]' ).trigger( 'change' );
			}

			if ( $( element ).find( 'input[name=_sale_price], input[name*=variable_sale_price]' ).val() ) {

				var sale_price = parseFloat( $( element ).find( 'input[name=_sale_price], input[name*=variable_sale_price]' ).val().replace( ',', '.' ) );

				var new_sale_price = wc_gzd_format_price( ( sale_price / base_product ) * base );
				var old_sale_price = $( element ).find( 'input[name=_unit_price_sale], input[name*=variable_unit_price_sale]' ).val();

				$( element ).find( 'input[name=_unit_price_sale], input[name*=variable_unit_price_sale]' ).val( new_sale_price );

				// Tell WooCommerce to save variations if prices have changed
				if ( new_sale_price != old_sale_price ) {
					$( element ).find( 'input[name=_unit_price_sale], input[name*=variable_unit_price_sale]' ).trigger( 'change' );
				}

			} else {
				$( element ).find( 'input[name=_unit_price_sale], input[name*=variable_unit_price_sale]' ).val( '' );
			}

		}

	}

	// Simple products
	$( '#general_product_data, .woocommerce_variable_attributes' ).bind( 'recalculate_unit_prices', function() {
		wc_gzd_recalculate_unit_prices( $( this ) );
	});

	$( document ).on( 'click', '#_unit_price_auto', function() {

		if ( $( this ).is( ':checked' ) ) {
			$( this ).parents( '#general_product_data' ).trigger( 'recalculate_unit_prices' );
		} else {
			$( 'input' ).removeClass( 'wc_input_error' );
			$( '#general_product_data' ).find( 'input[name=_unit_price_regular]' ).removeAttr( 'readonly' );
			$( '#general_product_data' ).find( 'input[name=_unit_price_sale]' ).removeAttr( 'readonly' );
		}

	});

	$( document ).on( 'change', 'input[name=_regular_price], input[name=_sale_price], input[name=_unit_base], input[name=_unit_product]', function() {

		if ( $( 'input[name=_unit_price_auto]' ).is( ':checked' ) ) {
			$( this ).parents( '#general_product_data' ).trigger( 'recalculate_unit_prices' );

			if ( $( this ).parents( '.product_data' ).find( '.woocommerce_variable_attributes' ).length > 0 ) {
				$( this ).parents( '.product_data' ).find( '.woocommerce_variable_attributes' ).each( function() {
					wc_gzd_recalculate_unit_prices( $( this ) );
				});
			}
		}
	});

	$( 'input[name=_regular_price]' ).trigger( 'change' );

	// Variable products
	$( document ).find( '.woocommerce_variable_attributes' ).addClass( 'event-bound' );

	$( document ).on( 'click', 'input[name*=variable_unit_price_auto]', function() {

		// Check for new variations that are not bound to the event
		if ( ! $( this ).parents( '.woocommerce_variable_attributes' ).hasClass( 'event-bound' ) ) {
			
			$( this ).parents( '.woocommerce_variable_attributes' ).addClass( 'event-bound' );
			$( this ).parents( '.woocommerce_variable_attributes' ).bind( 'recalculate_unit_prices', function() {
				wc_gzd_recalculate_unit_prices( $( this ) );
			});
		}

		if ( $( this ).is( ':checked' ) ) {
			$( this ).parents( '.woocommerce_variable_attributes' ).trigger( 'recalculate_unit_prices' );
		} else {
			$( this ).parents( '.woocommerce_variable_attributes' ).find( 'input' ).removeClass( 'wc_input_error' );
			$( this ).parents( '.woocommerce_variable_attributes' ).find( 'input[name*=variable_unit_price_regular]' ).removeAttr( 'readonly' );
			$( this ).parents( '.woocommerce_variable_attributes' ).find( 'input[name*=variable_unit_price_sale]' ).removeAttr( 'readonly' );
		}
	});

	$( document ).on( 'change', 'input[name*=variable_regular_price], input[name*=variable_sale_price], input[name*=variable_unit_product]', function() {

		if ( $( this ).parents( '.woocommerce_variable_attributes' ).find( 'input[name*=variable_unit_price_auto]' ).is( ':checked' ) ) {
			wc_gzd_recalculate_unit_prices( $( this ).parents( '.woocommerce_variable_attributes' ) );
		}

	});

	$( '#woocommerce-product-data' ).on( 'click', '.woocommerce_variation', function() {
		
		if ( $( this ).find( 'input[name*=variable_unit_price_auto]' ).is( ':checked' ) ) {
			
			$( this ).find( 'input[name*=variable_unit_price_regular]' ).attr( 'readonly', 'readonly' );
			$( this ).find( 'input[name*=variable_unit_price_sale]' ).attr( 'readonly', 'readonly' );
			wc_gzd_recalculate_unit_prices( $( this ).find( '.woocommerce_variable_attributes' ) );

		}

	});

	// Check for price changes after variations have been loaded
	$( '#woocommerce-product-data' ).bind( 'woocommerce_variations_loaded', function() {

		$( '.woocommerce_variations .woocommerce_variable_attributes' ).each( function() {
			if ( $( this ).find( 'input[name*=variable_unit_price_auto]' ).is( ':checked' ) ) {
				wc_gzd_recalculate_unit_prices( $( this ) );
			}
		});

	});

});