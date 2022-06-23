( function( $ ) {

	ResponsiveLightboxGallery = {
		modal: null,
		lastGalleryID: 0,
		lastGalleryImage: '',
		currentGalleryID: 0,
		resetFilters: false,
		galleries: {},
		gutenberg: false,
		primaryButtonClass: '',
		secondaryButtonClass: '',

		/**
		 * Initialize galleries.
		 */
		init: function() {
			this.gutenberg = typeof rlBlockEditor !== 'undefined';
			this.searchGalleries = _.debounce( this.getGalleries, 500 ),
			this.bindEvents();
			this.setButtons();
		},

		/**
		 * Search galleries.
		 */
		searchGalleries: function() {},

		/**
		 * Set modal buttons.
		 */
		setButtons: function() {
			if ( this.gutenberg ) {
				this.primaryButtonClass = '.rl-media-button-select-gallery';
				this.secondaryButtonClass = '.rl-media-button-insert-gallery';
			} else {
				this.primaryButtonClass = '.rl-media-button-insert-gallery';
				this.secondaryButtonClass = '.rl-media-button-select-gallery';
			}
		},

		/**
		 * Get modal primary button.
		 */
		getModalButton: function() {
			return this.modal[0].getElementsByClassName( 'rl-media-button-select-gallery' )[0];
		},

		/**
		 * Open modal.
		 */
		open: function( galleryID ) {
			if ( typeof galleryID === 'undefined' )
				var galleryID = 0;

			var phrase = '';

			$( this.primaryButtonClass ).show();
			$( this.secondaryButtonClass ).hide();

			// reset filters?
			if ( this.resetFilters ) {
				phrase = '';

				// clear searh input
				$( '#rl-media-search-input' ).val( '' );

				// reset categories
				this.modal.find( '#rl-media-attachment-categories' ).val( 0 );
			} else
				phrase = $( '#rl-media-search-input' ).val();

			// display modal
			this.modal.show();

			// fix columns
			this.setColumns();

			// get galleries
			this.getGalleries( phrase, galleryID );
		},

		/**
		 * Close modal.
		 */
		close: function( event ) {
			event.preventDefault();

			this.modal.hide();
		},

		/**
		 * Calculate column width.
		 */
		setColumns: function() {
			var list = this.modal.find( '.rl-galleries-list' );
			var listWidth = list.width();
			var content = this.modal.find( '.media-frame-content' );
			var columns = parseInt( content.attr( 'data-columns' ) );
			var oldColumns = newColumns = columns;

			if ( listWidth ) {
				// get sidebar width
				var width = this.modal.find( '.media-sidebar' ).outerWidth() + 'px';

				// set attachment list new width
				list.css( 'right', width );

				// do the same for primary toolbar
				this.modal.find( '.attachments-browser .media-toolbar' ).css( 'right', width );

				// calculate new columns number
				newColumns = Math.min( Math.round( listWidth / 170 ), 12 ) || 1;

				// set new columns number
				if ( ! oldColumns || oldColumns !== newColumns )
					content.attr( 'data-columns', newColumns );
			}
		},

		/**
		 * Click gallery event handler.
		 */
		handleClickGallery: function( event ) {
			event.preventDefault();

			var gallery = $( event.target ).closest( 'li' );

			// set current gallery id
			this.currentGalleryID = parseInt( gallery.data( 'id' ) );

			// clicked different gallery?
			if ( this.lastGalleryID !== this.currentGalleryID ) {
				gallery.parent().find( 'li' ).removeClass( 'selected details' );

				this.lastGalleryID = this.currentGalleryID;

				// get full source image
				var fullSource = gallery.find( '.centered' ).data( 'full-src' );

				// invalid full source image?
				if ( fullSource === '' )
					this.lastGalleryImage = gallery.find( 'img' ).first().attr( 'src' );
				else
					this.lastGalleryImage = fullSource;

				gallery.addClass( 'selected details' );

				this.clickGallery( this.currentGalleryID, false );
			} else {
				// already selected?
				if ( gallery.hasClass( 'selected details' ) ) {
					// unselect gallery
					this.currentGalleryID = 0;

					gallery.removeClass( 'selected details' );

					this.clickGallery( this.currentGalleryID, true );
				} else {
					gallery.addClass( 'selected details' );

					this.clickGallery( this.currentGalleryID, false );
				}
			}
		},

		/**
		 * Load gallery thumbnails from cache or via AJAX.
		 */
		clickGallery: function( gallery_id, toggle ) {
			var _this = this;

			_this.modal.find( '.media-selection' ).toggleClass( 'empty', toggle );
			_this.modal.find( this.primaryButtonClass ).prop( 'disabled', toggle );

			// load gallery preview images?
			if ( ! toggle ) {
				// clear images
				_this.modal.find( '.rl-attachments-list' ).empty();

				if ( _this.galleries[gallery_id].inProgress ) {
					// display spinner
					_this.toggleSpinner( true );

					return;
				}

				// load cached images
				if ( _this.galleries[gallery_id].ready ) {
					// hide spinner
					_this.toggleSpinner( false );

					// update images
					_this.updateGalleryPreview( _this.galleries[gallery_id].data, false );
				// get images for the first time
				} else {
					// display spinner
					_this.toggleSpinner( true );

					// set in progress flag
					_this.galleries[gallery_id].inProgress = true;

					$.post( ajaxurl, {
						action: 'rl-post-gallery-preview',
						post_id: rlArgsGallery.post_id,
						gallery_id: gallery_id,
						page: rlArgsGallery.page,
						nonce: rlArgsGallery.nonce
					} ).done( function( response ) {
						try {
							if ( response.success ) {
								// store gallery data
								_this.galleries[gallery_id].data = response.data;

								// set ready flag
								_this.galleries[gallery_id].ready = true;

								// same gallery?
								if ( _this.currentGalleryID === gallery_id ) {
									// update gallery data
									_this.updateGalleryPreview( _this.galleries[gallery_id].data, true );
								}
							} else {
								// set ready flag
								_this.galleries[gallery_id].ready = false;
							}
						} catch( e ) {
							// set ready flag
							_this.galleries[gallery_id].ready = false;
						}
					} ).fail( function() {
						// set ready flag
						_this.galleries[gallery_id].ready = false;
					} ).always( function() {
						// set in progress flag
						_this.galleries[gallery_id].inProgress = false;

						// same gallery?
						if ( _this.currentGalleryID === gallery_id ) {
							// hide spinner
							_this.toggleSpinner( false );
						}
					} );
				}
			}
		},

		/**
		 * Select gallery (block editor).
		 */
		selectGallery: function( event ) {
			event.preventDefault();

			if ( $( this ).attr( 'disabled' ) )
				return;

			this.modal.hide();
		},

		/**
		 * Insert gallery (classic editor).
		 */
		insertGallery: function( event ) {
			event.preventDefault();

			if ( $( this ).attr( 'disabled' ) )
				return;

			var shortcode = '[rl_gallery id="' + this.lastGalleryID + '"]';
			var editor = tinyMCE.get( 'content' );

			if ( editor && ! editor.isHidden() )
				editor.execCommand( 'mceInsertContent', false, shortcode );
			else
				wp.media.editor.insert( shortcode );

			this.modal.hide();
		},

		/**
		 * Load galleries.
		 */
		getGalleries: function( search, galleryID ) {
			var modal = this.modal;
			var spinner = $( '.rl-gallery-reload-spinner' );
			var galleries = modal.find( '.rl-galleries-list' );
			var _this = this;

			// clear galleries
			galleries.empty();

			// hide gallery info
			modal.find( '.media-selection' ).addClass( 'empty' );

			// clear images
			modal.find( '.rl-attachments-list' ).empty();

			// display spinner
			spinner.fadeIn( 'fast' );

			// get galleries
			$.post( ajaxurl, {
				action: 'rl-post-get-galleries',
				post_id: rlArgsGallery.post_id,
				search: search,
				page: rlArgsGallery.page,
				nonce: rlArgsGallery.nonce,
				category: _this.resetFilters ? 0 : modal.find( '#rl-media-attachment-categories' ).val()
			} ).done( function( response ) {
				try {
					if ( response.success ) {
						if ( response.data.html !== '' ) {
							modal.find( '.rl-no-galleries' ).hide();
							modal.find( '.rl-galleries-list' ).empty().append( response.data.html );

							// set up galleries
							response.data.galleries.forEach( function( gallery_id ) {
								_this.galleries[gallery_id] = {
									'inProgress': false,
									'ready': false,
									'data': {}
								};
							} );

							// select gallery
							if ( galleryID !== 0 )
								galleries.find( 'li[data-id="' + galleryID + '"] .js--select-attachment' ).trigger( 'click' );
						} else
							modal.find( '.rl-no-galleries' ).show();
					} else {
						//
					}
				} catch( e ) {
					//
				}
			} ).always( function() {
				// hide spinner
				spinner.fadeOut( 'fast' );
			} );
		},

		/**
		 * Toggle spinner.
		 */
		toggleSpinner: function( display ) {
			var spinner = this.modal.find( '.rl-gallery-images-spinner' );
			var info = this.modal.find( '.selection-info' );

			if ( display ) {
				// display spinner
				spinner.fadeIn( 'fast' ).css( 'visibility', 'visible' );

				// turn off info
				info.addClass( 'rl-loading-content' );
			} else {
				// hide spinner
				spinner.fadeOut( 'fast' );

				// turn on info
				info.removeClass( 'rl-loading-content' );
			}
		},

		/**
		 * Clear and load maximum 20 gallery thumbnails.
		 */
		updateGalleryPreview: function( gallery, animate ) {
			// update gallery attachments
			this.modal.find( '.rl-attachments-list' ).empty().append( gallery.attachments ).fadeOut( 0 ).delay( animate? 'fast' : 0 ).fadeIn( 0 );

			// update number of images in gallery
			this.modal.find( '.rl-gallery-count' ).text( gallery.count );

			// update gallery edit link
			if ( gallery.edit_url !== '' )
				this.modal.find( '.rl-edit-gallery-link' ).removeClass( 'hidden' ).attr( 'href', gallery.edit_url );
			else
				this.modal.find( '.rl-edit-gallery-link' ).addClass( 'hidden' ).attr( 'href', '' );
		},

		/**
		 * Reload galleries.
		 */
		reloadGalleries: function( event ) {
			event.preventDefault();

			// hide "no galleries" box
			this.modal.find( '.rl-no-galleries' ).hide();

			// reset galleries
			this.galleries = {};

			// reset filters
			this.resetFilters = false;

			// load galleries
			this.getGalleries( $( '#rl-media-search-input' ).val(), 0 );
		},

		/**
		 * Bind all events.
		 */
		bindEvents: function() {
			var _this = this;

			// add gallery
			$( document ).on( 'click', '#rl-insert-modal-gallery-button', function( e ) { _this.open( 0 ); } );

			// ready event
			$( function() {
				_this.modal = $( '#rl-modal-gallery' );

				// search galleries
				_this.modal.on( 'keyup', '#rl-media-search-input', function() {
					_this.searchGalleries( $( this ).val() );
				} );

				// reload galleries
				_this.modal.on( 'click', '.rl-reload-galleries', function( e ) {
					_this.reloadGalleries( e );
				} );

				// change category
				_this.modal.on( 'change', '#rl-media-attachment-categories', function( e ) {
					_this.reloadGalleries( e );
				} );

				// close gallery
				_this.modal.on( 'click', '.media-modal-close, .media-modal-backdrop, .rl-media-button-cancel-gallery', function( e ) {
					_this.close( e );
				} );

				// click gallery
				_this.modal.on( 'click', '.rl-galleries-list li .js--select-attachment, .rl-galleries-list li button', function( e ) {
					_this.handleClickGallery( e );
				} );

				// insert gallery (classic editor)
				_this.modal.on( 'click', '.rl-media-button-insert-gallery', function( e ) {
					_this.insertGallery( e );
				} );

				// select gallery (block editor)
				_this.modal.on( 'click', '.rl-media-button-select-gallery', function( e ) {
					_this.selectGallery( e );
				} );

				// resize window
				$( window ).on( 'resize', function() {
					_this.setColumns();
				} );
			} );
		}
	}

	ResponsiveLightboxGallery.init();

} )( jQuery );