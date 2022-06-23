( function( $ ) {

	// parse query string
	var parseQueryString = function( name, str ) {
		var regex = new RegExp( '[?&]' + name.replace( /[\[\]]/g, '\\$&' ) + '(=([^&#]*)|&|#|$)' );
		var results = regex.exec( '&' + str );

		return ( ! results || ! results[2] ? '' : decodeURIComponent( results[2].replace( /\+/g, ' ' ) ) );
	}

	// observe DOM changes
	var observeContentChanges = function( el, onlyAdded, callback ) {
		if ( typeof MutationObserver !== 'undefined' ) {
			// define a new observer
			var observer = new MutationObserver( function( mutations, observer ) {
				if ( onlyAdded ) {
					if ( mutations[0].addedNodes.length )
						callback();
				} else {
					if ( mutations[0].addedNodes.length || mutations[0].removedNodes.length )
						callback();
				}
			} );

			// have the observer observe for changes in children
			observer.observe( el, { childList: true, subtree: true } );
		}
	};

	// ready event
	$( function() {
		initPlugin();
	} );

	// custom events trigger
	$( document ).on( rlArgs.customEvents, function() {
		initPlugin();
	} );

	function initPlugin() {
		var containers = [];

		// check for infinite galleries
		$( '.rl-gallery-container' ).each( function() {
			var container = $( this );

			// is it ifinite scroll gallery?
			if ( container.hasClass( 'rl-pagination-infinite' ) )
				containers.push( container );
			// remove loading class
			else
				container.removeClass( 'rl-loading' );
		} );

		// any infinite galleries?
		if ( containers.length > 0 ) {
			for ( var i = 0; i < containers.length; i++ ) {
				var container = containers[i];
				var gallery = container.find( '.rl-gallery' );
				var galleryId = parseInt( container.data( 'gallery_id' ) );
				var galleryScrollType = container.find( '.rl-pagination-bottom' ).data( 'button' );
				var galleryButton = typeof galleryScrollType !== 'undefined' && galleryScrollType === 'manually';

				// initialize infinite scroll
				gallery.infiniteScroll( {
					path: '.rl-gallery-container[data-gallery_id="' + galleryId + '"] .rl-pagination-bottom .next',
					append: '.rl-gallery-container[data-gallery_id="' + galleryId + '"] .rl-gallery-item',
					status: false,
					hideNav: '.rl-gallery-container[data-gallery_id="' + galleryId + '"] .rl-pagination-bottom',
					prefill: ! galleryButton,
					loadOnScroll: true,
					scrollThreshold: galleryButton ? false : 400,
					button: galleryButton ? '.rl-gallery-container[data-gallery_id="' + galleryId + '"] .rl-load-more' : false,
					debug: true,
					history: false,
					responseBody: 'text',
					onInit: function() {
						// infinite with button?
						if ( container.hasClass( 'rl-pagination-infinite' ) && galleryButton ) {
							// remove loading class
							container.removeClass( 'rl-loading' );
						}

						// store gallery ID for append event
						var _galleryId = galleryId;

						// request event
						this.on( 'request', function() {
							// add loading class
							container.addClass( 'rl-loading' );
						} );

						// append event
						this.on( 'append', function( body, path, items, response ) {
							// remove loading class
							container.removeClass( 'rl-loading' );

							$.event.trigger( {
								type: 'doResponsiveLightbox',
								script: rlArgs.script,
								selector: rlArgs.selector,
								args: rlArgs,
								pagination_type: 'infinite',
								gallery_id: _galleryId,
								masonry: gallery.hasClass( 'rl-masonry-gallery' ) || gallery.hasClass( 'rl-basicmasonry-gallery' ),
								infinite: {
									gallery: gallery,
									body: body,
									items: items,
									response: response
								}
							} );
						} );
					}
				} );
			}
		}

		// initialize event
		$.event.trigger( {
			type: 'doResponsiveLightbox',
			script: rlArgs.script,
			selector: rlArgs.selector,
			args: rlArgs
		} );
	}

	// pagination
	$( document ).on( 'click', '.rl-pagination a.page-numbers', function( e ) {
		var link = $( this );
		var container = link.closest( '.rl-gallery-container' );

		// ajax type pagination?
		if ( container.hasClass( 'rl-pagination-ajax' ) ) {
			e.preventDefault();
			e.stopPropagation();

			var galleryId = container.data( 'gallery_id' );
			var galleryNo = container.find( '.rl-gallery' ).data( 'gallery_no' );

			// add loading class
			container.addClass( 'rl-loading' );

			$.post( rlArgs.ajaxurl, {
				action: 'rl-get-gallery-page-content',
				gallery_id: galleryId,
				gallery_no: galleryNo,
				post_id: rlArgs.postId,
				page: parseQueryString( 'rl_page', link.prop( 'href' ) ),
				nonce: rlArgs.nonce,
				preview: rlArgs.preview,
				lightbox: rlArgs.script
			} ).done( function( response ) {
				// replace container with new content
				container.replaceWith( $( response ).removeClass( 'rl-loading' ) );

				// trigger main event
				$.event.trigger( {
					type: 'doResponsiveLightbox',
					script: rlArgs.script,
					selector: rlArgs.selector,
					args: rlArgs,
					pagination_type: 'ajax',
					gallery_id: galleryId,
					gallery_no: galleryNo
				} );
			} ).always( function() {
				container.removeClass( 'rl-loading' );
			} );

			return false;
		}
	} );

	// this is similar to the WP function add_action();
	$( document ).on( 'doResponsiveLightbox', function( event ) {
		if ( typeof event.masonry !== 'undefined' && event.masonry === true )
			return false;

		var script = event.script;
		var selector = event.selector;
		var args = event.args;

		if ( typeof script === 'undefined' || typeof selector === 'undefined' )
			return false;

		rl_view_image = function( script, url ) {
			$.event.trigger( {
				type: 'doLightboxViewImage',
				script: script,
				url: url
			} );
		}

		rl_hide_image = function( script, url ) {
			$.event.trigger( {
				type: 'doLightboxHideImage',
				script: script,
				url: url
			} );
		}

		// WooCommerce 3.0+ compatibility
		setTimeout( function() {
			var flex = $( '.flex-viewport' );

			if ( args.woocommerce_gallery === '1' ) {
				var gallery = $( '.woocommerce-product-gallery' );

				if ( gallery.find( '.woocommerce-product-gallery__trigger' ).length === 0 ) {
					gallery.prepend( '<a href="#" class="woocommerce-product-gallery__trigger">🔍</a>' );

					gallery.on( 'click', '.woocommerce-product-gallery__trigger', function( e ) {
						e.preventDefault();
						e.stopPropagation();

						if ( script === 'lightgallery' ) {
							if ( flex.length ) {
								var image = flex.find( '.flex-active-slide a[data-rel] img' );
								var linkId = flex.find( '.flex-active-slide a[data-rel]' ).data( 'lg-id' );

								image.trigger( 'click.lgcustom-item-' + linkId );
							} else {
								var link = gallery.find( 'a[data-rel]' ).first();
								var image = link.find( 'img' );

								image.trigger( 'click.lgcustom-item-' + link.data( 'lg-id' ) );
							}
						} else if ( script === 'fancybox_pro' ) {
							if ( flex.length ) {
								var index = flex.find( '.flex-active-slide' ).index();
								var imageId = flex.find( '.flex-active-slide a[data-rel]' ).data( 'fancybox' );

								Fancybox.fromOpener( '[data-fancybox="' + imageId + '"]', {
									startIndex: index
								} );
							} else {
								var link = gallery.find( 'a[data-rel]' ).first();

								Fancybox.fromOpener( '[data-fancybox="' + link.data( 'fancybox' ) + '"]', {
									startIndex: 0
								} );
							}
						} else {
							if ( flex.length )
								flex.find( '.flex-active-slide a[data-rel]' ).trigger( 'click' );
							else
								gallery.find( 'a[data-rel]' ).first().trigger( 'click' );
						}
					} );
				}
			}
		}, 10 );

		// initialize lightbox
		switch ( script ) {
			case 'swipebox':
				var slide = $( '#swipebox-overlay' ).find( '.slide.current' );
				var imageSource = '';
				var allowHide = false;
				var closeExecuted = false;

				$( 'a[rel*="' + selector + '"], a[data-rel*="' + selector + '"]' ).swipebox( {
					useCSS: ( args.animation === '1' ? true : false ),
					useSVG: ( args.useSVG === '1' ? true : false ),
					hideCloseButtonOnMobile: ( args.hideCloseButtonOnMobile === '1' ? true : false ),
					removeBarsOnMobile: ( args.removeBarsOnMobile === '1' ? true : false ),
					hideBarsDelay: ( args.hideBars === '1' ? parseInt( args.hideBarsDelay ) : 0 ),
					videoMaxWidth: parseInt( args.videoMaxWidth ),
					loopAtEnd: ( args.loopAtEnd === '1' ? true : false ),
					afterOpen: function() {
						closeExecuted = false;

						// update current slide container
						slide = $( '#swipebox-overlay' ).find( '.slide.current' );

						// get image source
						var image = slide.find( 'img' ).attr( 'src' );

						// valid image source?
						if ( typeof image !== 'undefined' ) {
							imageSource = image;

							// trigger image view
							rl_view_image( script, imageSource );
						} else
							imageSource = '';

						// add current slide observer
						observeContentChanges( document.getElementById( 'swipebox-slider' ), false, function() {
							if ( imageSource === '' ) {
								// get image source
								var image = slide.find( 'img' ).attr( 'src' );

								// valid image source?
								if ( typeof image !== 'undefined' ) {
									imageSource = image;

									// trigger image view
									rl_view_image( script, imageSource );
								} else
									imageSource = '';
							}
						} );
					},
					nextSlide: function() {
						// update current slide container
						slide = $( '#swipebox-overlay' ).find( '.slide.current' );

						// get image source
						var image = slide.find( 'img' ).attr( 'src' );

						// valid image source?
						if ( typeof image !== 'undefined' ) {
							imageSource = image;

							// trigger image view
							rl_view_image( script, imageSource );
						} else
							imageSource = '';
					},
					prevSlide: function() {
						// update current slide container
						slide = $( '#swipebox-overlay' ).find( '.slide.current' );

						// get image source
						var image = slide.find( 'img' ).attr( 'src' );

						// valid image source?
						if ( typeof image !== 'undefined' ) {
							imageSource = image;

							// trigger image view
							rl_view_image( script, imageSource );
						} else
							imageSource = '';
					},
					afterClose: function() {
						// afterClose event executed
						closeExecuted = true;

						// allow to hide image?
						if ( allowHide ) {
							// trigger image hide
							rl_hide_image( script, imageSource );

							allowHide = false;
						}
					}
				} );

				// additional event to prevent rl_hide_image to execure while opening modal
				$( window ).on( 'resize', function() {
					if ( ! closeExecuted ) {
						allowHide = true;
					}
				} );
				break;

			case 'prettyphoto':
				var viewDisabled = false;
				var lastImage = '';

				$( 'a[rel*="' + selector + '"], a[data-rel*="' + selector + '"]' ).each( function() {
					var el = $( this );
					var title = el.data( 'rl_title' );
					var caption = el.data( 'rl_caption' );

					if ( ! title )
						title = '';

					if ( ! caption )
						caption = '';

					// set description
					el.attr( 'title', caption );

					// set title
					el.find( 'img' ).attr( 'alt', title );
				} );

				$( 'a[rel*="' + selector + '"], a[data-rel*="' + selector + '"]' ).prettyPhoto( {
					hook: 'data-rel',
					animation_speed: args.animationSpeed,
					slideshow: ( args.slideshow === '1' ? parseInt( args.slideshowDelay ) : false ),
					autoplay_slideshow: ( args.slideshowAutoplay === '1' ? true : false ),
					opacity: args.opacity,
					show_title: ( args.showTitle === '1' ? true : false ),
					allow_resize: ( args.allowResize === '1' ? true : false ),
					allow_expand: ( args.allowExpand === '1' ? true : false ),
					default_width: parseInt( args.width ),
					default_height: parseInt( args.height ),
					counter_separator_label: args.separator,
					theme: args.theme,
					horizontal_padding: parseInt( args.horizontalPadding ),
					hideflash: ( args.hideFlash === '1' ? true : false ),
					wmode: args.wmode,
					autoplay: ( args.videoAutoplay === '1' ? true : false ),
					modal: ( args.modal === '1' ? true : false ),
					deeplinking: ( args.deeplinking === '1' ? true : false ),
					overlay_gallery: ( args.overlayGallery === '1' ? true : false ),
					keyboard_shortcuts: ( args.keyboardShortcuts === '1' ? true : false ),
					social_tools: ( args.social === '1' ? '<div class="pp_social"><div class="twitter"><a href="//twitter.com/share" class="twitter-share-button" data-count="none">Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script></div><div class="facebook"><iframe src="//www.facebook.com/plugins/like.php?locale=en_US&href=' + location.href + '&amp;layout=button_count&amp;show_faces=true&amp;width=500&amp;action=like&amp;font&amp;colorscheme=light&amp;height=23" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:500px; height:23px;" allowTransparency="true"></iframe></div></div>' : '' ),
					ie6_fallback: true,
					changepicturecallback: function() {
						// is view disabled?
						if ( viewDisabled ) {
							// enable view
							viewDisabled = false;

							return;
						}

						lastImage = $( '#pp_full_res' ).find( 'img' ).attr( 'src' );

						// trigger image view
						rl_view_image( script, lastImage );

						// is expanding allowed?
						if ( args.allowExpand === '1' ) {
							// disable changepicturecallback event after expanding
							$( 'a.pp_expand' ).on( 'click', function() {
								viewDisabled = true;
							} );
						}
					},
					callback: function() {
						// trigger image hide
						rl_hide_image( script, lastImage );
					}
				} );
				break;

			case 'fancybox':
				var lastImage = '';

				$( 'a[rel*="' + selector + '"], a[data-rel*="' + selector + '"]' ).fancybox( {
					modal: ( args.modal === '1' ? true : false ),
					overlayShow: ( args.showOverlay === '1' ? true : false ),
					showCloseButton: ( args.showCloseButton === '1' ? true : false ),
					enableEscapeButton: ( args.enableEscapeButton === '1' ? true : false ),
					hideOnOverlayClick: ( args.hideOnOverlayClick === '1' ? true : false ),
					hideOnContentClick: ( args.hideOnContentClick === '1' ? true : false ),
					cyclic: ( args.cyclic === '1' ? true : false ),
					showNavArrows: ( args.showNavArrows === '1' ? true : false ),
					autoScale: ( args.autoScale === '1' ? true : false ),
					scrolling: args.scrolling,
					centerOnScroll: ( args.centerOnScroll === '1' ? true : false ),
					opacity: ( args.opacity === '1' ? true : false ),
					overlayOpacity: parseFloat( args.overlayOpacity / 100 ),
					overlayColor: args.overlayColor,
					titleShow: ( args.titleShow === '1' ? true : false ),
					titlePosition: args.titlePosition,
					transitionIn: args.transitions,
					transitionOut: args.transitions,
					easingIn: args.easings,
					easingOut: args.easings,
					speedIn: parseInt( args.speeds ),
					speedOut: parseInt( args.speeds ),
					changeSpeed: parseInt( args.changeSpeed ),
					changeFade: parseInt( args.changeFade ),
					padding: parseInt( args.padding ),
					margin: parseInt( args.margin ),
					width: parseInt( args.videoWidth ),
					height: parseInt( args.videoHeight ),
					onComplete: function() {
						lastImage = $( '#fancybox-content' ).find( 'img' ).attr( 'src' );

						// trigger image view
						rl_view_image( script, lastImage );
					},
					onClosed: function() {
						// trigger image hide
						rl_hide_image( script, lastImage );
					}
				} );
				break;

			case 'nivo':
				$.each( $( 'a[rel*="' + selector + '"], a[data-rel*="' + selector + '"]' ), function() {
					var attr = $( this ).attr( 'data-rel' );

					// check data-rel attribute first
					if ( typeof attr === 'undefined' || attr == false ) {
						// if not found then try to check rel attribute for backward compatibility
						attr = $( this ).attr( 'rel' );
					}

					// for some browsers, `attr` is undefined; for others, `attr` is false. Check for both.
					if ( typeof attr !== 'undefined' && attr !== false ) {
						var match = attr.match( new RegExp( selector + '\\-(gallery\\-(?:[\\da-z]{1,4}))', 'ig' ) );

						if ( match !== null )
							$( this ).attr( 'data-lightbox-gallery', match[0] );
					}
				} );

				var observerInitialized = false;
				var changeAllowed = true;
				var lastImage = '';

				$( 'a[rel*="' + selector + '"], a[data-rel*="' + selector + '"]' ).nivoLightbox( {
					effect: args.effect,
					clickOverlayToClose: ( args.clickOverlayToClose === '1' ? true : false ),
					keyboardNav: ( args.keyboardNav === '1' ? true : false ),
					errorMessage: args.errorMessage,
					afterShowLightbox: function( lightbox ) {
						var content = $( lightbox )[0].find( '.nivo-lightbox-content' );

						// is observer initialized?
						if ( ! observerInitialized ) {
							// turn it off
							observerInitialized = true;

							// add content observer
							observeContentChanges( document.getElementsByClassName( 'nivo-lightbox-content' )[0], true, function() {
								if ( changeAllowed ) {
									lastImage = content.find( '.nivo-lightbox-image img' ).attr( 'src' );

									// trigger image view
									rl_view_image( script, lastImage );

									// disallow observer changes
									changeAllowed = false;
								}
							} );
						}
					},
					afterHideLightbox: function() {
						// allow observer changes
						changeAllowed = true;

						// trigger image hide
						rl_hide_image( script, lastImage );
					},
					onPrev: function( element ) {
						// disallow observer changes
						changeAllowed = false;

						lastImage = element[0].attr( 'href' );

						// trigger image view
						rl_view_image( script, lastImage );
					},
					onNext: function( element ) {
						// disallow observer changes
						changeAllowed = false;

						lastImage = element[0].attr( 'href' );

						// trigger image view
						rl_view_image( script, lastImage );
					}
				} );
				break;

			case 'imagelightbox':
				var selectors = [];
				var lastImage = '';

				$( 'a[rel*="' + selector + '"], a[data-rel*="' + selector + '"]' ).each( function( i, item ) {
					var attr = $( item ).attr( 'data-rel' );

					// check data-rel attribute first
					if ( typeof attr !== 'undefined' && attr !== false && attr !== 'norl' )
						selectors.push( attr );
					// if not found then try to check rel attribute for backward compatibility
					else {
						attr = $( item ).attr( 'rel' );

						if ( typeof attr !== 'undefined' && attr !== false && attr !== 'norl' )
							selectors.push( attr );
					}
				} );

				if ( selectors.length > 0 ) {
					// make unique
					selectors = _.uniq( selectors );

					$( selectors ).each( function( i, item ) {
						if ( typeof event.pagination_type !== 'undefined' ) {
							$( 'a[data-rel="' + item + '"], a[rel="' + item + '"]' ).each( function() {
								$( this ).off( 'click.imageLightbox' );
							} );
						}

						$( 'a[data-rel="' + item + '"], a[rel="' + item + '"]' ).imageLightbox( {
							animationSpeed: parseInt( args.animationSpeed ),
							preloadNext: ( args.preloadNext === '1' ? true : false ),
							enableKeyboard: ( args.enableKeyboard === '1' ? true : false ),
							quitOnEnd: ( args.quitOnEnd === '1' ? true : false ),
							quitOnImgClick: ( args.quitOnImageClick === '1' ? true : false ),
							quitOnDocClick: ( args.quitOnDocumentClick === '1' ? true : false ),
							onLoadEnd: function() {
								lastImage = $( '#imagelightbox' ).attr( 'src' );

								// trigger image view
								rl_view_image( script, lastImage );
							},
							onEnd: function() {
								// trigger image hide
								rl_hide_image( script, lastImage );
							}
						} );
					} );
				}
				break;

			case 'tosrus':
				var selectors = [];
				var lastImage = '';

				$( 'a[rel*="' + selector + '"], a[data-rel*="' + selector + '"]' ).each( function( i, item ) {
					var attr = $( item ).attr( 'data-rel' );

					// check data-rel attribute first
					if ( typeof attr !== 'undefined' && attr !== false && attr !== 'norl' )
						selectors.push( attr );
					// if not found then try to check rel attribute for backward compatibility
					else {
						attr = $( item ).attr( 'rel' );

						if ( typeof attr !== 'undefined' && attr !== false && attr !== 'norl' )
							selectors.push( attr );
					}
				} );

				if ( selectors.length > 0 ) {
					// make unique
					selectors = _.uniq( selectors );

					$( selectors ).each( function( i, item ) {
						if ( typeof event.pagination_type !== 'undefined' ) {
							$( 'body' ).find( '.tosrus-' + item ).remove();

							$( 'a[data-rel="' + item + '"], a[rel="' + item + '"]' ).each( function() {
								$( this ).off( 'click.tos' );
							} );
						}

						var tos = $( 'a[data-rel="' + item + '"], a[rel="' + item + '"]' ).tosrus( {
							infinite: ( args.infinite === '1' ? true : false ),
							autoplay: {
								play: ( args.autoplay === '1' ? true : false ),
								pauseOnHover: ( args.pauseOnHover === '1' ? true : false ),
								timeout: args.timeout
							},
							effect: args.effect,
							keys: {
								prev: ( args.keys === '1' ? true : false ),
								next: ( args.keys === '1' ? true : false ),
								close: ( args.keys === '1' ? true : false )
							},
							pagination: {
								add: ( args.pagination === '1' ? true : false ),
								type: args.paginationType
							},
							// forced
							show: false,
							buttons: true,
							caption: {
								add: true,
								attributes: [ "title" ]
							},
							wrapper: {
								classes: 'tosrus-' + item,
								onClick: args.closeOnClick === '1' ? 'close' : 'toggleUI'
							}
						} );

						tos.on( 'sliding.tos', function( event, number ) {
							lastImage = $( $( event.target ).find( '.tos-slider .tos-slide' )[number] ).find( 'img' ).attr( 'src' );

							// trigger image view
							rl_view_image( script, lastImage );
						} );

						tos.on( 'closing.tos', function() {
							// trigger image hide
							rl_hide_image( script, lastImage );
						} );
					} );
				}
				break;

			case 'featherlight':
				var selectors = [];
				var lastImage = '';

				$( 'a[rel*="' + selector + '"], a[data-rel*="' + selector + '"]' ).each( function( i, item ) {
					var attr = $( item ).attr( 'data-rel' );

					// check data-rel attribute first
					if ( typeof attr !== 'undefined' && attr !== false && attr !== 'norl' )
						selectors.push( attr );
					// if not found then try to check rel attribute for backward compatibility
					else {
						attr = $( item ).attr( 'rel' );

						if ( typeof attr !== 'undefined' && attr !== false && attr !== 'norl' )
							selectors.push( attr );
					}
				} );

				if ( selectors.length > 0 ) {
					// make unique
					selectors = _.uniq( selectors );

					// set defaults
					$.extend( $.featherlight.defaults, {
						openSpeed: parseInt( args.openSpeed ),
						closeSpeed: parseInt( args.closeSpeed ),
						closeOnClick: args.closeOnClick,
						closeOnEsc: ( args.closeOnEsc === '1' ? true : false ),
						afterOpen: function( event ) {
							lastImage = event.currentTarget.href;

							// trigger image view
							rl_view_image( script, lastImage );
						},
						afterClose: function() {
							// trigger image hide
							rl_hide_image( script, lastImage );
						}
					} );

					$( selectors ).each( function( i, item ) {
						if ( typeof event.pagination_type !== 'undefined' ) {
							$( 'a[data-rel="' + item + '"], a[rel="' + item + '"]' ).each( function() {
								$( this ).off( 'click.featherlight' );
							} );
						}

						// gallery?
						if ( /-gallery-/.test( item ) ) {
							$( 'a[data-rel="' + item + '"], a[rel="' + item + '"]' ).featherlightGallery( {
								galleryFadeIn: parseInt( args.galleryFadeIn ),
								galleryFadeOut: parseInt( args.galleryFadeOut ),
								previousIcon: '&#10094;',
								nextIcon: '&#10095;'
							} );
						// video?
						} else if ( /-video-/.test( item ) ) {
							$( 'a[data-rel="' + item + '"], a[rel="' + item + '"]' ).featherlight();
						// single image?
						} else {
							$( 'a[data-rel="' + item + '"], a[rel="' + item + '"]' ).featherlight();
						}
					} );
				}
				break;

			case 'magnific':
				var selectors = [];
				var lastImage = '';

				$( 'a[rel*="' + selector + '"], a[data-rel*="' + selector + '"]' ).each( function( i, item ) {
					var attr = $( item ).attr( 'data-rel' );

					// check data-rel attribute first
					if ( typeof attr !== 'undefined' && attr !== false && attr !== 'norl' )
						selectors.push( attr );
					// if not found then try to check rel attribute for backward compatibility
					else {
						attr = $( item ).attr( 'rel' );

						if ( typeof attr !== 'undefined' && attr !== false && attr !== 'norl' )
							selectors.push( attr );
					}
				} );

				if ( selectors.length > 0 ) {
					// make unique
					selectors = _.uniq( selectors );

					$( selectors ).each( function( i, item ) {
						var subselector = $( 'a[data-rel="' + item + '"], a[rel="' + item + '"]' );
						var element = $( subselector[0] );
						var media_type = element.data( 'magnific_type' );
						var content_type = element.data( 'rl_content' );

						// check content type first
						if ( typeof content_type !== 'undefined' )
							media_type = content_type;

						// then media type if needed
						if ( typeof media_type === 'undefined' )
							media_type = 'image';

						subselector.magnificPopup( {
							type: media_type === 'gallery' ? 'image' : ( media_type === 'video' ? 'iframe' : media_type ),
							disableOn: args.disableOn,
							midClick: args.midClick === '1',
							preloader: args.preloader === '1',
							closeOnContentClick: args.closeOnContentClick === '1',
							closeOnBgClick: args.closeOnBgClick === '1',
							closeBtnInside: args.closeBtnInside === '1',
							showCloseBtn: args.showCloseBtn === '1',
							enableEscapeKey: args.enableEscapeKey === '1',
							alignTop: args.alignTop === '1',
							autoFocusLast: args.autoFocusLast === '1',
							fixedContentPos: args.fixedContentPos === 'auto' ? 'auto' : ( args.fixedContentPos === '1' ),
							fixedBgPos: args.fixedBgPos === 'auto' ? 'auto' : ( args.fixedBgPos === '1' ),
							image: {
								titleSrc: function( item ) {
									var title = item.el.data( 'rl_title' );
									var caption = item.el.data( 'rl_caption' );

									if ( ! title )
										title = '';

									if ( ! caption )
										caption = '';

									return title + '<small>' + caption + '</small>';
								}
							},
							gallery: {
								enabled: subselector.length > 1 && media_type === 'gallery',
								navigateByImgClick: true,
								preload: [0,1]
							},
							callbacks: {
								close: function() {
									rl_hide_image( script, this.currItem.src );
								},
								imageLoadComplete: function() {
									// trigger image view
									rl_view_image( script, this.currItem.src );
								}
							}
						} );
					} );
				}
				break;
		}
	} );

} )( jQuery );