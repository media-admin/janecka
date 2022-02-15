( function( $ ) {

	// ready event
	$( function() {
		// cancel deactivation
		$( document ).on( 'click', '.rl-deactivate-plugin-cancel', function( e ) {
			tb_remove();

			return false;
		} );

		// simple deactivation
		$( document ).on( 'click', '.rl-deactivate-plugin-simple', function( e ) {
			// display spinner
			$( '.rl-deactivation-buttons .spinner' ).addClass( 'is-active' );
		} );

		// deactivation with sending data
		$( document ).on( 'click', '.rl-deactivate-plugin-data', function( e ) {
			var spinner = $( '.rl-deactivation-buttons .spinner' ),
				url = $( this ).attr( 'href' );

			// display spinner
			spinner.addClass( 'is-active' );

			// submit data
			$.post( ajaxurl, {
				action: 'rl-deactivate-plugin',
				option_id: $( 'input[name="rl_deactivation_option"]:checked' ).val(),
				other: $( 'textarea[name="rl_deactivation_other"]' ).val(),
				nonce: rlArgsPlugins.nonce
			} ).done( function( response ) {
				// deactivate plugin
				window.location.href = url;
			} ).fail( function() {
				// deactivate plugin
				window.location.href = url;
			} );

			return false;
		} );

		// click on deactivation link
		$( document ).on( 'click', '.rl-deactivate-plugin-modal', function( e ) {
			var modalBox = $( '#rl-deactivation-container' ).closest( '#TB_window' );

			tb_show( rlArgsPlugins.deactivate, '#TB_inline?inlineId=rl-deactivation-modal&modal=false' );

			if ( modalBox.length > 0 ) {
				$( modalBox ).addClass( 'rl-deactivation-modal' );
				$( modalBox ).find( '#TB_closeWindowButton' ).on( 'blur' );
			}

			return false;
		} );

		// change radio
		$( document ).on( 'change', 'input[name="rl_deactivation_option"]', function( e ) {
			if ( $( this ).val() === '6' )
				$( '.rl-deactivation-textarea textarea' ).prop( 'disabled', false );
			else
				$( '.rl-deactivation-textarea textarea' ).prop( 'disabled', true );
		} );
	} );

} )( jQuery );