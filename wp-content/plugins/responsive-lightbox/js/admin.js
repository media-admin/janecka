( function( $ ) {

	// ready event
	$( function() {
		// init native WP color picker
		$( '.responsive-lightbox-settings .color-picker' ).wpColorPicker();

		// reset settings
		$( '.responsive-lightbox-settings input.reset-responsive-lightbox-settings' ).on( 'click', function() {
			return confirm( rlArgsAdmin.resetSettingsToDefaults );
		} );

		// slide toggle media provider options
		$( '.rl-media-provider-expandable' ).on( 'change', function() {
			var active = $( this ),
				options = active.closest( 'td' ).find( '.rl-media-provider-options' );

			if ( active.is( ':checked' ) )
				options.slideDown( 'fast' );
			else
				options.slideUp( 'fast' );
		} );

		// load all previously used taxonomies
		$( document ).on( 'click', '#rl_folders_load_old_taxonomies', function() {
			var select = $( '#rl_media_taxonomy' ),
				spinner = select.parent().find( '.spinner' ),
				taxonomies = [];

			select.find( 'option' ).each( function( i, item ) {
				taxonomies.push( $( item ).val() );
			} );

			// show spinner
			spinner.toggleClass( 'is-active', true );

			$.post( ajaxurl, {
				action: 'rl-folders-load-old-taxonomies',
				taxonomies: taxonomies,
				nonce: rlArgsAdmin.tax_nonce
			} ).done( function( response ) {
				try {
					if ( response.success && response.data.taxonomies.length > 0 ) {
						$.each( response.data.taxonomies, function( i, item ) {
							select.append( $( '<option></option>' ).attr( 'value', item ).text( item ) );
						} );
					} else {
						//@TODO
					}
				} catch ( e ) {
					//@TODO
				}
			} ).always( function() {
				// hide spinner
				spinner.toggleClass( 'is-active', false );
			} );

			return false;
		} );
	} );

} )( jQuery );