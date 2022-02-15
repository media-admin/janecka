jQuery( function ( $ ) {

	$( document ).on( 'click', '#wc-gzdp-confirm-order-button', function() {
		$(this).parents( '.wc-gzdp-submit-wrapper' ).find( '#wc-gzdp-confirm-order' ).val(1);
	});

});