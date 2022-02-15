window.germanized = window.germanized || {};

( function( $, germanized ) {

	germanized.multistep_checkout_amazon_compatibility = {
		params: {},

		init: function() {
			this.params = wc_gzdp_multistep_amazon_helper_params;

			$( document.body )
				.on( 'wc_gzdp_step_changed', this.onStepChanged )
				.on( 'updated_checkout', this.moveAdditionalFields )
				.on( 'payment_method_selected', this.moveAdditionalFields );

			this.moveAdditionalFields();
		},

		/**
		 * The amazon plugin moves additional field right before the #payment block which
		 * is only visible within the second step - this may lead to errors when switching to step 2
		 * as validation happens during step 1.
		 */
		moveAdditionalFields: function() {
			var self = germanized.multistep_checkout_amazon_compatibility;

			if ( self.isAmazonPayment() ) {
				if ( $( '.wc-gzdp-amazon-billing-fields-wrapper' ).find( '.woocommerce-billing-fields' ).length <= 0 && $( '.woocommerce-billing-fields .woocommerce-billing-fields__field-wrapper > *' ).length > 0 ) {
					$( '.woocommerce-billing-fields' ).appendTo( '.wc-gzdp-amazon-billing-fields-wrapper' );
				}

				if ( $( '.wc-gzdp-amazon-shipping-fields-wrapper' ).find( '.woocommerce-shipping-fields' ).length <= 0 &&  $( '.woocommerce-shipping-fields .woocommerce-shipping-fields__field-wrapper > *' ).length > 0 ) {
					$( '.woocommerce-shipping-fields' ).appendTo( '.wc-gzdp-amazon-shipping-fields-wrapper' );
				}

				if ( $( '.wc-gzdp-amazon-shipping-fields-wrapper' ).find( '.woocommerce-additional-fields' ).length <= 0 ) {
					$( '.woocommerce-additional-fields' ).appendTo( '.wc-gzdp-amazon-additional-fields-wrapper' );
				}
			}
		},

		isAmazonPayment: function() {
			return $( 'input[name=payment_method]' ).length > 0 && $( 'input[name=payment_method]:checked' ).length > 0 && 'amazon_payments_advanced' === $( 'input[name=payment_method]:checked' ).val();
		},

		onStepChanged: function() {
			var self = germanized.multistep_checkout_amazon_compatibility;

			$( '.woocommerce-gzdp-checkout-verify-data .addresses address' ).text( self.params.managed_by );
		},
	};

	$( document ).ready( function() {
		germanized.multistep_checkout_amazon_compatibility.init();
	});

})( jQuery, window.germanized );