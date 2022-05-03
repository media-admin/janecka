window.germanized = window.germanized || {};

( function( $, germanized ) {

    germanized.pro_checkout = {
        params: {},

        init: function() {
            this.params = wc_gzdp_checkout_params;

            $( document )
                .on( 'change', '#billing_vat_id, #shipping_vat_id', this.onChangeVatID )
                .on( 'change', '#billing_company, #shipping_company', this.onChangeCompany )
                .on( 'change', '#ship-to-different-address-checkbox', this.onChangeShipToDifferentAddress )
                .on( 'change', '#billing_postcode, #shipping_postcode', this.onChangePostcode );

            $( document.body )
                .on( 'updated_checkout', this.onUpdatedCheckout )
                .on( 'checkout_error', this.onUpdatedCheckout )
                .on( 'country_to_state_changing', this.onCountryToStateChange );

            this.showOrHideVatIdField();
        },

        onCountryToStateChange: function() {
            var self = germanized.pro_checkout;

            self.onChangePostcode();
        },

        getVatExemptPostcodesByCountry: function( country ) {
            var self = germanized.pro_checkout,
                postcodes = [];

            country = country.toString().toUpperCase();

            if ( self.params.vat_exempt_postcodes.hasOwnProperty( country ) ) {
                return self.params.vat_exempt_postcodes[ country ];
            }

            return postcodes;
        },

        onChangePostcode: function() {
            var thisform       = $( '.woocommerce-checkout' ),
                self           = germanized.pro_checkout,
                $fields        = thisform.find( '#billing_vat_id, #shipping_vat_id' ),
                $postcodefield = thisform.find( '#billing_postcode, #shipping_postcode' ),
                $countryfield  = thisform.find( '#billing_country, #shipping_country' );

            $countryfield.each( function( key, value ) {
                var $field = thisform.find( value );

                if ( $field.length > 0 ) {
                    var fieldPrefix = 'billing';

                    if ( $field.attr( 'id' ).includes( 'shipping' ) ) {
                        fieldPrefix = 'shipping';
                    }

                    var $vatId    = thisform.find( '#' + fieldPrefix + '_vat_id' );
                    var $postcode = thisform.find( '#' + fieldPrefix + '_postcode' );

                    if ( $vatId.length > 0 && $postcode.length > 0 ) {
                        var $parent         = $vatId.closest( '.form-row' ),
                            country         = $field.val(),
                            exemptPostcodes = self.getVatExemptPostcodesByCountry( country ),
                            postcode        = $postcode.val().toString().toUpperCase().trim();

                        postcode = postcode.replace( /[\\s\\-]/, '' );

                        /**
                         * Allow passing a parameter to explicitly allow VAT Ids for UK
                         */
                        if ( 'GB' === country && 'no' === self.params.great_britain_supports_vat_id ) {
                            var postcodeStart = postcode.substring( 0, 2 );

                            if ( 'BT' === postcodeStart ) {
                                $parent.show();
                            } else {
                                $parent.hide();
                            }
                        } else if ( exemptPostcodes.length > 0 ) {
                            var isExempt = false;

                            $.each( exemptPostcodes, function( i, exemptPostcode ) {
                                if ( exemptPostcode.includes( '*' ) ) {
                                    exemptPostcode    = exemptPostcode.replace( '*', '' );
                                    var postcodeStart = postcode.substring( 0, exemptPostcode.length );

                                    if ( exemptPostcode === postcodeStart ) {
                                        isExempt = true;
                                        return false;
                                    }
                                } else if ( exemptPostcode === postcode ) {
                                    isExempt = true;
                                    return false;
                                }
                            } );

                            if ( isExempt ) {
                                $parent.hide();
                            } else {
                                $( document.body ).off( 'country_to_state_changing', self.onCountryToStateChange );

                                var $wrapper = 'shipping' === fieldPrefix ? $('.woocommerce-shipping-fields') : $('.woocommerce-billing-fields');

                                $( document.body ).trigger( 'country_to_state_changing', [ country, $wrapper ] );

                                $( document.body ).on( 'country_to_state_changing', self.onCountryToStateChange );
                            }
                        }
                    }
                }
            });
        },

        onUpdatedCheckout: function( e, data ) {
            var $field      = $( '.woocommerce-checkout' ).find( '#billing_vat_id:visible, #shipping_vat_id:visible' ),
                $errors     = $( '.woocommerce-checkout' ).find( '.woocommerce-error' ),
                hasVatError = false;

            if ( $errors.length > 0 ) {
                var $vatIdError = $errors.find( '[data-id$="vat_id"]' );

                if ( $vatIdError.length > 0 ) {
                    var fieldId = $vatIdError.data( 'id' );
                    $field      = $( '.woocommerce-checkout' ).find( '#' + fieldId );
                    hasVatError = true;
                }
            }

            if ( $field.length > 0 && $field.is( ':input' ) ) {
                var $parent = $field.closest( '.form-row' );

                $parent.removeClass( 'woocommerce-validated woocommerce-invalid' );

                if ( hasVatError ) {
                    $parent.addClass( 'woocommerce-invalid' );
                } else {
                    $parent.addClass( 'woocommerce-validated' );
                }
            }
        },

        validateField: function( $field ) {
            var self = germanized.pro_checkout;

            if ( $field.length > 0 && $field.is( ':input' ) ) {
                var vatId   = $field.val(),
                    $parent = $field.closest( '.form-row' );

                if ( vatId.length > 0 ) {
                    if ( self.validateId( vatId ) ) {
                        $parent.removeClass( 'woocommerce-invalid' );
                    } else {
                        $parent.removeClass( 'woocommerce-validated' );
                    }
                }
            }
        },

        validateId: function( vatId ) {
            return /^(CHE[0-9]{9}|ATU[0-9]{8}|IX([0-9]{9}|[0-9]{12})|BE[01][0-9]{9}|BG[0-9]{9,10}|HR[0-9]{11}|CY[A-Z0-9]{9}|CZ[0-9]{8,10}|DK[0-9]{8}|EE[0-9]{9}|FI[0-9]{8}|FR[0-9A-Z]{2}[0-9]{9}|DE[0-9]{9}|EL[0-9]{9}|HU[0-9]{8}|IE([0-9]{7}[A-Z]{1,2}|[0-9][A-Z][0-9]{5}[A-Z])|IT[0-9]{11}|LV[0-9]{11}|LT([0-9]{9}|[0-9]{12})|LU[0-9]{8}|MT[0-9]{8}|NL[0-9]{9}B[0-9]{2}|PL[0-9]{10}|PT[0-9]{9}|RO[0-9]{2,10}|SK[0-9]{10}|SI[0-9]{8}|ES[A-Z]([0-9]{8}|[0-9]{7}[A-Z])|SE[0-9]{12}|GB([0-9]{9}|[0-9]{12}|GD[0-4][0-9]{2}|HA[5-9][0-9]{2}))$/.test( vatId );
        },

        onChangeVatID: function() {
            var self   = germanized.pro_checkout,
                $field = $( this );

            $( '.woocommerce-error, .woocommerce-message' ).each( function() {
                /**
                 * Do not removes login, coupon toggle messages
                 */
                if ( $( this ).parents( '.woocommerce-form-login-toggle, .woocommerce-form-coupon-toggle' ).length <= 0 ) {
                    $( this ).remove();
                }
            } );

            self.validateField( $field );

            $( 'body' ).trigger( 'update_checkout' );
        },

        onChangeCompany: function() {
            var self = germanized.pro_checkout;

            $( 'body' ).trigger( 'update_checkout' );
        },

        onChangeShipToDifferentAddress: function() {
            var self = germanized.pro_checkout;

            self.showOrHideVatIdField();
        },

        showOrHideVatIdField: function() {
            var self             = germanized.pro_checkout,
                $checkbox        = $( '#ship-to-different-address-checkbox' ),
                $billing_vat_id  = $( '#billing_vat_id' );

            if ( $checkbox.is( ':checked' ) ) {
                // Backup real value
                $billing_vat_id.data( 'field-value', $billing_vat_id.val() );

                // Use placeholder value to make sure billing vat id wont throw empty errors
                $billing_vat_id.val( '' ).parents( '.form-row' ).hide();

                self.onChangeVatID();
            } else {
                if ( ! $billing_vat_id.val() || $billing_vat_id.val() === '1' ) {
                    var oldVal = $billing_vat_id.data( 'field-value' );

                    $billing_vat_id.val( oldVal );
                }

                $billing_vat_id.parents( '.form-row' ).hide();

                var $wrapper    = $('.woocommerce-billing-fields');
                var country     = $( '#billing_country' ).val();

                $( document.body ).trigger( 'country_to_state_changing', [ country, $wrapper ] );
            }
        }
    };

    $( document ).ready( function() {
        germanized.pro_checkout.init();
    });

})( jQuery, window.germanized );