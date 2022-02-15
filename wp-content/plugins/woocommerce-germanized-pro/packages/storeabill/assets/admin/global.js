window.storeabill = window.storeabill || {};
window.storeabill.admin = window.storeabill.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.global = {

        params: {},

        init: function() {
            var self    = storeabill.admin.global;
            self.params = storeabill_admin_global_params;

            $( document )
                .on( 'click', 'a.sab-toggle', this.onInputToogleClick )
                .on( 'click', '.sab-notice .notice-dismiss', this.onNoticeDismiss );

            $( document.body )
                .on( 'sab_init_tooltips', this.initTipTip );

            // Tooltips
            $( document.body ).trigger( 'sab_init_tooltips' );
        },

        onNoticeDismiss: function() {
            if ( $( this ).parents( '.notice' ).length > 0 ) {
                var $notice = $( this ).parents( '.notice' );

                $notice.slideUp( 150, function() {
                    $notice.remove();
                });

                return false;
            }
        },

        initTipTip: function() {
            $( '.sab-tip' ).tipTip( {
                'attribute': 'data-tip',
                'fadeIn': 50,
                'fadeOut': 50,
                'delay': 200
            } );
        },

        onInputToogleClick: function() {
            var self      = storeabill.admin.global,
                $link     = $( this ),
                $toggle   = $link.find( 'span.sab-input-toggle' ),
                $row      = $toggle.parents( 'fieldset' ),
                $checkbox = $row.find( 'input[type=checkbox]' ),
                isEnabled  = $toggle.hasClass( 'sab-input-toggle--enabled' );

            if ( $link.hasClass( 'sab-toggle-ajax' ) ) {
                $toggle.addClass( 'sab-input-toggle--loading' );

                var data = {
                    action  : $link.data( 'action' ),
                    security: $link.data( 'nonce' ),
                    enable  : ! isEnabled,
                    id      : $link.data( 'id' ),
                    data    : $link.data()
                };

                $.ajax( {
                    url      : self.params.ajax_url,
                    data     : data,
                    dataType : 'json',
                    type     : 'POST',
                    success:  function( response ) {
                        $toggle.removeClass( 'sab-input-toggle--enabled sab-input-toggle--disabled' );
                        $toggle.removeClass( 'sab-input-toggle--loading' );

                        if ( true === response.data ) {
                            $toggle.addClass( 'sab-input-toggle--enabled' );
                        } else {
                            $toggle.addClass( 'sab-input-toggle--disabled' );
                        }

                        $( document.body ).trigger( 'storeabill_ajax_toggle_updated', [ $link, response, data.id, data ] );
                    }
                } );
            } else {
                $toggle.removeClass( 'sab-input-toggle--enabled sab-input-toggle--disabled' );

                if ( $checkbox.length > 0 ) {
                    if ( isEnabled ) {
                        $checkbox.prop( 'checked', false );
                    } else {
                        $checkbox.prop( 'checked', true );
                    }

                    $checkbox.trigger( 'change' );
                }

                if ( isEnabled ) {
                    $toggle.addClass( 'sab-input-toggle--disabled' );
                } else {
                    $toggle.addClass( 'sab-input-toggle--enabled' );
                }

                $( document.body ).trigger( 'storeabill_toggle_updated', [ $toggle, ! isEnabled ] );
            }

            return false;
        },
    };

    $( document ).ready( function() {
        storeabill.admin.global.init();
    });

})( jQuery, window.storeabill.admin );
