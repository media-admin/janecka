/*global wc_enhanced_select_params */
jQuery( function( $ ) {

    function getEnhancedSelectFormatString() {
        return {
            'language': {
                errorLoading: function() {
                    // Workaround for https://github.com/select2/select2/issues/4355 instead of i18n_ajax_error.
                    return sab_enhanced_select_params.i18n_searching;
                },
                inputTooLong: function( args ) {
                    var overChars = args.input.length - args.maximum;

                    if ( 1 === overChars ) {
                        return storeabill_admin_enhanced_select_params.i18n_input_too_long_1;
                    }

                    return storeabill_admin_enhanced_select_params.i18n_input_too_long_n.replace( '%qty%', overChars );
                },
                inputTooShort: function( args ) {
                    var remainingChars = args.minimum - args.input.length;

                    if ( 1 === remainingChars ) {
                        return storeabill_admin_enhanced_select_params.i18n_input_too_short_1;
                    }

                    return storeabill_admin_enhanced_select_params.i18n_input_too_short_n.replace( '%qty%', remainingChars );
                },
                loadingMore: function() {
                    return storeabill_admin_enhanced_select_params.i18n_load_more;
                },
                maximumSelected: function( args ) {
                    if ( args.maximum === 1 ) {
                        return storeabill_admin_enhanced_select_params.i18n_selection_too_long_1;
                    }

                    return storeabill_admin_enhanced_select_params.i18n_selection_too_long_n.replace( '%qty%', args.maximum );
                },
                noResults: function() {
                    return storeabill_admin_enhanced_select_params.i18n_no_matches;
                },
                searching: function() {
                    return storeabill_admin_enhanced_select_params.i18n_searching;
                }
            }
        };
    }

    try {
        $( document.body )

            .on( 'sab-enhanced-select-init', function() {

                // Regular select boxes
                $( ':input.sab-enhanced-select' ).filter( ':not(.enhanced)' ).each( function() {
                    var select2_args = $.extend({
                        minimumResultsForSearch: 10,
                        allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
                        placeholder: $( this ).data( 'placeholder' )
                    }, getEnhancedSelectFormatString() );

                    $( this ).selectWoo( select2_args ).addClass( 'enhanced' );
                });

                $( ':input.sab-enhanced-select-nostd' ).filter( ':not(.enhanced)' ).each( function() {
                    var select2_args = $.extend({
                        minimumResultsForSearch: 10,
                        allowClear:  true,
                        placeholder: $( this ).data( 'placeholder' )
                    }, getEnhancedSelectFormatString() );

                    $( this ).selectWoo( select2_args ).addClass( 'enhanced' );
                });

                // Ajax customer search boxes
                $( ':input.sab-external-customer-search' ).filter( ':not(.enhanced)' ).each( function() {
                    var handler = $( this ).data( 'handler' );

                    var select2_args = {
                        allowClear:  $( this ).data( 'allow_clear' ) ? true : false,
                        placeholder: $( this ).data( 'placeholder' ),
                        minimumInputLength: $( this ).data( 'minimum_input_length' ) ? $( this ).data( 'minimum_input_length' ) : '1',
                        escapeMarkup: function( m ) {
                            return m;
                        },
                        ajax: {
                            url:         storeabill_admin_enhanced_select_params.ajax_url,
                            dataType:    'json',
                            delay:       1000,
                            data:        function( params ) {
                                return {
                                    term:     params.term,
                                    handler:  handler,
                                    action:   'storeabill_admin_json_search_external_customers',
                                    security: storeabill_admin_enhanced_select_params.search_external_customers_nonce,
                                    exclude:  $( this ).data( 'exclude' )
                                };
                            },
                            processResults: function( data ) {
                                var terms = [];
                                if ( data ) {
                                    $.each( data, function( id, text ) {
                                        terms.push({
                                            id: id,
                                            text: text
                                        });
                                    });
                                }
                                return {
                                    results: terms
                                };
                            },
                            cache: true
                        }
                    };

                    select2_args = $.extend( select2_args, getEnhancedSelectFormatString() );

                    $( this ).selectWoo( select2_args ).addClass( 'enhanced' );

                    if ( $( this ).data( 'sortable' ) ) {
                        var $select = $(this);
                        var $list   = $( this ).next( '.select2-container' ).find( 'ul.select2-selection__rendered' );

                        $list.sortable({
                            placeholder : 'ui-state-highlight select2-selection__choice',
                            forcePlaceholderSize: true,
                            items       : 'li:not(.select2-search__field)',
                            tolerance   : 'pointer',
                            stop: function() {
                                $( $list.find( '.select2-selection__choice' ).get().reverse() ).each( function() {
                                    var id     = $( this ).data( 'data' ).id;
                                    var option = $select.find( 'option[value="' + id + '"]' )[0];
                                    $select.prepend( option );
                                } );
                            }
                        });
                    }
                });
            })

            // WooCommerce Backbone Modal
            .on( 'wc_backbone_modal_before_remove', function() {
                $( '.sab-enhanced-select' ).filter( '.select2-hidden-accessible' )
                    .selectWoo( 'close' );
            })

            .trigger( 'sab-enhanced-select-init' );

        $( 'html' ).on( 'click', function( event ) {
            if ( this === event.target ) {
                $( '.sab-enhanced-select' ).filter( '.select2-hidden-accessible' )
                    .selectWoo( 'close' );
            }
        } );
    } catch( err ) {
        // If select2 failed (conflict?) log the error but don't stop other scripts breaking.
        window.console.log( err );
    }
});
