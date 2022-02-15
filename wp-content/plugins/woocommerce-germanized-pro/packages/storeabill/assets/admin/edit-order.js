window.storeabill = window.storeabill || {};
window.storeabill.admin = window.storeabill.admin || {};

( function( $, admin ) {

    /**
     * Core
     */
    admin.edit_order = {

        params: {},
        invoices: {},
        cancellations: {},
        $wrapper: false,
        needsSaving: false,
        needsFixation: false,

        init: function() {
            var self            = storeabill.admin.edit_order;
            self.params         = storeabill_admin_edit_order_params;
            self.$wrapper       = $( '#sab-order-invoices' );

            self.initTiptip();

            // Listen to AJAX Events to allow running actions after Woo saved/added/removed order items.
            $( document ).ajaxComplete( self.onAjaxComplete );

            $( document )
                .on( 'click', '#sab-order-invoices #sab-order-invoice-sync', self.onSync )
                .on( 'click', '#sab-order-invoices #sab-order-invoice-finalize', self.onFinalize )
                .on( 'click', '#sab-order-invoices #sab-order-invoice-list .document-delete', self.onDeleteDocument )
                .on( 'click', '#sab-order-invoices #sab-order-invoice-list .document-refresh', self.onRefreshDocument )
                .on( 'click', '#sab-order-invoices #sab-order-invoice-list .document-cancel', self.onCancelInvoice )
                .on( 'click', '#sab-order-invoices #sab-order-invoice-list .document-send', self.onSendDocument )
                .on( 'click', '#sab-order-invoices #sab-order-invoice-list .sab-document-action-button:not(.document-delete, .document-refresh, .document-cancel, .document-send)[target="_self"]', self.onAction )
                .on( 'click', '#sab-order-invoices .notice-dismiss', self.onRemoveNotice );

            $( document.body ).on( 'storeabill_ajax_toggle_updated', self.onPaymentStatusUpdate );
        },

        onAction: function() {
            var self      = storeabill.admin.edit_order,
                $document = $( this ).parents( '.order-document' ),
                url       = $( this ).attr( 'href' );

            self.$wrapper.find( '.notice-wrapper' ).empty();
            self.block();

            $.ajax({
                type: 'GET',
                url:  url + '&display_type=order&do_ajax=yes',
                success: function( data ) {
                    if ( data.success ) {
                        $document.find( '.document-actions' ).html( data.fragments['.sab-document-actions'] );

                        self.$wrapper = $( '#sab-order-invoices' );
                        self.initTiptip();

                        if ( data.hasOwnProperty( 'message' ) ) {
                            self.addNotice( data.message, 'success' );
                        } else if( data.hasOwnProperty( 'messages' ) ) {
                            $.each( data.messages, function( i, message ) {
                                self.addNotice( message, 'success' );
                            });
                        }

                        self.unblock();
                    } else {
                        self.onAjaxError( data );
                    }
                },
                error: function( data ) {
                    self.onAjaxError( data );
                },
                dataType: 'json'
            });

            return false;
        },

        onActionSuccess: function( data ) {
            var self      = storeabill.admin.edit_order,
                $document = self.getDocument( data.document_id );

            self.unblock();
        },

        onPaymentStatusUpdate: function( e, $linkToggle, response, documentId ) {
            var self = storeabill.admin.edit_order;

            if ( $linkToggle.hasClass( 'sab-toggle-invoice-payment-status' ) ) {
                if ( response.success ) {
                    self.onAjaxSuccess( response );
                } else {
                    self.onAjaxError( response );
                }
            }
        },

        onSendDocument: function() {
            var self      = storeabill.admin.edit_order,
                $document = $( this ).parents( '.order-document' ),
                id        = $document.data( 'document-id' );

            self.sendDocument( id );

            return false;
        },

        sendDocument: function( document_id ) {
            var self = storeabill.admin.edit_order;

            var params = {
                'action'     : 'send_document',
                'document_id': document_id
            };

            self.block();
            self.doAjax( params );
        },

        onCancelInvoice: function() {
            var self      = storeabill.admin.edit_order,
                $document = $( this ).parents( '.order-document' ),
                id        = $document.data( 'document-id' );

            var answer = window.confirm( self.getParams().i18n_cancel_invoice_notice );

            if ( answer ) {
                self.cancelInvoice( id );
            }

            return false;
        },

        cancelInvoice: function( document_id ) {
            var self = storeabill.admin.edit_order;

            var params = {
                'action'     : 'cancel_invoice',
                'document_id': document_id
            };

            self.block();
            self.doAjax( params );
        },

        onRefreshDocument: function() {
            var self      = storeabill.admin.edit_order,
                $document = $( this ).parents( '.order-document' ),
                id        = $document.data( 'document-id' );

            self.refreshDocument( id );

            return false;
        },

        refreshDocument: function( document_id ) {
            var self = storeabill.admin.edit_order;

            var params = {
                'action'     : 'refresh_document',
                'document_id': document_id
            };

            self.block();
            self.doAjax( params );
        },

        onDeleteDocument: function() {
            var self      = storeabill.admin.edit_order,
                $document = $( this ).parents( '.order-document' ),
                id        = $document.data( 'document-id' );

            var answer = window.confirm( self.getParams().i18n_delete_document_notice );

            if ( answer ) {
                self.deleteDocument( id );
            }

            return false;
        },

        deleteDocument: function( document_id ) {
            var self = storeabill.admin.edit_order;

            var params = {
                'action'     : 'delete_document',
                'document_id': document_id
            };

            self.block();
            self.doAjax( params, self.onDeleteDocumentSuccess );
        },

        onDeleteDocumentSuccess: function( data ) {
            var self      = storeabill.admin.edit_order,
                $document = self.getDocument( data.document_id );

            if ( $document.length > 0 ) {
                $document.slideUp( 100 );

                setTimeout( function() {
                    self.onAjaxSuccess( data );
                }, 150 );
            } else {
                self.onAjaxSuccess( data );
            }
        },

        onAjaxComplete: function( e, jqXHR, settings ) {
            var self = storeabill.admin.edit_order;

            if ( jqXHR != null ) {

                if ( settings.hasOwnProperty( 'data' ) ) {
                    var search = settings.data;
                    var data   = false;

                    try {
                        data = JSON.parse('{"' + search.replace(/&/g, '","').replace(/=/g,'":"') + '"}', function( key, value ) { return key==="" ? value:decodeURIComponent( value ) });
                    } catch (e) {
                        data = false;
                    }

                    if ( data && data.hasOwnProperty( 'action' ) ) {
                        var action = data.action;

                        if (
                            'woocommerce_save_order_items' === action
                            || 'woocommerce_remove_order_item' === action
                            || 'woocommerce_add_order_item' === action
                            || 'woocommerce_delete_refund' === action
                            || 'woocommerce_calc_line_taxes' === action
                            || 'woocommerce_remove_order_tax' === action
                            || 'woocommerce_add_order_shipping' === action
                            || 'woocommerce_add_order_fee' === action
                            || 'woocommerce_remove_order_coupon' === action
                            || 'woocommerce_add_coupon_discount' === action
                            || 'woocommerce_load_order_items' === action
                        ) {
                            self.sync( self.hasInvoices() );
                        }
                    }
                }
            }
        },

        hasInvoices: function() {
            var self = storeabill.admin.edit_order;

            return self.$wrapper.find( '.order-document' ).length > 0;
        },

        onSync: function( e ) {
            var self = storeabill.admin.edit_order;

            e.preventDefault();
            self.sync();
            return false;
        },

        onFinalize: function( e ) {
            var self = storeabill.admin.edit_order;

            e.preventDefault();
            self.finalize();
            return false;
        },

        finalize: function() {
            var self = storeabill.admin.edit_order;

            self.block();

            var params = {
                'action' : 'order_finalize',
            };

            self.doAjax( params );
        },

        sync: function( addNew = true ) {
            var self = storeabill.admin.edit_order;

            self.block();

            var params = {
                'action' : 'order_sync',
                'add_new': addNew ? 1 : 0,
            };

            self.doAjax( params );
        },

        block: function() {
            var self = storeabill.admin.edit_order;

            self.$wrapper.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        },

        unblock: function() {
            var self = storeabill.admin.edit_order;

            self.$wrapper.unblock();
        },

        getData: function( additionalData ) {
            var self = storeabill.admin.edit_order,
                data = {};

            additionalData = additionalData || {};

            $.each( self.$wrapper.find( ':input[name]' ).serializeArray(), function( index, item ) {
                if ( item.name.indexOf( '[]' ) !== -1 ) {
                    item.name = item.name.replace( '[]', '' );
                    data[ item.name ] = $.makeArray( data[ item.name ] );
                    data[ item.name ].push( item.value );
                } else {
                    data[ item.name ] = item.value;
                }
            });

            $.extend( data, additionalData );

            return data;
        },

        doAjax: function( params, cSuccess, cError ) {
            var self             = storeabill.admin.edit_order,
                url              = self.params.ajax_url,
                type             = 'POST';
                $wrapper         = self.$wrapper;

            $wrapper.find( '.notice-wrapper' ).empty();

            cSuccess = cSuccess || self.onAjaxSuccess;
            cError   = cError || self.onAjaxError;

            if ( ! params.hasOwnProperty( 'security' ) ) {
                params['security'] = self.params.edit_documents_nonce;
            }

            if ( ! params.hasOwnProperty( 'order_id' ) ) {
                params['order_id'] = self.params.order_id;
            }

            if ( ! params.action.includes( "storeabill_woo_admin_" ) ) {
                params.action = 'storeabill_woo_admin_' + params.action;
            }

            params = self.getData( params );

            $.ajax({
                type: 'POST',
                url:  url,
                data: params,
                success: function( data ) {
                    if ( data.success ) {
                        cSuccess.apply( $wrapper, [ data ] );
                    } else {
                        cError.apply( $wrapper, [ data ] );
                    }
                },
                error: function( data ) {
                    cError.apply( $wrapper, [ data ] );
                },
                dataType: 'json'
            });
        },

        refreshFragments: function( fragments ) {
            var self = storeabill.admin.edit_order;

            if ( fragments ) {
                $.each( fragments, function ( key, value ) {
                    $( key ).replaceWith( value );
                    $( key ).unblock();
                } );
            }

            self.$wrapper = $( '#sab-order-invoices' );
            self.initTiptip();
        },

        onAjaxError: function( data ) {
            var self = storeabill.admin.edit_order;

            if ( data.hasOwnProperty( 'fragments' ) ) {
                self.refreshFragments( data.fragments );
            }

            if ( data.hasOwnProperty( 'message' ) ) {
                self.addNotice( data.message, 'error' );
            } else if( data.hasOwnProperty( 'messages' ) ) {
                $.each( data.messages, function( i, message ) {
                    self.addNotice( message, 'error' );
                });
            }

            self.unblock();
        },

        onAjaxSuccess: function( data ) {
            var self = storeabill.admin.edit_order;

            if ( data.hasOwnProperty( 'fragments' ) ) {
                self.refreshFragments( data.fragments );
            }

            if ( data.hasOwnProperty( 'message' ) ) {
                self.addNotice( data.message, 'success' );
            } else if( data.hasOwnProperty( 'messages' ) ) {
                $.each( data.messages, function( i, message ) {
                    self.addNotice( message, 'success' );
                });
            }

            self.unblock();
        },

        onRemoveNotice: function() {
            $( this ).parents( '.notice' ).slideUp( 150, function() {
                $( this ).remove();
            });
        },

        addNotice: function( message, noticeType ) {
            var self = storeabill.admin.edit_order;

            self.$wrapper.find( '.notice-wrapper' ).append( '<div class="notice is-dismissible notice-' + noticeType +'"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>' );
        },

        getParams: function() {
            var self = storeabill.admin.edit_order;

            return self.params;
        },

        getDocument: function( documentId ) {
            return $( '#sab-order-invoice-list' ).find( '#sab-document-' + documentId );
        },

        documentExists: function( documentId ) {
            var self = storeabill.admin.edit_order;

            return self.getDocument( documentId ).length > 0;
        },

        initTiptip: function() {
            var self = storeabill.admin.edit_order;

            // Tooltips
            $( document.body ).trigger( 'sab_init_tooltips' );
        }
    };

    $( document ).ready( function() {
        storeabill.admin.edit_order.init();
    });

})( jQuery, window.storeabill.admin );
