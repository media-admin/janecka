window.storeabill = window.storeabill || {};
window.storeabill.admin = window.storeabill.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.table = {

        params: {},

        init: function() {
            var self    = storeabill.admin.table;
            self.params = storeabill_admin_table_params;

            $( document )
                .on( 'click', '.sab-document-action-button.document-sync, .sab-document-action-button.document-send', self.onAction );
        },

        onAction: function() {
            var self    = storeabill.admin.table,
                $button = $( this ),
                $column = $button.parents( 'td' );

            if ( ! $button.hasClass( 'loading' ) ) {
                $button.find( '.spinner' ).remove();

                $button.append( '<span class="spinner is-active"></span>' );
                $button.addClass( 'loading' );

                if ( $( '.sab-action-notice-wrapper' ).length <= 0 ) {
                    $( '.wp-list-table' ).before( '<div class="sab-action-notice-wrapper"></div>' );
                } else {
                    $( '.sab-action-notice-wrapper' ).html( '' );
                }

                $.ajax( {
                    type: 'GET',
                    url: $button.attr( 'href' ) + '&display_type=table&do_ajax=yes',
                    dataType: 'json',
                    success: function( response ) {
                        $button.find( '.spinner' ).remove();
                        $button.removeClass( 'loading' );

                        if ( response.success ) {
                            $column.html( response.fragments['.sab-document-actions'] );

                            // Tooltips
                            $( document.body ).trigger( 'sab_init_tooltips' );

                            if ( response.hasOwnProperty( 'messages' ) ) {
                                $.each( response.messages, function( i, message ) {
                                    $( '.sab-action-notice-wrapper' ).append( '<div class="sab-notice notice is-dismissible updated"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
                                });
                            }
                        } else {
                            if ( response.hasOwnProperty( 'messages' ) ) {
                                $.each( response.messages, function( i, message ) {
                                    $( '.sab-action-notice-wrapper' ).append( '<div class="sab-notice notice error is-dismissible updated notice-error"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
                                });
                            }
                        }

                        if ( $( '.sab-action-notice-wrapper' ).find( '.notice' ).length > 0 ) {
                            $( '.sab-action-notice-wrapper' )[0].scrollIntoView({
                                behavior: "smooth",
                                block: "start"
                            });
                        }
                    }
                }).fail( function( response ) {
                    $button.find( '.spinner' ).remove();
                    $button.removeClass( 'loading' );

                    window.console.log( response );
                } );
            }

            return false;
        }
    };

    $( document ).ready( function() {
        storeabill.admin.table.init();
    });

})( jQuery, window.storeabill.admin );
