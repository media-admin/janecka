window.storeabill = window.storeabill || {};
window.storeabill.admin = window.storeabill.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.templates = {

        params: {},

        init: function() {
            var self    = storeabill.admin.templates;
            self.params = storeabill_admin_templates_params;

            $( document )
                .on( 'click', '.sab-document-templates a.delete', self.onDelete )
                .on( 'click', '.sab-document-templates a.copy', self.onCopy )
                .on( 'click', '.sab-document-templates a.create-first-page', self.onCreateFirstPage )
                .on( 'click', '.sab-document-templates a.sab-add-template', self.onAddTemplate )
                .on( 'click', '.sab-add-editor-template-content a.sab-add-template', self.onAddEditorTemplate )
                .on( 'click', '.sab-add-editor-template-content .sab-editor-template-preview', self.onChangeEditorTemplate )
                .on( 'change', '.sab-document-templates .sab-document-template-default', self.onChangeDefault );
        },

        onChangeEditorTemplate: function() {
            var self    = storeabill.admin.templates,
                $parent = $( this ).parents( '.sab-editor-template-choose' );

            $parent.find( '.active' ).removeClass( 'active' );
            $( this ).addClass( 'active' );

            return false;
        },

        onAddEditorTemplate: function() {
            var self         = storeabill.admin.templates,
                documentType = $( this ).parents( '.sab-add-editor-template-content' ).data( 'document-type' ),
                template     = $( this ).parents( '.sab-add-editor-template-content' ).find( '.sab-editor-template-preview.active' ).data( 'template' );

            self.addTemplate( documentType, template );

            return false;
        },

        addTemplate: function( documentType, template = '' ) {
            var self = storeabill.admin.templates;

            self.doAjax({
                'action'       : 'create_document_template',
                'template'     : template,
                'document_type': documentType
            });

            $( '#TB_closeWindowButton' ).trigger( 'click' );
        },

        onAddTemplate: function() {
            var self = storeabill.admin.templates;

            self.addTemplate( self.getDocumentType() );

            return false;
        },

        onChangeDefault: function() {
            var self       = storeabill.admin.templates,
                templateId = $( this ).val();

            self.doAjax({
                'action'       : 'update_default_document_template',
                'id'           : templateId,
                'document_type': self.getDocumentType()
            });

            return false;
        },

        getDocumentType: function() {
            var self = storeabill.admin.templates;

            return self.getWrapper().data( 'document-type' );
        },

        getTemplateId: function( $this ) {
            return $this.parents( '.sab-document-template' ).data( 'id' );
        },

        onDelete: function() {
            var self = storeabill.admin.templates,
                id   = self.getTemplateId( $( this ) );

            var answer = window.confirm( self.params.i18n_delete_template_notice );

            if ( answer ) {
                self.doAjax({
                    'action': 'delete_document_template',
                    'id'    : id
                });
            }

            return false;
        },

        onCopy: function() {
            var self = storeabill.admin.templates,
                id   = self.getTemplateId( $( this ) );

            self.doAjax({
                'action': 'copy_document_template',
                'id'    : id
            });

            return false;
        },

        onCreateFirstPage: function() {
            var self = storeabill.admin.templates,
                id   = self.getTemplateId( $( this ) );

            self.doAjax({
                'action': 'create_document_template_first_page',
                'id'    : id
            });

            return false;
        },

        doAjax: function( params ) {
            var self     = storeabill.admin.templates,
                url      = self.params.ajax_url;

            self.getWrapper().find( '.notice-wrapper' ).empty();
            self.block();

            if ( ! params.hasOwnProperty( 'security' ) ) {
                params['security'] = self.params.edit_templates_nonce;
            }

            if ( ! params.action.includes( "storeabill_admin_" ) ) {
                params.action = 'storeabill_admin_' + params.action;
            }

            $.ajax({
                type: "POST",
                url:  url,
                data: params,
                success: function( data ) {
                    if ( data.success ) {
                        self.refreshDOM( data.fragments );
                        self.addNotices( data.messages, 'success' );
                        self.unblock();
                    } else {
                        self.refreshDOM( data.fragments );
                        self.addNotices( data.messages, 'error' );
                        self.unblock();
                    }
                },
                error: function( data ) {},
                dataType: 'json'
            });
        },

        addNotices: function( messages, type ) {
            var self = storeabill.admin.templates;

            $.each( messages, function( i, message ) {
                self.addNotice( message, type );
            });
        },

        addNotice: function( message, noticeType ) {
            var self = storeabill.admin.templates;

            self.getWrapper().find( '.notice-wrapper' ).append( '<div class="notice sab-notice is-dismissible notice-' + noticeType +'"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
        },

        getWrapper: function() {
            return $( '.sab-document-templates' );
        },

        block: function() {
            var self = storeabill.admin.templates;

            self.getWrapper().block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },

        unblock: function() {
            var self = storeabill.admin.templates;

            self.getWrapper().unblock();
        },

        refreshDOM: function( fragments ) {
            if ( fragments ) {
                $.each( fragments, function ( key, value ) {
                    $( key ).replaceWith( value );
                } );

                $( document.body ).trigger( 'sab_init_tooltips' );
            }
        },

        onSync: function() {
            var self    = storeabill.admin.table,
                $button = $( this );

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
                    url: $button.attr( 'href' ),
                    dataType: 'json',
                    success: function( response ) {
                        $button.find( '.spinner' ).remove();
                        $button.removeClass( 'loading' );

                        if ( response.success ) {
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
                    window.console.log( response );
                } );
            }

            return false;
        },
    };

    $( document ).ready( function() {
        storeabill.admin.templates.init();
    });

})( jQuery, window.storeabill.admin );
