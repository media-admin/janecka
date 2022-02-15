jQuery( function( $ ) {

    $( document ).ajaxComplete( function( ev, jqXHR, settings ) {
        if ( jqXHR != null && jqXHR.hasOwnProperty('responseText') && typeof jqXHR.responseText !== "undefined" ) {
            if (jqXHR.responseText.indexOf( 'product-quick-view-container' ) >= 0) {
                if ( $('.product-lightbox form').hasClass('variations_form') ) {
                    $( '.product-lightbox .variations_form' ).wc_germanized_variation_form();
                    $( '.product-lightbox .variations_form .variations select' ).change();
                    $( '.product-lightbox .variations_form .variations input:radio:checked' ).change();
                }
            }
        }
    });
});