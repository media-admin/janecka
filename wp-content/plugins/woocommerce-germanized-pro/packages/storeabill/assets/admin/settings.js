window.storeabill = window.storeabill || {};
window.storeabill.admin = window.storeabill.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.settings = {

        params: {},
        timeout: null,

        init: function() {
            var self    = storeabill.admin.settings;
            self.params = storeabill_admin_settings_params;

            $( document )
                .on( 'click', '.sab-oauth-disconnect-button', this.onSyncHandlerDisconnect )
                .on( 'click', '.sab-oauth-refresh-button, .sab-oauth-button', this.maybeShowCode )
                .on( 'change keydown paste input', '.sab-oauth-wrapper .authorization-code input[type=text]', this.onChangeCode )
                .on( 'click', '.sab-oauth-submit-code', this.onSubmitCode )
                .on( 'change', '.sab-input-unblock input[type=checkbox]', this.onEnableEditMode )
                .on( 'change keydown paste input', '.sab-number-preview-trigger', this.onPreviewDocumentNumber );
        },

        onPreviewDocumentNumber: function() {
            var self     = storeabill.admin.settings,
                $wrapper = $( '.sab-number-preview' );

            clearTimeout( self.timeout );

            self.timeout = setTimeout( function() {
                $wrapper.addClass( 'loading' );

                var params = {
                    'last_number'    : $( '.sab-number-preview-last-number' ).val(),
                    'document_type'  : $wrapper.data( 'document-type' ),
                    'number_min_size': $( '.sab-number-preview-number-min-size' ).val(),
                    'number_format'  : $( '.sab-number-preview-number-format' ).val(),
                    'security'       : self.params.preview_number_nonce,
                    'action'         : 'storeabill_admin_preview_formatted_document_number'
                };

                $.ajax({
                    type: "POST",
                    url:  self.params.ajax_url,
                    data: params,
                    success: function( data ) {
                        $wrapper.removeClass( 'loading' );

                        if ( data.success ) {
                            $wrapper.find( '.sab-number' ).html( data.preview );
                        }
                    },
                    error: function( data ) {},
                    dataType: 'json'
                });
            }, 500 );
        },

        onEnableEditMode: function() {
            var $parent = $( this ).parents( '.sab-input-unblock-wrapper' );

            if ( $( this ).is( ':checked' ) ) {
                $parent.find( 'input.sab-input-to-unblock' ).prop( 'disabled', false );
            } else {
                $parent.find( 'input.sab-input-to-unblock' ).prop( 'disabled', true );
            }

            return false;
        },

        onChangeCode: function() {
            if ( $( this ).val().length > 0 ) {
                $( this ).parents( '.authorization-code' ).find( '.sab-oauth-submit-code' ).show();
            } else {
                $( this ).parents( '.authorization-code' ).find( '.sab-oauth-submit-code' ).hide();
            }
        },

        onSubmitCode: function( e ) {
            e.preventDefault();

            $( this ).parents( 'form' ).find( '[type=submit]' ).trigger( 'click' );
            
            return false;
        },

        onSyncHandlerDisconnect: function( e ) {
            var self    = storeabill.admin.settings,
                $this   = $( this ),
                $parent = $this.parents( '.sab-oauth-connected' ),
                $input  = $parent.find( 'input.sab-oauth-disconnect-input' );

            e.preventDefault();

            var answer = window.confirm( self.params.i18n_oauth_disconnect_notice );

            if ( answer ) {
                $input.val( 'yes' );
                $this.parents( 'form' ).find( '[type=submit]' ).trigger( 'click' );
            }
        },

        maybeShowCode: function( e ) {
            var self    = storeabill.admin.settings,
                $this   = $( this ),
                $parent = $this.parents( '.sab-oauth-wrapper' );

            if ( $parent.find( '.authorization-code' ).length > 0 ) {
                $parent.find( '.authorization-code' ).show();
            }
        }
    };

    $( document ).ready( function() {
        storeabill.admin.settings.init();
    });

})( jQuery, window.storeabill.admin );
