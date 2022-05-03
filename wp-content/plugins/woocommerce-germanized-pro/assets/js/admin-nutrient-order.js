window.germanized = window.germanized || {};
window.germanized.admin = window.germanized.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.nutrient_order = {

        params: {},

        init: function () {
            var self                 = germanized.admin.nutrient_order,
                sortable_terms_table = $( '.wp-list-table tbody' );

            self.params = wc_gzdp_nutrient_order;

            sortable_terms_table.sortable( {

                // Settings
                items:     '> tr:not(.no-items)',
                cursor:    'move',
                axis:      'y',
                cancel:    '.inline-edit-row',
                distance:  2,
                opacity:   0.9,
                tolerance: 'pointer',
                scroll:    true,

                /**
                 * Sort start
                 *
                 * @param {event} e
                 * @param {element} ui
                 * @returns {void}
                 */
                start: function ( e, ui ) {

                    if ( typeof ( inlineEditTax ) !== 'undefined' ) {
                        inlineEditTax.revert();
                    }

                    ui.placeholder.height( ui.item.height() );
                    ui.item.parent().parent().addClass( 'dragging' );
                },

                /**
                 * Sort dragging
                 *
                 * @param {event} e
                 * @param {element} ui
                 * @returns {void}
                 */
                helper: function ( e, ui ) {

                    ui.children().each( function() {
                        jQuery( this ).width( jQuery( this ).width() );
                    } );

                    return ui;
                },

                /**
                 * Sort dragging stopped
                 *
                 * @param {event} e
                 * @param {element} ui
                 * @returns {void}
                 */
                stop: function ( e, ui ) {
                    ui.item.children( '.row-actions' ).show();
                    ui.item.parent().parent().removeClass( 'dragging' );
                },

                /**
                 * Update the data in the database based on UI changes
                 *
                 * @param {event} e
                 * @param {element} ui
                 * @returns {void}
                 */
                update: function ( e, ui ) {
                    sortable_terms_table.sortable( 'disable' ).addClass( 'to-updating' );

                    ui.item.addClass( 'to-row-updating' );

                    var strlen     = 4,
                        termid     = ui.item[0].id.substr( strlen ),
                        levelClass = ui.item[0].className.match( /level-\d+/ ) ? ui.item[0].className.match( /level-\d+/ )[0] : 'level-0',
                        prevterm   = ui.item.prevAll( "." + levelClass ),
                        prevtermid = false,
                        level      = levelClass.match(/\d+$/)[0];

                    if ( prevterm.length > 0 ) {
                        prevtermid = prevterm.attr( 'id' ).substr( strlen );
                    }

                    var nexttermid = false,
                        nextterm   = ui.item.nextAll( "." + levelClass );

                    if ( nextterm.length > 0 ) {
                        nexttermid = nextterm.attr( 'id' ).substr( strlen );
                    }

                    // Go do the sorting stuff via ajax
                    $.post( self.params.ajax_url, {
                        action: 'reorder_nutrient_terms',
                        security: self.params.reorder_nutrient_nonce,
                        id:     termid,
                        previd: prevtermid,
                        nextid: nexttermid
                    }, self.onTermOrderUpdate );
                }
            } );
        },

        onTermOrderUpdate: function( response ) {
            var self                 = germanized.admin.nutrient_order,
                sortable_terms_table = $( '.wp-list-table tbody' );

            if ( 'children' === response ) {
                window.location.reload();
                return;
            }

            $.each( response.new_pos, function( key, value ) {

                if ( 'next' === key ) {
                    return;
                }

                var $inline_key = $( '#inline_' + key );

                if ( $inline_key.length > 0 ) {
                    var $dom_order = $inline_key.find( '.nutrient-order' );

                    if ( undefined !== value.order ) {
                        if ( $dom_order.length > 0 ) {
                            $dom_order.html( value.order );
                        }

                        var $dom_term_parent = $inline_key.find( '.parent' );

                        if ( $dom_term_parent.length > 0 ) {
                            $dom_term_parent.html( value.order );
                        }
                    } else if ( $dom_order.length > 0 ) {
                        $dom_order.html( value );
                    }
                }
            } );

            if ( response.next ) {
                $.post( self.params.ajax_url, {
                    action:  'reorder_nutrient_terms',
                    security: self.params.reorder_nutrient_nonce,
                    id:       response.next['id'],
                    previd:   response.next['previd'],
                    nextid:   response.next['nextid'],
                    start:    response.next['start'],
                    excluded: response.next['excluded']
                }, self.onTermOrderUpdate );
            } else {
                setTimeout( function() {
                    $( '.to-row-updating' ).removeClass( 'to-row-updating' );
                }, 500 );

                sortable_terms_table.removeClass( 'to-updating' ).sortable( 'enable' );
            }
        }
    };

    $( document ).ready( function() {
        germanized.admin.nutrient_order.init();
    });

})( jQuery, window.germanized.admin );
