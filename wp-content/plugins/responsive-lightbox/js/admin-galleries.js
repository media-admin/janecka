( function( $ ) {

	/**
	 * Ready event.
	 */
	$( function() {
		var galleryFrame = null;
		var embedFrame = null;
		var attachmentFrame = null;
		var galleryContainer = $( '.rl-gallery-images' );
		var galleryIds = $( '.rl-gallery-ids' );
		var embedNumber = galleryContainer.find( 'li[data-type="embed"]' ).length;
		var mediaProviders = {};
		var secondaryToolbar = false;

		// activate sortable media gallery
		mediaGallerySortable( galleryContainer, galleryIds, $( 'input[name="rl_gallery[images][menu_item]"]:checked' ).val() );

		// init galleries
		initGalleries();

		/**
		 * Handle navigation menu in the "Gallery Images" tab.
		 */
		$( document ).on( 'change', '.rl-gallery-tab-menu-item', function() {
			var tab = $( this ).closest( '.postbox' ).attr( 'id' ).replace( 'responsive-gallery-', '' );
			var source = $( this ).closest( '.rl-gallery-tab-menu' );
			var container = $( this ).closest( '.inside' ).find( '.rl-gallery-tab-content' );
			var spinner = source.find( '.spinner' );
			var menuItem = $( this ).val();

			// disable nav on ajax
			container.addClass( 'rl-loading-content' );
			source.addClass( 'rl-loading-content' );

			// display spinner
			spinner.fadeIn( 'fast' ).css( 'visibility', 'visible' );

			// post ajax request
			$.post( ajaxurl, {
				action: 'rl-get-menu-content',
				post_id: rlArgsGalleries.post_id,
				tab: tab,
				menu_item: menuItem,
				nonce: rlArgsGalleries.nonce
			} ).done( function( response ) {
				try {
					if ( response.success ) {
						// replace HTML
						container.html( response.data );

						// enable nav after ajax
						container.removeClass( 'rl-loading-content' );
						source.removeClass( 'rl-loading-content' );

						// update gallery data
						galleryFrame = null;
						galleryContainer = $( '.rl-gallery-images' );
						galleryIds = $( '.rl-gallery-ids' );

						// refresh sortable only for media library
						mediaGallerySortable( galleryContainer, galleryIds, menuItem );

						// refresh color picker
						container.find( '.color-picker' ).wpColorPicker();
					} else {
						// @todo
					}
				} catch ( e ) {
					// @todo
				}
			} ).always( function() {
				// hide spinner
				spinner.fadeOut( 'fast' );
			} );
		} );

		/**
		 * Handle navigation menu of tabs.
		 */
		$( document ).on( 'click', '.nav-tab', function( e ) {
			e.preventDefault();

			var anchor = $( this ).attr( 'href' ).substr( 1 );

			// remove active class
			$( '.nav-tab' ).removeClass( 'nav-tab-active' );

			// add active class
			$( this ).addClass( 'nav-tab-active' );

			// hide all normal metaboxes
			$( '#responsive_lightbox_metaboxes-sortables div[id^="responsive-gallery-"]' ).removeClass( 'rl-display-metabox' ).addClass( 'rl-hide-metabox' );

			// display needed metabox
			if ( anchor === '' )
				$( '#responsive-gallery-images' ).addClass( 'rl-display-metabox' ).removeClass( 'rl-hide-metabox' );
			else
				$( '#responsive-gallery-' + anchor ).addClass( 'rl-display-metabox' ).removeClass( 'rl-hide-metabox' );

			$( 'input[name="rl_active_tab"]' ).val( anchor );
		} );

		/**
		 * Mark shortcode.
		 */
		$( '.rl-shortcode' ).on( 'click', function() {
			var number = $( this ).data( 'number' );
			var el = document.getElementsByClassName( 'rl-shortcode' ).item( number );
			var selection = window.getSelection();

			// remove all selections
			selection.removeAllRanges();

			var range = document.createRange();

			// select node
			range.selectNodeContents( el );

			// select content
			selection.addRange( range );
		} );

		/**
		 * Remove image.
		 */
		$( document ).on( 'click', '.rl-gallery-image-remove', function( e ) {
			e.preventDefault();

			// prevent featured images being removed
			if ( $( this ).closest( '.rl-gallery-images-featured' ).length === 1 )
				return false;

			var li = $( this ).closest( 'li.rl-gallery-image' );
			var attachmentIds = getCurrentAttachments( galleryIds, false );

			// remove id
			attachmentIds = _.without( attachmentIds, li.data( 'attachment_id' ) );

			// remove attachment
			li.remove();

			// update attachment ids
			galleryIds.val( _.uniq( attachmentIds ).join( ',' ) );

			return false;
		} );

		/**
		 * Edit image.
		 */
		$( document ).on( 'click', '.rl-gallery-image-edit', function( e ) {
			e.preventDefault();

			var attachmentId = $( this ).closest( 'li.rl-gallery-image' ).data( 'attachment_id' );
			var parsedInt = parseInt( attachmentId );

			// integer id
			if ( parsedInt == attachmentId ) {
				attachmentId = parsedInt;

				// frame already exists?
				if ( attachmentFrame !== null ) {
					attachmentFrame.detach();
					attachmentFrame.dispose();
					attachmentFrame = null;
				}

				// create new frame
				attachmentFrame = wp.media( {
					id: 'rl-edit-attachment-modal',
					frame: 'select',
					uploader: false,
					title: rlArgsGalleries.editAttachment,
					library: {
						post__in: [attachmentId],
						type: rlArgsGalleries.supports.default,
						content: 'browse',
						contentUserSetting: false,
						router: 'browse',
						searchable: false,
						sortable: false,
						multiple: false,
						editable: true
					},
					button: {
						text: rlArgsGalleries.saveChanges
					}
				} ).on( 'open', function() {
					var attachment = wp.media.attachment( attachmentId );
					var selection = attachmentFrame.state().get( 'selection' );

					// set browser mode
					attachmentFrame.content.mode( 'browse' );

					// add classes
					attachmentFrame.$el.closest( '.media-modal' ).addClass( 'rl-edit-modal' );
					attachmentFrame.$el.closest( '.media-frame' ).addClass( 'hide-router' );

					// get attachment
					attachment.fetch();

					// add attachment
					selection.add( attachment );
				} ).on( 'close', function() {
					var selection = attachmentFrame.state().get( 'selection' );

					// unselect attachment to avoid issues with videos
					selection.reset();
				} );

				attachmentFrame.open();
			// embed string
			} else if ( /^e\d+$/.test( attachmentId ) ) {
				// attachmentId = 0;

				// frame already exists?
				if ( attachmentFrame !== null ) {
					attachmentFrame.detach();
					attachmentFrame.dispose();
					attachmentFrame = null;
				}

				// create new frame
				attachmentFrame = wp.media( {
					id: 'rl-edit-attachment-modal',
					frame: 'select',
					uploader: false,
					title: rlArgsGalleries.editEmbed,
					library: {
						post__in: [0]
					},
					button: {
						text: rlArgsGalleries.saveChanges
					}
				} ).on( 'open', function() {
					var attachment = wp.media.attachment( attachmentId );
					var selection = attachmentFrame.state().get( 'selection' );
					var embedItem = $( '.rl-gallery-image[data-attachment_id="' + attachmentId + '"]' );

					// set browser mode
					attachmentFrame.content.mode( 'browse' );

					// add classes
					attachmentFrame.$el.closest( '.media-modal' ).addClass( 'rl-edit-modal' );
					attachmentFrame.$el.closest( '.media-frame' ).addClass( 'hide-router' );

					// get attachment
					attachment.fetch();

					// simulate real attachment
					attachment.id = attachmentId;
					attachment.attributes = {
						id: attachmentId,
						filename: embedItem.find( 'input[data-type="url"]' ).val(),
						dateFormatted: embedItem.find( 'input[data-type="date"]' ).val(),
						width: embedItem.find( 'input[data-type="width"]' ).val(),
						height: embedItem.find( 'input[data-type="height"]' ).val(),
						title: embedItem.find( 'input[data-type="title"]' ).val(),
						description: embedItem.find( 'textarea[data-type="caption"]' ).val(),
						url: embedItem.find( 'input[data-type="url"]' ).val(),
						sizes: {
							thumbnail: {
								width: embedItem.find( 'input[data-type="thumbnail_width"]' ).val(),
								height: embedItem.find( 'input[data-type="thumbnail_height"]' ).val(),
								url: embedItem.find( 'input[data-type="thumbnail_url"]' ).val(),
								orientation: 'landscape'
							}
						},
						type: 'image',
					};

					// add attachment
					selection.add( attachment );

					// update details label
					attachmentFrame.$el.find( '.media-sidebar h2' ).text( rlArgsGalleries.videoDetails );

					var detailsItem = attachmentFrame.$el.find( '.attachment-details' );

					// hide unwanted html
					detailsItem.find( 'p.description' ).hide();
					detailsItem.find( '[data-setting="alt"]' ).hide();
					detailsItem.find( '[data-setting="caption"]' ).hide();
					detailsItem.find( '[data-setting="title"] input' ).prop( 'readonly', false );
					detailsItem.find( '[data-setting="description"] textarea' ).prop( 'readonly', false );

					// get media toolbar
					var toolbar = attachmentFrame.toolbar.get();

					toolbar.primary.$el.find( 'button' ).on( 'click', function() {
						embedItem.find( 'input[data-type="title"]' ).val( detailsItem.find( '[data-setting="title"] input' ).val() );
						embedItem.find( 'textarea[data-type="caption"]' ).val( detailsItem.find( '[data-setting="description"] textarea' ).val() );
					} );
				} );

				attachmentFrame.open();
			}

			return false;
		} );

		/**
		 * Handle changing image status.
		 */
		$( document ).on( 'click', '.rl-gallery-image-status', function( e ) {
			e.preventDefault();

			var li = $( this ).closest( 'li.rl-gallery-image' );

			// active?
			if ( li.hasClass( 'rl-status-active' ) ) {
				li.addClass( 'rl-status-inactive' ).removeClass( 'rl-status-active' );

				// add item
				li.find( '.rl-gallery-exclude' ).val( li.data( 'attachment_id' ) );
			} else {
				li.addClass( 'rl-status-active' ).removeClass( 'rl-status-inactive' );

				// remove item
				li.find( '.rl-gallery-exclude' ).val( '' );
			}

			return false;
		} );

		/**
		 * Handle gallery modal.
		 */
		$( document ).on( 'click', '.rl-gallery-select', function( e ) {
			e.preventDefault();

			// open media frame if already exists
			if ( galleryFrame !== null ) {
				galleryFrame.open();

				return;
			}

			// create the media frame
			galleryFrame = wp.media( {
				title: rlArgsGalleries.textSelectImages,
				multiple: 'add',
				autoSelect: true,
				filters: 'all',
				library: {
					type: rlArgsGalleries.supports.default,
					filters: 'all'
				},
				button: {
					text: rlArgsGalleries.textUseImages
				}
			} ).on( 'content:render', function( view ) {
				if ( view !== null ) {
					// get all selects
					var selects = view.toolbar.secondary.$el.find( 'select.attachment-filters' );

					// fix it only for more then 1 select (default wp)
					if ( selects.length > 1 ) {
						// calculate new width
						var number = parseInt( 100 / selects.length ) - 2;

						$( selects ).each( function( i, el ) {
							$( el ).css( 'width', 'calc(' + number + '% - 12px)' );
						} );
					}
				}
			} ).on( 'open', function() {
				// get media toolbar
				var toolbar = galleryFrame.toolbar.get();

				// add clear button
				toolbar.set( 'rl-clear-selection', {
					style: 'secondary',
					priority: 0,
					text: rlArgsGalleries.clearSelection,
					requires: {
						selection: true
					},
					click: function() {
						var state = this.controller.state();
						var selection = state.get( 'selection' );

						// clear selected images
						selection.reset();

						// reset counter
						toolbar.secondary.$el.find( '.rl-gallery-count' ).text( 0 );
					}
				} );

				var selection = galleryFrame.state().get( 'selection' );
				var attachmentIds = getCurrentAttachments( galleryIds, true );

				if ( ! secondaryToolbar ) {
					toolbar.secondary.$el.append(
						'<div class="media-selection">' +
							'<div class="selection-info">' +
								'<span class="count">' + rlArgsGalleries.selectedImages + ': <span class="rl-gallery-count">' + attachmentIds.length + '</span></span>' +
							'</div>' +
						'</div>'
					);

					secondaryToolbar = true;
				} else
					toolbar.secondary.$el.find( '.rl-gallery-count' ).text( attachmentIds.length );

				// clear selected images
				selection.reset();

				$.each( attachmentIds, function() {
					// prepare attachment
					attachment = wp.media.attachment( this );

					// turn off counters
					selection.off( 'add', toggleSelection );
					selection.off( 'remove', toggleSelection );

					// select attachment
					selection.add( attachment ? [attachment] : [] );
				} );

				var span = toolbar.secondary.$el.find( '.rl-gallery-count' );

				// turn on counters
				selection.on( 'add', toggleSelection, {
					el: span,
					event: 'add'
				} ).on( 'remove', toggleSelection, {
					el: span,
					event: 'remove'
				} );
			} ).on( 'select', function() {
				var selection = galleryFrame.state().get( 'selection' );
				var attachmentIds = getCurrentAttachments( galleryIds, false );
				var selectedIds = [];

				if ( selection ) {
					selection.map( function( attachment ) {
						// fetched attachment? attachment visible in modal
						if ( typeof attachment.id === 'number' ) {
							// add attachment
							selectedIds.push( attachment.id );

							// is image already in gallery?
							if ( $.inArray( attachment.id, attachmentIds ) !== - 1 )
								return;

							// add attachment
							attachmentIds.push( attachment.id );
							attachment = attachment.toJSON();

							// default size
							var size = {
								width: 150,
								height: 150,
								orientation: 'landscape',
								url: attachment.url
							};

							// image?
							if ( attachment.type === 'image' ) {
								// is preview size available?
								if ( attachment.sizes && attachment.sizes.thumbnail ) {
									if ( attachment.sizes.thumbnail.url )
										size.url = attachment.sizes.thumbnail.url;

									if ( attachment.sizes.thumbnail.height )
										size.height = attachment.sizes.thumbnail.height;

									if ( attachment.sizes.thumbnail.width )
										size.width = attachment.sizes.thumbnail.width;

									if ( attachment.sizes.thumbnail.orientation )
										size.orientation = attachment.sizes.thumbnail.orientation;
								}
							// video?
							} else if ( attachment.type === 'video' ) {
								size.url = rlArgsGalleries.videoIcon;

								// is preview size available?
								if ( attachment.thumb ) {
									if ( attachment.thumb.src && attachment.icon !== attachment.thumb.src )
										size.url = attachment.thumb.src;

									if ( attachment.thumb.height )
										size.height = attachment.thumb.height;

									if ( attachment.thumb.width )
										size.width = attachment.thumb.width;

									if ( attachment.thumb.orientation )
										size.orientation = attachment.thumb.orientation;
								// fallback to full size preview image
								} else if ( attachment.image ) {
									if ( attachment.image.src && attachment.icon !== attachment.image.src )
										size.url = attachment.image.src;

									if ( attachment.image.height )
										size.height = attachment.image.height;

									if ( attachment.image.width )
										size.width = attachment.image.width;

									if ( attachment.image.orientation )
										size.orientation = attachment.image.orientation;
								}
							}

							// append new image
							galleryContainer.append( rlArgsGalleries.mediaItemTemplate.replace( /__MEDIA_ID__/g, attachment.id ).replace( /__MEDIA_DATA__/g, getExcludeInput( galleryContainer ) + '<img width="' + size.width + '" height="' + size.height + '" src="' + rlArgsGalleries.thumbnail[0] + '" class="attachment-thumbnail size-thumbnail format-' + size.orientation + '" alt="" />' ).replace( /__MEDIA_STATUS__/g, ' rl-status-active' ).replace( /__MEDIA_TYPE__/g, attachment.type ) );

							// update image src and alt the safe way
							$( 'li[data-attachment_id="' + attachment.id + '"]' ).find( 'img' ).attr( 'alt', attachment.alt ).attr( 'src', size.url );
						// only attachment id? attachment not visible in modal
						} else {
							// add attachment
							selectedIds.push( + attachment.id );

							// is image already in gallery?
							if ( $.inArray( + attachment.id, attachmentIds ) !== - 1 )
								return;

							// add attachment
							attachmentIds.push( + attachment.id );
						}
					} );
				}

				// assign copy of attachment ids
				var copy = attachmentIds;

				for ( var i = 0; i < attachmentIds.length; i ++ ) {
					// unselected image?
					if ( $.inArray( attachmentIds[i], selectedIds ) === - 1 ) {
						// skip embed
						if ( /^e\d+$/.test( attachmentIds[i] ) )
							continue;

						// remove unselected attachment
						galleryContainer.find( 'li.rl-gallery-image[data-attachment_id="' + attachmentIds[i] + '"]' ).remove();

						// update attachment ids
						copy = _.without( copy, attachmentIds[i] );
					}
				}

				// update gallery ids
				galleryIds.val( _.uniq( copy ).join( ',' ) );
			} );

			// open media frame
			galleryFrame.open();
		} );

		// extend Embed
		var RLToolbarEmbed = wp.media.view.Toolbar.Embed;

		wp.media.view.Toolbar.Embed = wp.media.view.Toolbar.Embed.extend( {
			initialize: function() {
				// replace button text
				this.options.text = rlArgsGalleries.embedVideo;

				// call original function
				RLToolbarEmbed.prototype.initialize.apply( this, arguments );
			}
		} );

		// extend EmbedLink
		var RLEmbedLink = wp.media.view.EmbedLink;

		wp.media.view.EmbedLink = wp.media.view.EmbedLink.extend( {
			rlVideoFrameToolbar: null,
			rlDisableButton: function() {
				this.rlVideoFrameToolbar.primary.$el.find( 'button' ).prop( 'disabled', true );
			},
			updateoEmbed: function( model, url ) {
				var youtube = new RegExp( 'https?://((m|www)\.)?youtube\.com/watch.*', 'i' );
				var vimeo = new RegExp( 'https?://(.+\.)?vimeo\.com/.*', 'i' );

				if ( ( rlArgsGalleries.supports.youtube && youtube.test( url ) ) || ( rlArgsGalleries.supports.vimeo && vimeo.test( url ) ) ) {
					this.$el.find( '.rl-embed-video-text' ).hide();
				} else {
					// clear url
					model.set( 'url', '' );

					this.$el.find( '.rl-embed-video-text' ).show();
				}

				// call original function
				RLEmbedLink.prototype.updateoEmbed.apply( this, arguments );
			},
			fetch: function() {
				// no url?
				if ( ! this.controller.state().props.get( 'url' ) )
					return;

				// call original function
				RLEmbedLink.prototype.fetch.apply( this, arguments );

				this.dfd.done( function( response, status, xhr ) {
					// save response for later use
					embedFrame.rlOembedResponse = response;

					this.rlVideoFrameToolbar.primary.$el.find( 'button' ).prop( 'disabled', false );
				} );
			},
			initialize: function() {
				this.rlVideoFrameToolbar = embedFrame.toolbar.get();

				// call original function
				RLEmbedLink.prototype.initialize.apply( this, arguments );

				this.listenTo( this.model, 'change:url', this.rlDisableButton );
			}
		} );

		/**
		 * Handle gallery modal.
		 */
		$( document ).on( 'click', '.rl-gallery-select-videos', function( e ) {
			e.preventDefault();

			// open media frame if already exists
			if ( embedFrame !== null ) {
				embedFrame.open();

				return;
			}

			// create the media frame
			embedFrame = wp.media( {
				frame: 'post',
				state: 'embed',
				type: 'link',
				metadata: {}
			} ).on( 'open', function() {
				// clear response
				embedFrame.rlOembedResponse = {};

				// get content
				var content = embedFrame.content.get();

				// hide setting link text
				content.$( '.setting' ).hide();

				var supportedEmbeds = [];

				if ( rlArgsGalleries.supports.youtube )
					supportedEmbeds.push( 'YouTube' );

				if ( rlArgsGalleries.supports.vimeo )
					supportedEmbeds.push( 'Vimeo' );

				if ( content.$( '.embed-link-settings' ).find( '.rl-embed-video-text' ).length === 0 ) {
					// append text
					content.$( '.embed-link-settings' ).prepend( '<span class="rl-embed-video-text">' + rlArgsGalleries.onlyEmbedProviders.replace( '%s', supportedEmbeds.join( ', ' ) ) + '</span>' );
				}

				// get menu
				var menu = embedFrame.$el;

				// hide menu
				if ( menu.length > 0 )
					$( menu ).addClass( 'hide-menu' );

				// get media toolbar
				var toolbar = embedFrame.toolbar.get();

				// update button
				toolbar.primary.$el.find( 'button' ).prop( 'disabled', true );
			} ).on( 'close', function() {
				var state = embedFrame.state();

				// save url before clearing
				embedFrame.rlSelectedUrl = state.props.get( 'url' );

				// clear url
				state.props.set( 'url', 'http://' );
			} ).on( 'select', function() {
				var attachmentIds = getCurrentAttachments( galleryIds, false );

				// embed id
				var embedId = 'e' + embedNumber;

				// add attachment
				attachmentIds.push( embedId );

				// update gallery ids
				galleryIds.val( _.uniq( attachmentIds ).join( ',' ) );

				// get embed item
				var embedItem = $( rlArgsGalleries.mediaEmbedTemplate.replace( /__EMBED_ID__/g, embedId ) );

				// update embed item values
				embedItem.find( 'input[data-type="url"]' ).val( embedFrame.rlSelectedUrl );

				// check width
				if ( 'width' in embedFrame.rlOembedResponse )
					embedItem.find( 'input[data-type="width"]' ).val( embedFrame.rlOembedResponse.width );
				else
					embedItem.find( 'input[data-type="width"]' ).val( 0 );

				// check height
				if ( 'height' in embedFrame.rlOembedResponse )
					embedItem.find( 'input[data-type="height"]' ).val( embedFrame.rlOembedResponse.height );
				else
					embedItem.find( 'input[data-type="height"]' ).val( 0 );

				// check thumbnail url
				if ( 'thumbnail_url' in embedFrame.rlOembedResponse )
					embedItem.find( 'input[data-type="thumbnail_url"]' ).val( embedFrame.rlOembedResponse.thumbnail_url );
				else
					embedItem.find( 'input[data-type="thumbnail_url"]' ).val( '' );

				// check thumbnail width
				if ( 'thumbnail_width' in embedFrame.rlOembedResponse )
					embedItem.find( 'input[data-type="thumbnail_width"]' ).val( embedFrame.rlOembedResponse.thumbnail_width );
				else
					embedItem.find( 'input[data-type="thumbnail_width"]' ).val( 0 );

				// check thumbnail height
				if ( 'thumbnail_height' in embedFrame.rlOembedResponse )
					embedItem.find( 'input[data-type="thumbnail_height"]' ).val( embedFrame.rlOembedResponse.thumbnail_height );
				else
					embedItem.find( 'input[data-type="thumbnail_height"]' ).val( 0 );

				// check title
				if ( 'title' in embedFrame.rlOembedResponse )
					embedItem.find( 'input[data-type="title"]' ).val( embedFrame.rlOembedResponse.title );
				else
					embedItem.find( 'input[data-type="title"]' ).val( '' );

				// check description
				if ( 'description' in embedFrame.rlOembedResponse )
					embedItem.find( 'textarea[data-type="caption"]' ).text( embedFrame.rlOembedResponse.description );
				else
					embedItem.find( 'textarea[data-type="caption"]' ).text( '' );

				// check upload date
				if ( 'upload_date' in embedFrame.rlOembedResponse )
					embedItem.find( 'input[data-type="date"]' ).val( embedFrame.rlOembedResponse.upload_date );
				else
					embedItem.find( 'input[data-type="date"]' ).val( '' );

				// append new image
				galleryContainer.append(
					rlArgsGalleries.mediaItemTemplate
					.replace( /__MEDIA_ID__/g, embedId )
					.replace( /__MEDIA_DATA__/g, getExcludeInput( galleryContainer ) + embedItem.html() + '<img width="' + embedFrame.rlOembedResponse.thumbnail_width + '" height="' + embedFrame.rlOembedResponse.thumbnail_height + '" src="' + embedFrame.rlOembedResponse.thumbnail_url + '" class="attachment-thumbnail size-thumbnail format-' + ( embedFrame.rlOembedResponse.thumbnail_width > embedFrame.rlOembedResponse.thumbnail_height ? 'landscape' : 'portrait' ) + '" alt="" />' )
					.replace( /__MEDIA_STATUS__/g, ' rl-status-active' )
					.replace( /__MEDIA_TYPE__/g, 'embed' )
				);

				embedNumber++
			} );

			// open media frame
			embedFrame.open();
		} );

		/**
		 * Handle updating gallery preview.
		 */
		$( document ).on( 'click', '.rl-gallery-update-preview, .rl-gallery-preview-pagination a', function( e ) {
			e.preventDefault();

			var click = $( this );
			var type = click.hasClass( 'rl-gallery-update-preview' ) ? 'update' : 'page';
			var menuItem = $( '.rl-gallery-tab-menu-images input:checked' ).val();
			var container = $( '.rl-gallery-tab-inside-images-' + menuItem );
			var spinner = click.closest( 'td' ).find( '.rl-gallery-preview-inside .spinner' );
			var inside = $( this ).closest( '.inside' ).find( '.rl-gallery-tab-content' );
			var disabledContent = inside.find( 'tr[data-field_type="media_preview"]' );
			var queryArgs = {};

			// disable whole content
			inside.addClass( 'rl-loading-content' );

			// enable inside content
			disabledContent.find( '.rl-gallery-content' ).removeClass( 'rl-content-disabled' );
			disabledContent.find( '.rl-gallery-preview-pagination' ).removeClass( 'rl-content-disabled' );

			// pagination?
			if ( type === 'page' ) {
				var content = click.attr( 'href' ).match( 'preview_page/\\d+' );
				var page = 1;

				// get valid page number
				if ( content !== null )
					page = content[0].split( '/' )[1];

				queryArgs['preview_page'] = page;
			} else
				queryArgs['preview_page'] = 1;

			// get all field values
			container.find( 'tr[data-field_type]' ).each( function() {
				var el = $( this );
				var fieldName = el.data( 'field_name' );
				var value = null;

				switch ( el.data( 'field_type' ) ) {
					case 'text':
						value = el.find( 'input' ).val();

						if ( ! value )
							value = '';
						break;

					case 'number':
						value = parseInt( el.find( 'input' ).val() );

						if ( ! value )
							value = 0;
						break;

					case 'taxonomy':
						value = {
							'id': parseInt( el.find( 'select option:selected' ).val() ),
							'children': el.find( 'input[type="checkbox"]' ).prop( 'checked' )
						};

						if ( ! value )
							value = {
								'id': 0,
								'children': false
							};
						break;

					case 'select':
						value = el.find( 'select option:selected' ).val();

						if ( ! value )
							value = '';
						break;

					case 'radio':
						value = el.find( 'input:checked' ).val();

						if ( ! value )
							value = '';
						break;

					case 'multiselect':
						value = el.find( 'select' ).val();

						if ( ! value )
							value = [];
						break;

					case 'hidden':
						// get all response data elements
						var elements = el.find( 'span[class="rl-response-data"]' );

						var currentPage = queryArgs['preview_page'];

						value = {};

						if ( elements.length > 0 ) {
							elements.each( function( i, element ) {
								var subElement = $( element );
								var provider = subElement.data( 'provider' );
								var subName = subElement.data( 'name' )
								var subValue = subElement.data( 'value' );

								if ( ! ( provider in value ) )
									value[provider] = {};

								if ( ! ( provider in mediaProviders ) )
									mediaProviders[provider] = {};

								if ( ! ( currentPage in mediaProviders[provider] ) )
									mediaProviders[provider][currentPage] = {};

								// save response data for every page
								mediaProviders[provider][currentPage][subName] = subValue;

								value[provider][subName] = subValue;
							} );
						}
						break;
				}

				queryArgs[fieldName] = value;
			} );

			// display spinner
			spinner.fadeIn( 'fast' ).css( 'visibility', 'visible' );

			// post ajax request
			$.post( ajaxurl, {
				action: 'rl-get-preview-content',
				post_id: rlArgsGalleries.post_id,
				menu_item: menuItem,
				query: queryArgs,
				preview_type: type,
				excluded: $( '.rl-gallery-exclude' ).map( function( i, elem ) {
					return $( elem ).val();
				} ).get(),
				nonce: rlArgsGalleries.nonce
			} ).done( function( response ) {
				if ( response.success ) {
					container.find( 'tr[data-field_type]' ).each( function() {
						var el = $( this );

						// any response data fields?
						if ( el.data( 'field_type' ) === 'hidden' && el.data( 'field_name' ) === 'response_data' ) {
							var responseData = response.data.response_data;

							// loop through all providers
							for ( var provider in responseData ) {
								if ( responseData.hasOwnProperty( provider ) ) {
									var arguments = responseData[provider];

									// loop through all arguments
									for ( var argument in arguments ) {
										if ( arguments.hasOwnProperty( argument ) ) {
											$( '#rl_images_remote_library_response_data_' + provider + '_' + argument ).data( 'value', arguments[argument] );
										}
									}
								}
							}
						}
					} );

					// update images
					$( '.rl-gallery-images' ).empty().append( response.data.images );

					// update pagination
					$( '.rl-gallery-preview-pagination' ).replaceWith( response.data.pagination );
				} else {
					// @todo
				}
			} ).always( function() {
				// hide spinner
				spinner.fadeOut( 'fast' );

				// enable content
				inside.removeClass( 'rl-loading-content' );

				// enable inside content
				disabledContent.find( '.rl-gallery-content' ).removeClass( 'rl-content-disabled' );
				disabledContent.find( '.rl-gallery-preview-pagination' ).removeClass( 'rl-content-disabled' );
			} );

			return false;
		} );

		/**
		 * Handle remote library search string change.
		 */
		$( document ).on( 'keyup', '#rl-images-remote_library-media_search, #rl-images-featured-number_of_posts, #rl-images-featured-offset, #rl-images-featured-images_per_post', function() {
			disableInsideContent( $( this ) );
		} );

		/**
		 * Handle remote library media provider change.
		 */
		$( document ).on( 'change', '#rl-images-remote_library-media_provider, #rl-images-folders-folder, #rl-images-folders-folder-include-children, #rl-images-featured-orderby, input[name="rl_gallery[images][featured][order]"], input[name="rl_gallery[images][featured][image_source]"], #rl-images-featured-post_type, #rl-images-featured-post_status, #rl-images-featured-post_format, #rl-images-featured-post_term, #rl-images-featured-post_author, #rl-images-featured-page_parent, #rl-images-featured-page_template, #rl-images-featured-number_of_posts, #rl-images-featured-offset, #rl-images-featured-images_per_post', function() {
			disableInsideContent( $( this ) );
		} );
	} );

	/**
	 * Handle featured image change.
	 */
	$( document ).on( 'change', '#postimagediv .inside', function() {
		var el = $( this ).find( 'input[name="rl_gallery_featured_image"]:checked' );
		var value = $( el ).val();

		$( '#postimagediv .inside' ).attr( 'data-featured-type', value );
		$( '.rl-gallery-featured-image-select' ).children( 'div' ).hide();
		$( '.rl-gallery-featured-image-select-' + value ).show();

		// media library
		if ( value === 'id' ) {
			var thumbnail_id = parseInt( $( '#_thumbnail_id' ).attr( 'data-featured-id' ) );

			if ( thumbnail_id > 0 )
				$( '#_thumbnail_id' ).val( thumbnail_id ).attr( 'data-featured-id', - 1 );
			// custom URL or first gallery image
		} else {
			var thumbnail_id = parseInt( $( '#_thumbnail_id' ).val() );

			if ( thumbnail_id > 0 )
				$( '#_thumbnail_id' ).attr( 'data-featured-id', thumbnail_id ).val( - 1 );
		}
	} );

	/**
	 * Reinitialize select2.
	 */
	$( document ).on( 'ajaxComplete', function() {
		initSelect2();
	} );

	/**
	 * Initialize galleries.
	 */
	function initGalleries() {
		initSelect2();

		// color picker
		$( '.rl-gallery-tab-content .color-picker' ).wpColorPicker();

		// make sure HTML5 validation is turned off
		$( 'form#post' ).attr( 'novalidate', 'novalidate' );

		// make sure to dispay images metabox at start
		$( '#responsive-gallery-images' ).show();

		// get gallery metabox
		var metabox = document.getElementById( 'responsive_lightbox_metaboxes-sortables' );

		// observe sortables
		if ( metabox !== null && typeof MutationObserver !== 'undefined' ) {
			function disableFirstButton() {
				$( '#' + $( '.meta-box-sortables:visible:first' ).attr( 'id' ) ).find( '.postbox:visible:first .handle-order-higher' ).attr( 'aria-disabled', 'true' );
			}

			var observer = new MutationObserver( function( mutations ) {
				_.each( mutations, function( mutation ) {
					if ( mutation.attributeName === 'class' ) {
						// get metaboxes
						var target = $( mutation.target );

						if ( target.hasClass( 'ui-sortable' ) && ! target.hasClass( 'ui-sortable-disabled' ) ) {
							setTimeout( function() {
								// disable sortable
								target.sortable( 'disable' );

								// destroy sortable
								target.sortable( 'destroy' );

								// remove all classes
								target.removeClass();

								// find and disable first button
								disableFirstButton();

								// get sortable
								var sortables = $( '.meta-box-sortables' );

								// get instance
								var instance = sortables.sortable( 'instance' );

								// store original stop event function
								var stopSortable = instance.options.stop;

								// set flag
								var allowOriginalStop = true;

								// replace stop event
								sortables.sortable( {
									stop: function() {
										if ( allowOriginalStop ) {
											allowOriginalStop = false;
											stopSortable();
										}

										allowOriginalStop = true;

										// find and disable first button
										disableFirstButton();
									}
								} );

								// handle toggling postboxes
								$( document ).on( 'postbox-toggled', disableFirstButton );

								// handle moving postboxes
								$( '.postbox .handle-order-higher, .postbox .handle-order-lower' ).on( 'click.postboxes', disableFirstButton );

								// stop observer
								observer.disconnect();
							}, 50 );
						}
					}
				} );
			} );

			// start observing
			observer.observe( metabox, {attributes: true} );
		}
	}

	/**
	 * Disable remote library inside content.
	 */
	function disableInsideContent( el ) {
		var container = el.closest( 'table' ).find( 'tr[data-field_type="media_preview"]' );

		// disable content
		container.find( '.rl-gallery-content' ).addClass( 'rl-content-disabled' );
		container.find( '.rl-gallery-preview-pagination' ).addClass( 'rl-content-disabled' );
	}

	/**
	 * Initialize select2.
	 */
	function initSelect2() {
		$( '.rl-gallery-tab-inside select.select2' ).select2( {
			closeOnSelect: true,
			multiple: true,
			width: 300,
			minimumInputLength: 0
		} );
	}

	/**
	 * Get gallery item exclude input.
	 */
	function getExcludeInput( container ) {
		var tabId = $( 'input[name="rl_active_tab"]' ).val();
		var menuItem = $( '.rl-gallery-tab-menu-' + tabId ).find( '.rl-gallery-tab-menu-item:checked' ).val();
		var fieldName = container.closest( 'tr[data-field_name]' ).data( 'field_name' );

		return rlArgsGalleries.mediaExcludeTemplate
			.replace( /__MEDIA_TAB_ID__/g, tabId )
			.replace( /__MEDIA_MENU_ITEM__/g, menuItem )
			.replace( /__MEDIA_FIELD_NAME__/g, fieldName );
	}

	/**
	 * Get attachment IDs.
	 */
	function getCurrentAttachments( ids, onlyAttachments ) {
		var attachments = ids.val();
		var parsedAttachments = [];

		if ( attachments !== '' ) {
			attachments.split( ',' ).forEach( function( val, i ) {
				var parsedInt = parseInt( val );

				// only real attachments?
				if ( onlyAttachments ) {
					// integer ids
					if ( Number.isInteger( parsedInt ) && parsedInt > 0 )
						parsedAttachments.push( parsedInt );
				} else {
					// integer ids
					if ( parsedInt == val )
						parsedAttachments.push( parsedInt );
					// embed strings
					else if ( /^e\d+$/.test( val ) )
						parsedAttachments.push( val );
				}
			} );
		}

		return parsedAttachments;
	}

	/**
	 * Make media gallery sortable.
	 */
	function mediaGallerySortable( gallery, ids, type ) {
		if ( type === 'media' ) {
			// images order
			gallery.sortable( {
				items: 'li.rl-gallery-image',
				cursor: 'move',
				scrollSensitivity: 40,
				forcePlaceholderSize: true,
				forceHelperSize: false,
				helper: 'clone',
				opacity: 0.65,
				placeholder: 'rl-gallery-sortable-placeholder',
				start: function( event, ui ) {
					ui.item.css( 'border-color', '#f6f6f6' );
				},
				stop: function( event, ui ) {
					ui.item.removeAttr( 'style' );
				},
				update: function( event, ui ) {
					var attachmentIds = [];

					gallery.find( 'li.rl-gallery-image' ).each( function() {
						attachmentIds.push( $( this ).attr( 'data-attachment_id' ) );
					} );

					ids.val( _.uniq( attachmentIds ).join( ',' ) );
				}
			} );
		}
	}

	/**
	 * Toggle attachment selection.
	 */
	function toggleSelection() {
		if ( 'event' in this ) {
			var value = parseInt( this.el.text() );

			if ( this.event === 'add' )
				this.el.text( value + 1 );
			else if ( this.event === 'remove' )
				this.el.text( value - 1 );
		}
	}

} )( jQuery );