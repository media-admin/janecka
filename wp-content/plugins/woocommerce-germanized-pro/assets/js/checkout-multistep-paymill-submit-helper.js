jQuery( function( $ ) {

	$( 'body' ).on( 'click', '#place_order', function( e ) {
		$( '#wc-gzdp-step-submit' ).remove();
	});

	$( 'body' ).on( 'click', paymill_form_checkout_submit_id, function( e ) {

		if ( $( '#payment_method_paymill' ).is( ':checked' ) ) {

			var next = $( this ).data( 'next' );
			var current = $( this ).data( 'current' );

			$( '#wc_gzdp_is_payment_step_paymill' ).remove();

			$( paymill_form_checkout_id ).append( '<input type="hidden" name="wc_gzdp_step_submit" id="wc-gzdp-step-submit" value="payment" />' );

			e.preventDefault();
			e.stopPropagation();
			e.stopImmediatePropagation();

			$( this ).parents( '.step-wrapper' ).trigger( 'refresh' );

			$( 'body' ).bind( 'wc_gzdp_step_refreshed', function() {

				if ( $( '.woocommerce-error' ).length == 0 ) {

					// next step
					$( '.step-' + next ).trigger( 'change' );

				}

				$( 'body' ).unbind( 'wc_gzdp_step_refreshed' );

			});

		}

	});

});