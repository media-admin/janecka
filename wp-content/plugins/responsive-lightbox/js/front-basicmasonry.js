( function( $ ) {

	/**
	 * Hook into doResponsiveLightbox event.
	 */
	$( document ).on( 'doResponsiveLightbox', function( event ) {
		// special masonry check
		if ( typeof event.masonry !== 'undefined' && event.masonry === false )
			return false;

		if ( typeof event.pagination_type !== 'undefined' ) {
			// infinite scroll
			if ( event.pagination_type === 'infinite' ) {
				var gallery = event.infinite.gallery;
				var galleryNumber = parseInt( gallery.data( 'gallery_no' ) ) + 1;
				var elements = event.infinite.items;

				if ( typeof window['rlArgsBasicMasonryGallery' + galleryNumber] !== 'undefined' ) {
					var options = window['rlArgsBasicMasonryGallery' + galleryNumber].data;

					// init masonry
					grid = gallery.masonry( {
						itemSelector: '.rl-gallery-item',
						columnWidth: '.rl-grid-sizer',
						percentPosition: true,
						originLeft: options.originLeft,
						originTop: options.originTop
					} );

					// append new images
					grid.append( elements ).masonry( 'appended', elements );

					// layout masonry
					grid.imagesLoaded( function() {
						grid.masonry( 'layout' );

						// trigger event only for standard lightboxes
						if ( rlArgs.scriptExtension !== '1' ) {
							// reinitialize lightbox
							$.event.trigger( {
								type: 'doResponsiveLightbox',
								script: rlArgs.script,
								selector: rlArgs.selector,
								args: rlArgs,
								masonry: false
							} );
						}
					} );
				}
			// ajax
			} else {
				var grid = $( '.rl-gallery-container[data-gallery_id="' + event.gallery_id + '"] .rl-gallery' );
				var galleryNumber = parseInt( grid.data( 'gallery_no' ) ) + 1;

				if ( typeof window['rlArgsBasicMasonryGallery' + galleryNumber] !== 'undefined' ) {
					var options = window['rlArgsBasicMasonryGallery' + galleryNumber].data;

					// init masonry
					grid.masonry( {
						itemSelector: '.rl-gallery-item',
						columnWidth: '.rl-grid-sizer',
						percentPosition: true,
						originLeft: options.originLeft,
						originTop: options.originTop
					} );

					// layout masonry
					grid.imagesLoaded( function() {
						grid.masonry( 'layout' );
					} );
				}
			}
		} else {
			$( '.rl-basicmasonry-gallery' ).each( function() {
				var grid = $( this );
				var galleryNumber = parseInt( grid.data( 'gallery_no' ) ) + 1;

				if ( typeof window['rlArgsBasicMasonryGallery' + galleryNumber] !== 'undefined' ) {
					var options = window['rlArgsBasicMasonryGallery' + galleryNumber].data;

					// init masonry
					grid.masonry( {
						itemSelector: '.rl-gallery-item',
						columnWidth: '.rl-grid-sizer',
						percentPosition: true,
						originLeft: options.originLeft,
						originTop: options.originTop
					} );

					// layout masonry
					grid.imagesLoaded( function() {
						// remove loading class
						grid.parent().removeClass( 'rl-loading' );
						grid.masonry( 'layout' );
					} );
				}
			} );
		}
	} );

} )( jQuery );