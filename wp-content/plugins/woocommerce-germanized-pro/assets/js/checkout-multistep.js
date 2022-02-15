window.germanized = window.germanized || {};

( function( $, germanized ) {

    /**
     * Order Data Panel
     */
    germanized.multistep_checkout = {

        params: {},

        init: function() {
            this.params = wc_gzd_multistep_checkout_params;
            var self    = germanized.multistep_checkout;

            // Support other Payment Plugins - just add a wrap around their custom payment wrapper
            if ( $( '#payment-manual' ).length ) {
                $( '#payment-manual' ).wrap( '<div id="order-payment"></div>' );
                $( '#order_payment_heading' ).insertBefore( '#payment-manual' );
            } else {
                $( '#payment' ).wrap( '<div id="order-payment"></div>' );
                $( '#order_payment_heading' ).insertBefore( '#payment' );
            }

            if (  $( '#order_review > #order-payment ~ *' ).length === 0 ) {
                $( '#order-payment ~ *' ).wrapAll( '<div id="order-verify"></div>' );
            } else {
                $( '#order_review > #order-payment ~ *' ).wrapAll( '<div id="order-verify"></div>' );
            }

            $( '#order_review_heading' ).prependTo( '#order-verify' );

            $.each( self.params.steps, function( index, elem ) {
                if ( $( elem.selector ).length )  {
                    // Wrap selector with step-wrapper
                    $( elem.selector ).wrap( '<div class="' + self.params.wrapper + ' ' + elem.wrapper_classes.join( ' ' ) +  '" id="step-wrapper-' + elem.id +  '" data-id="' + elem.id +  '"></div>' );

                    if ( elem.submit_html ) {
                        $( '#step-wrapper-' + elem.id ).append( elem.submit_html );
                    }
                }
            });

            $( '.step-wrapper' ).hide();

            $( 'body' ).trigger( 'wc_gzdp_multistep_checkout_init' );

            // Listen to AJAX Events to check whether fragments can be refreshed (data check within last step).
            $( document ).ajaxComplete( self.onAjaxComplete );

            /**
             * Inject step submit attribute before sending AJAX checkout request
             * for better third-party compatibility and to be sure to to track every form submit request.
             */
            $( document ).ajaxSend( self.onAjaxSend );

            $( document )
                .on( 'click', '.step, .step-trigger', self.onClickStep )
                .on( 'change', '.step', self.onChangeStep )
                .on( 'refresh', '.step-wrapper', self.onRefreshStep );

            // Trigger change on first step
            $( '.step-nav li a.step:first' ).trigger( 'change' );
        },

        refreshCurrentStep: function( step ) {
            var self = germanized.multistep_checkout;

            $.post( self.params.ajax_url + '?action=woocommerce_gzdp_multistep_refresh_step', {
                wc_gzdp_multistep_refresh_step : self.params.refresh_step_nonce,
                step                           : step
            }, self.onRefreshCurrentStepSuccess, 'json' );
        },

        onRefreshCurrentStepSuccess: function() {},

        onClickNextStep: function( e ) {
            germanized.multistep_checkout.nextStep( e );
        },

        nextStep: function( e ) {
            var self = germanized.multistep_checkout;

            var $currentStep = self.getCurrentStep();
            var $button = $currentStep.find( '.next-step-button' );

            var next    = $button.data( 'next' ),
                current = $button.data( 'current' );

            if ( $button.parents( '.step-wrapper' ).hasClass( 'no-ajax' ) ) {
                $( '.step-' + next ).trigger( 'change', $( '.step-' + next ) );
                // Stop auto ajax reload
                e.preventDefault();
                e.stopPropagation();
            } else {
                $( document.body ).bind( 'updated_checkout', function() {
                    if ( $( document ).find( '.woocommerce-checkout-payment .blockUI' ).length ) {
                        $( document ).find( '.woocommerce-checkout-payment' ).unblock();
                    }
                });

                // Trigger Wrapper Refresh
                $button.parents( '.step-wrapper' ).trigger( 'refresh' );

                $( 'body' ).bind( 'wc_gzdp_step_refreshed', function() {
                    var hasError = false,
                        $errorWrapper = $( '.woocommerce-error' );

                    /**
                     * Explicitly check whether the error wrapper exists and has children.
                     * Some payment plugins might treat the failure response created by the AJAX
                     * step response manually and try to add (empty) error messages returned by the request via JS.
                     */
                    if ( $errorWrapper.length > 0 && $errorWrapper.children().length > 0 ) {
                        hasError = true;
                    }

                    if ( ! hasError ) {
                        // next step
                        $( '.step-' + next ).trigger( 'change', $( '.step-' + next ) );
                    }

                    $( 'body' ).unbind( 'wc_gzdp_step_refreshed' );
                });
            }
        },

        onRefreshStep: function() {
            if ( $( this ).find( '.step-buttons' ).length ) {
                $( this ).find( '.step-buttons' ).prepend( '<input type="hidden" id="wc-gzdp-step-submit" name="wc_gzdp_step_submit" value="' + $( this ).data( 'id' ) + '" />' );

                $( 'body' ).bind( 'checkout_error', function( e ) {
                    $( '#wc-gzdp-step-submit' ).remove();

                    $( 'body' ).trigger( 'wc_gzdp_step_refreshed' );
                    $( 'body' ).unbind( 'checkout_error' );
                });
            }
        },

        onChangeStep: function( e, elem ) {
            var id   = $( this ).data( 'href' ),
                self = germanized.multistep_checkout;

            self.refreshCurrentStep( id );

            if ( $( '#step-wrapper-' + id ).length ) {

                if ( elem !== undefined ) {
                    $( '.woocommerce-error' ).remove();
                }

                $( '.step-nav' ).find( '.active' ).removeClass( 'active' );
                $( this ).parents( 'li' ).addClass( 'active' );

                $( this ).attr( 'href', '#step-' + $( this ).data( 'href' ) );

                $( '.step-wrapper' ).hide();
                $( '.step-wrapper' ).removeClass( 'step-wrapper-active' );
                $( '#step-wrapper-' + id ).show();
                $( '#step-wrapper-' + id ).addClass( 'step-wrapper-active' );

                /**
                 * Remove invalid classes from last-step elements
                 * to prevent (e.g. checkboxes) from being marked as invalid without submitting.
                 */
                $( '#order-verify' ).find( '.woocommerce-invalid' ).removeClass( 'woocommerce-invalid woocommerce-invalid-required-field' );

                $( 'body' ).removeClass( function ( index, className ) {
                    return ( className.match( /(^|\s)woocommerce-multistep-checkout-active-\S+/g ) || [] ).join(' ');
                });

                window.location.hash = 'step-' + id;

                $( 'body' ).addClass( 'woocommerce-multistep-checkout-active-' + id );
                $( 'body' ).trigger( 'wc_gzdp_step_changed', $( this ) );
            }
        },

        onClickStep: function(  ) {
            if ( ! $( this ).attr( 'href' ) ) {
                return false;
            }

            var step = $( this ).data( 'href' );

            $( 'body' ).trigger( 'wc_gzdp_show_step', $( this ) );
            $( '.step-' + step ).trigger( 'change', $( this ) );
        },

        onAjaxSend: function( ev, jqXHR, settings ) {
            var self = germanized.multistep_checkout;

            if ( wc_checkout_params.checkout_url === settings.url ) {
                var $current = self.getCurrentStep();

                if ( 'order' !== $current.data( 'id' ) ) {
                    settings.data += '&wc_gzdp_step_submit=' + $current.data( 'id' );
                    germanized.multistep_checkout.nextStep( ev );
                }
            }
        },

        getCurrentStep: function() {
            return $( '.step-wrapper-active' ).length > 0 ? $( '.step-wrapper-active' ) : $( '.step-wrapper-1' );
        },

        onAjaxComplete: function( ev, jqXHR, settings ) {
            if ( jqXHR != null && jqXHR.hasOwnProperty( 'responseText' ) ) {
                var response = null;

                try {
                    response = $.parseJSON( jqXHR.responseText );
                } catch ( error ) {
                    response = null;
                }

                if ( response !== null && response.hasOwnProperty( 'fragments' ) ) {
                    // Check if fragment exists in object
                    if ( response.fragments.hasOwnProperty( '.woocommerce-gzdp-checkout-verify-data' ) ) {
                        $( '.woocommerce-gzdp-checkout-verify-data' ).replaceWith( response.fragments['.woocommerce-gzdp-checkout-verify-data'] );
                    }

                    if ( response.fragments.hasOwnProperty( '.step-nav' ) ) {
                        $( '.step-nav' ).replaceWith( response.fragments['.step-nav'] );

                        $( 'ul.step-nav li a' ).each( function() {
                            var id = $( this ).data( 'href' );

                            if ( response.fragments.hasOwnProperty( '.step-buttons-' + id ) ) {
                                $( '.step-buttons-' + id ).replaceWith( response.fragments['.step-buttons-' + id] );
                            }
                        });
                    }
                }
            }
        }
    };

    $( document ).ready( function() {
        germanized.multistep_checkout.init();
    });

})( jQuery, window.germanized );