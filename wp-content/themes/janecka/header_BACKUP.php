<!DOCTYPE html>
<html <?php language_attributes(); ?>>

	<head>

		<!-- Meta Data -->
			<meta charset="<?php bloginfo( 'charset' ); ?>">

			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<meta http-equiv="content-type" content="text/html; charset=macintosh" />

			<meta name="viewport" content="width=device-width, initial-scale=1.0">


			<!-- Site Title -->
			<?php if (is_front_page() ) : ?>
				<title>Startseite | <?php bloginfo( 'name' ); ?></title>
			<?php else : ?>
				<title><?php wp_title($sep = ''); ?> | <?php bloginfo( 'name' ); ?></title>
			<?php endif; ?>


		<script>

			document.addEventListener("DOMContentLoaded", function() {

			dywc.init({

			cookie_version: 1, // Version der Cookiedefinition, damit bei Konfigurationsänderung erneutes Opt-In erforderlich wird
			cookie_name: 'Media Lab Cookie', // Name des Cookies, der zur Speicherung der Entscheidung verwendet wird
			cookie_expire: 31536e3, // Laufzeit des Cookies in Sekunden (31536e3 = 1Jahr)
			cookie_path: '/', // Pfad auf dem der Cookie gespeichert wird
			mode: 1, // 1 oder 2, bestimmt den Buttonstil
			bglayer: true, // Verdunklung des Hintergrunds aktiv (true) oder inaktiv (false)
			position: 'mt', // mt, mm, mb, lt, lm, lb, rt, rm, rb

			id_bglayer: 'dywc_bglayer',
			id_cookielayer: 'dywc',
			id_cookieinfo: 'dywc_info',

			url_legalnotice: '/datenschutz', // or null
			url_imprint: '/impressum', // or null

			text_title: 'Datenschutzeinstellungen',
			text_dialog: 'Wir nutzen Cookies auf unserer Website. Einige von ihnen sind essenziell, während andere uns helfen, diese Website und Ihre Erfahrung zu verbessern.',

			cookie_groups: [
			   {
					label: 'Notwendig',
					fixed: true,
					info: 'Zum Betrieb der Seite notwendige Cookies:',
						cookies: [
							{
								label: 'PHP Session Cookie',
								publisher: 'Eigentümer dieser Website',
								aim: 'Absicherung Kontaktformular / SPAM Schutz',
								name: 'PHPSESSID',
								duraction: 'Session'
							}, {
								label: 'Cookiespeicherung Entscheidungscookie',
								publisher: 'Eigentümer dieser Website',
								aim: 'Speichert die Einstellungen der Besucher bezüglich der Speicherung von Cookies.',
								name: 'dywc',
								duration: '1 Jahr'
							}
						],

				}, {
					label: 'Statistiken',
					fixed: false,
					info: 'Cookies für die Analyse des Benutzerverhaltens:',
					cookies: [
							{
								label: 'Google Analytics',
								publisher: 'Google LLC',
								aim: 'Cookie von Google für Website-Analysen. Erzeugt statistische Daten darüber, wie der Besucher die Website nutzt.',
								name: '_ga, _gid, _gat, _gtag',
								duration: '2 Jahre'
							}
						],
							accept: function() {
								dywc.log("Load Statistic Tracking");

								(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
								   (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
								   m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
								})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

								ga('create', '{TrackingID}', 'auto');
								ga('send', 'pageview');

							},

							reject: function() {

								dywc.log("Reject Statistic Tracking");

								// var disableStr = 'ga-disable-{TrackingID}';

								// window[disableStr] = true; document.cookie = disableStr + '=true; expires=Thu, 31 Dec 2099 23:59:59 UTC; path=/';

								// dywc.cookie.removeItem('_ga', '/', '.macoffice.at');
								// dywc.cookie.removeItem('_gid', '/', '.macoffice.at');
								// dywc.cookie.removeItem('_gat', '/', '.macoffice.at');
								// dywc.cookie.removeItem('_gat_gtag_{TrackingID}', '/', '.macoffice.at');

							}
				}, {
					label: 'Erleichterte Bedienung',
					fixed: false,
					info: 'Cookies zur Erleichterung der Bedienung für den Benutzer:',
					cookies: [
							{
								label: 'Google Maps',
								publisher: 'Google LLC',
								aim: 'Cookie von Google für die Nutzung von Google Maps.',
								name: 'NID',
								duration: '6 Monate'
						   }
						],

							accept: function() {

								dywc.log("Load Statistic Tracking");

								 /*
								 (function (d) {
									 var container = d.querySelector("#gmap-opt-in"),
										 wrap = d.querySelector("#gmap-opt-in .gmap-opt-in-wrap"),
										 btn = d.querySelector("#gmap-opt-in .gmap-opt-in-button"),
										 iframe = d.createElement("iframe"),
										 gmapSrc = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d19656.77575432525!2d16.209917483926358!3d47.810833882697544!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x476dcbe0da27d3e5%3A0x1c6428c578efbef6!2smac)office!5e0!3m2!1sde!2sat!4v1608589777546!5m2!1sde!2sat";

									 btn.addEventListener("click", function () {
										 // set iframe attributes
										 iframe.setAttribute("style", "border:0;");
										 iframe.setAttribute("src", gmapSrc);
										 iframe.setAttribute("frameborder", "0");

										 // remove all in container
										 container.removeChild(wrap);

										 // add iframe to container
										 container.appendChild(iframe);
									 }, false);
								 });
								 */











								/* Document Ready Script */
								document.ready = function( callback ) {

									if( document.readyState != 'loading' ) {
										callback();
									}
									else {
										document.addEventListener( 'DOMContentLoaded', callback );
									}

								};

								/* Automatically resize the iFrame */
								var iFrame2C = {};
								iFrame2C.rescale = function( iframe, format ) {

									let formatWidth = parseInt( format.split(':')[0] );
									let formatHeight = parseInt( format.split(':')[1] );
									let formatRatio = formatHeight / formatWidth;
									var iframeBounds = iframe.getBoundingClientRect();

									let currentWidth = iframeBounds.width;
									let newHeight = formatRatio * currentWidth;

									iframe.style.height = Math.round( newHeight ) + "px";
								};

								/* Resize iFrame */
								function iframeResize() {

									var iframes = document.querySelectorAll( 'iframe[data-scaling="true"]' );
									if( !!iframes.length ) {
										for( var i=0; i < iframes.length; i++ ) {
											let iframe = iframes[ i ];
											let videoFormat = '16:9';
											let is_data_format_existing = typeof iframe.getAttribute( 'data-format' ) !== "undefined";
											if( is_data_format_existing ) {
												let is_data_format_valid = iframe.getAttribute( 'data-format' ).includes( ':' );
												if( is_data_format_valid ) {
													videoFormat = iframe.getAttribute( 'data-format' );
												}
											}
											iFrame2C.rescale( iframe, videoFormat );
										}
									}
								}

								/* Event Listener on Resize for iFrame-Resizing */
								document.ready( function() {
									window.addEventListener( "resize", function() {
										iframeResize();
									});
									iframeResize();
								});

								/* Source-URLs */
								/*
								 data_type will be the value of the attribute "data-type" on element "video_trigger"
								 data_souce will be the value of the attribute "data-source" on element "video_trigger", which will be replaced on "{SOURCE}"
								*/
								function get_source_url( data_type ) {

									switch( data_type ) {

										case "google-maps":
											return "https://www.google.com/maps/embed?pb={SOURCE}";
										default: break;
									}
								}

								/* 2-Click Solution */
								document.ready( function() {

									var video_wrapper = document.querySelectorAll( '.video_wrapper' );

									if( !!video_wrapper.length ) {
										for( var i=0; i < video_wrapper.length; i++ ) {
											let _wrapper = video_wrapper[ i ];

											var video_triggers = _wrapper.querySelectorAll( '.video_trigger' );
											if( !!video_triggers.length ) {

												for( var l=0; l < video_triggers.length; l++ ) {

													var video_trigger = video_triggers[ l ];
													var accept_buttons = video_trigger.querySelectorAll( 'input[type="button"]' );

													if( !!accept_buttons.length ) {
														for( var j=0; j < accept_buttons.length; j++ ) {

															var accept_button = accept_buttons[ j ];
															accept_button.addEventListener( "click", function() {

																var _trigger = this.parentElement;
																var data_type = _trigger.getAttribute( "data-type" );
																var source = "";
																_trigger.style.display = "none";

																source = get_source_url( data_type );

																var data_source = _trigger.getAttribute( 'data-source' );
																source = source.replace( "{SOURCE}", data_source );

																var video_layers = _trigger.parentElement.querySelectorAll( ".video_layer" );
																if( !!video_layers.length ) {
																	for( var k=0; k < video_layers.length; k++ ) {

																		var video_layer = video_layers[ k ];
																		video_layer.style.display = "block";
																		video_layer.querySelector( "iframe" ).setAttribute( "src", source );

																	}
																}

																_wrapper.style.backgroundImage = "";
																_wrapper.style.height = "auto";

																var timeout = 100; // ms
																setTimeout( function() {
																	iframeResize();
																}, timeout );
															});
														}
													}
												}
											}
										};
									}
								});

							},

							reject: function() {

								dywc.log("Reject Statistic Tracking");

							}

						}, {

								label: 'Externe Medien',
								fixed: false,
								info: 'Cookies zum Einbinden fremder Inhalte:',
									cookies: [
										{
											label: 'Youtube',
											publisher: 'Google LLC',
											aim: 'Cookie von Google für die Benutzung von Youtube-Videos.',
											name: 'youtube, yt-remote-*',
											duration: '2 Jahre'
										}
									],

										accept: function() {

											dywc.log("Load Statistic Tracking");

											/* Document Ready Script */
											document.ready = function( callback ) {

												if( document.readyState != 'loading' ) {
													callback();
												}
												else {
													document.addEventListener( 'DOMContentLoaded', callback );
												}

											};

											/* Automatically resize the iFrame */
											var iFrame2C = {};
											iFrame2C.rescale = function( iframe, format ) {

												let formatWidth = parseInt( format.split(':')[0] );
												let formatHeight = parseInt( format.split(':')[1] );
												let formatRatio = formatHeight / formatWidth;
												var iframeBounds = iframe.getBoundingClientRect();

												let currentWidth = iframeBounds.width;
												let newHeight = formatRatio * currentWidth;

												iframe.style.height = Math.round( newHeight ) + "px";
											};

											/* Resize iFrame */
											function iframeResize() {

												var iframes = document.querySelectorAll( 'iframe[data-scaling="true"]' );
												if( !!iframes.length ) {
													for( var i=0; i < iframes.length; i++ ) {
														let iframe = iframes[ i ];
														let videoFormat = '16:9';
														let is_data_format_existing = typeof iframe.getAttribute( 'data-format' ) !== "undefined";
														if( is_data_format_existing ) {
															let is_data_format_valid = iframe.getAttribute( 'data-format' ).includes( ':' );
															if( is_data_format_valid ) {
																videoFormat = iframe.getAttribute( 'data-format' );
															}
														}
														iFrame2C.rescale( iframe, videoFormat );
													}
												}
											}

											/* Event Listener on Resize for iFrame-Resizing */
											document.ready( function() {
												window.addEventListener( "resize", function() {
													iframeResize();
												});
												iframeResize();
											});

											/* Source-URLs */
											/*
											 data_type will be the value of the attribute "data-type" on element "video_trigger"
											 data_souce will be the value of the attribute "data-source" on element "video_trigger", which will be replaced on "{SOURCE}"
											*/
											function get_source_url( data_type ) {

												switch( data_type ) {
													case "youtube":
													return "https://www.youtube-nocookie.com/embed/{SOURCE}?rel=0&controls=0&showinfo=0&autoplay=0&mute=0";

													/*
													case "youtube":
														return "https://www.youtube-nocookie.com/embed/HuEf86ATIys?rel=0&controls=0&showinfo=0&autoplay=0&mute=0";
													default: break;
													*/
												}
											}

											/* 2-Click Solution */
											document.ready( function() {

												var video_wrapper = document.querySelectorAll( '.video_wrapper' );

												if( !!video_wrapper.length ) {
													for( var i=0; i < video_wrapper.length; i++ ) {
														let _wrapper = video_wrapper[ i ];

														var video_triggers = _wrapper.querySelectorAll( '.video_trigger' );
														if( !!video_triggers.length ) {

															for( var l=0; l < video_triggers.length; l++ ) {

																var video_trigger = video_triggers[ l ];
																var accept_buttons = video_trigger.querySelectorAll( 'input[type="button"]' );

																if( !!accept_buttons.length ) {
																	for( var j=0; j < accept_buttons.length; j++ ) {

																		var accept_button = accept_buttons[ j ];
																		accept_button.addEventListener( "click", function() {

																			var _trigger = this.parentElement;
																			var data_type = _trigger.getAttribute( "data-type" );
																			var source = "";
																			_trigger.style.display = "none";

																			source = get_source_url( data_type );

																			var data_source = _trigger.getAttribute( 'data-source' );
																			source = source.replace( "{SOURCE}", data_source );

																			var video_layers = _trigger.parentElement.querySelectorAll( ".video_layer" );
																			if( !!video_layers.length ) {
																				for( var k=0; k < video_layers.length; k++ ) {

																					var video_layer = video_layers[ k ];
																					video_layer.style.display = "block";
																					video_layer.querySelector( "iframe" ).setAttribute( "src", source );

																				}
																			}

																			_wrapper.style.backgroundImage = "";
																			_wrapper.style.height = "auto";

																			var timeout = 100; // ms
																			setTimeout( function() {
																				iframeResize();
																			}, timeout );
																		});
																	}
																}
															}
														}
													};
												}
											});

										},

										reject: function() {
											dywc.log("Reject Statistic Tracking");
										}
						}

					]

				});

			});

		</script>

		<?php wp_enqueue_script("jquery"); ?>
		<?php wp_head(); ?>

	</head>

	<body <?php body_class(); ?> >

		<div class="container outer-container">

			<?php if(!empty($post->post_content)) { ?>

			<?php } else { ?>

			<?php

			   $args = array(
			   'post_status' => 'publish',
			   'posts_per_page' => 1,
			   'post_type' => 'hinweis',
			   'orderby'   => 'date',
			   'order' => 'DESC',
			   );

			$loop = new WP_Query( $args );

			while ( $loop->have_posts() ) : $loop->the_post(); ?>

				<article class="message">

					<div class="message-header">
						<h2 class=""><?php the_title(); ?></h2>
					</div>

				<div class="message-body">
					<p class="">
						<?php the_content(); ?>
					</p>
				</div>

				</article>

			<?php
			endwhile;
			wp_reset_postdata();
			?>

			<?php };?>



		<script>
			jQuery(document).ready(() => {
				const modal = $('.modal');
				$('#showModal').click(function(){
					modal.addClass('is-active');
			});
			jQuery('.modal-close').click(function(){
					modal.removeClass('is-active');
			});
			});
		</script>

		<header class="">

			<div class="container">

				<section class="top-header container fullwidth is-fixed-top">

					<a class="logo" href="<?php echo get_home_url(); ?>">
						<img class="brand-logo" src="<?php bloginfo( 'template_directory' ); ?>/assets/img/logos/janecka-logos/logo-Janecka-2lines.svg" alt="Logo JANECKA - Juweliere seit 1924">
					</a>

					<aside class="webshop-navbar">
						<nav>
							<ul>
								<li class="li__suche">
										<a id="close" class="" onclick="SEARCH_DIALOG.classList.toggle('is-active')">
										<img class="" src="<?php bloginfo( 'template_directory' ); ?>/assets/img/icons/icon-search.svg" alt="Suchen Icon">
										</a>

										<div id="SEARCH_DIALOG" class="modal">
											<div class="modal-background"></div>
											<div class="modal-content">
												<?php /* echo do_shortcode('[yith_woocommerce_ajax_search]'); */ ?>
												<?php aws_get_search_form( true ); ?>
											</div>
											<button id="close" class="modal-close is-large" aria-label="close"></button>
										</div>
								</li>

								<li>
									<a href="/meine-wunschliste">
										<img class="" src="<?php bloginfo( 'template_directory' ); ?>/assets/img/icons/icon-heart.svg" alt="Wunschzettel Icon">
									</a>
								</li>

								<li>
									<a href="/mein-konto">
										<img class="" src="<?php bloginfo( 'template_directory' ); ?>/assets/img/icons/icon-user.svg" alt="Kundenkonto Icon">
									</a>
								</li>

								<li>
									<a href="/mein-warenkorb">
										<img class="" src="<?php bloginfo( 'template_directory' ); ?>/assets/img/icons/icon-bag.svg" alt="Warenkorb Icon">
									</a>
								</li>

							</ul>
						</nav>
					</aside>

				</section>

				<nav class="navbar-brand">

					<a role="button" class="navbar-burger burger column column--menu" aria-label="menu" aria-expanded="false" data-target="navbar-main">
						<span aria-hidden="true"></span>
						<span aria-hidden="true"></span>
						<span aria-hidden="true"></span>
					</a>


						<div id="navbar-main" class="navbar-menu">

							<?php
							/* Navigation Walker für Hauptnavigation */

								$defaults = array(
									'theme-location' => 'nav-menu-main', //change it according to your register_nav_menus() function
									 'depth'		=>	3,
									 'menu'			=>	'Hauptnavigation',
									 'container'		=>	'',
									 'menu_class'		=>	'',
									 'items_wrap'		=>	'%3$s',
									 'walker'		=>	new MegaMenu_Navwalker(),
									 'fallback_cb'		=>	'MegaMenu_Navwalker::fallback'
								);
								wp_nav_menu( $defaults );
							?>

					</div>


					<!-- Make the MegaMenu appear -->
					<script>
						document.addEventListener('DOMContentLoaded', function () {

							// Get all "navbar-burger" elements
							var $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

							// Check if there are any nav burgers
							if ($navbarBurgers.length > 0) {

								// Add a click event on each of them
								$navbarBurgers.forEach(function ($el) {
									$el.addEventListener('click', function () {

										// Get the target from the "data-target" attribute
										var target = $el.dataset.target;
										var $target = document.getElementById(target);

										// Toggle the class on both the "navbar-burger" and the "navbar-menu"
										$el.classList.toggle('is-active');
										$target.classList.toggle('is-active');

									});
								});
							}

						});
					</script>





					<div class="navbar-end"></div>


				</nav>

			</div>

		</header>








		<?php if (is_front_page() ) : ?>

		<section class="hero hero-slider has-carousel">

			<div id="carousel-slider" class="hero-carousel header__slider-carousel">

				<?php

				   $args = array(
				   'post_status' => 'publish',
				   'posts_per_page' => -1,
				   'post_type' => 'slider',
				   'orderby'   => 'date',
				   'order' => 'ASC',
				   );

				$loop = new WP_Query( $args );

				while ( $loop->have_posts() ) : $loop->the_post(); ?>

					<div class='carousel-item has-background'>
						<?php $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full');?>

						<!-- <div class="hero-head"><h2><?php the_title(); ?></h2></div> -->
						<!-- <div class="hero-body"><?php the_content(); ?></div> -->

						<img class="is-background" src="<?=$thumbnail[0];?>" alt="">
					</div>

				<?php
				endwhile;
				wp_reset_postdata();
				?>

			</div>

		</section>
		<!-- End Hero Carousel -->

		<?php endif; ?>




		<div class="container">

		<?php woocommerce_breadcrumb(); ?>