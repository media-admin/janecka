jQuery( function( $ ) {

	if ( typeof paymill_form_checkout_submit_id != 'undefined' && $( '.woocommerce-checkout' ).length && $( '.step-wrapper' ).length ) {

		paymill_form_checkout_submit_id = '#next-step-payment';

	}

});