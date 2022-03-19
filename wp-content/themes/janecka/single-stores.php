<?php

/**
* Template Name: Seite Filiale
* Template Post Type: stores
* Template Description: Filiale-Bereich
*/

get_header(); ?>

	<main class="content">

		<h1 class="site-title"><?php the_title(); ?></h1>

		<section class="hero">
			<?php if ( has_post_thumbnail() ) : ?>
				<img class="header-hero-img" src="<?php the_post_thumbnail(); ?>
			<?php endif; ?>
		</section>


		<?php if ( get_field('store-aktuelle-meldung') ) : ?>
		<section class="store-notification container">
			<h2 class="store-notification__header">Aktueller Hinweis</h2>
			<p class="store-notification__content"><?php the_field('store-aktuelle-meldung'); ?></p>
			<hr class="store-notification__hr">
		</section>
		<?php endif; ?>


		<section class="store-description container">
			<?php the_field('store-kurzbeschreibung'); ?>
			<hr class="store-notification__hr">
		</section>


		<section class="columns contact-area">

			<div class="column is-one-third contact-area__overview">

				<h2>Kontakt</h2>

				<p class="contact-area__adress">
					<?php the_field('store-strasse', $post->ID); ?><br/>
					<?php the_field('store-plz', $post->ID); ?> <?php the_field('store-ort', $post->ID); ?><br/>
					Österreich / Austria

				</p>

				<p class="contact-area__details">
					Telefon: <a class="link-phonenumber" href="tel:<?php the_field('store-telefon', $post->ID); ?>"><?php the_field('store-telefon', $post->ID); ?></a><br/>
					E-Mail: <a class="link-email" href="mailto:<?php the_field('store-email', $post->ID); ?>"><?php the_field('store-email', $post->ID); ?></a>
				</p>


				<h3>Unsere Öffnungszeiten</h3>

				<p class="contact-area__opening-hours">

					<?php

					// Check rows exists.
					if( have_rows('store-oeffnungszeiten') ):

							// Loop through rows.
							while( have_rows('store-oeffnungszeiten') ) : the_row();

									// Load sub field value.
									$store_monday_opened = get_sub_field('monday_opened');
									$store_monday_closed = get_sub_field('monday_closed');
									$store_thuesday_opened = get_sub_field('thuesday_opened');
									$store_thuesday_closed = get_sub_field('thuesday_closed');
									$store_wednesday_opened = get_sub_field('wednesday_opened');
									$store_wednesday_closed = get_sub_field('wednesday_closed');
									$store_thursday_opened = get_sub_field('thursday_opened');
									$store_thuesday_closed = get_sub_field('thuesday_closed');
									$store_friday_opened = get_sub_field('friday_opened');
									$store_friday_closed = get_sub_field('friday_closed');
									$store_saturday_opened = get_sub_field('saturday_opened');
									$store_saturday_closed = get_sub_field('saturday_closed');
									?>

									<p class="store-oeffnungszeiten-listing">
										<table>
											<tr>
												<td>Montag</td>
												<td><strong><?php echo $store_monday_opened;?> - <?php echo $store_monday_closed;?> Uhr</strong></td>
											</tr>
											<tr>
												<td>Dienstag</td>
												<td><strong><?php echo $store_thuesday_opened;?> - <?php echo $store_thuesday_closed;?> Uhr</strong></td>
											</tr>
											<tr>
												<td>Mittwoch</td>
												<td><strong><?php echo $store_wednesday_opened;?> - <?php echo $store_wednesday_closed;?> Uhr</strong></td>
											</tr>
											<tr>
												<td>Donnerstag</td>
												<td><strong><?php echo $store_thursday_opened;?> - <?php echo $store_thuesday_closed;?> Uhr</strong></td>
											</tr>
											<tr>
												<td>Freitag</td>
												<td><strong><?php echo $store_friday_opened;?> - <?php echo $store_friday_closed;?> Uhr</strong></td>
											</tr>
											<tr>
												<td>Samstag</td>
												<td><strong><?php echo $store_saturday_opened;?> - <?php echo $store_saturday_closed;?> Uhr</strong></td>
											</tr>
										</table>
									</p>

							<?php
							endwhile;

					endif;
					?>

				</p>

				<button id="reservation" class="button contact-area__button-appointment-booking" onclick="APPOINTMENT_MODAL.classList.toggle('is-active')"> Terminvereinbarung </button>

				<div id="APPOINTMENT_MODAL" class="modal">
					<div class="modal-background"></div>
					<div class="modal-content">
						<?php echo do_shortcode('[contact-form-7 id="2731" title="Termin vereinbaren"]'); ?>
					</div>
					<button id="close" class="modal-close is-large" aria-label="close"></button>

				</div>

			</div>


			<div class="column is-two-third contact-area__map">

				<!-- Google Map -->
				<?php

				$location = get_field('store-map');

				if( $location ): ?>
					<div class="acf-map" data-zoom="14">
						<div class="marker" data-lat="<?php echo esc_attr($location['lat']); ?>" data-lng="<?php echo esc_attr($location['lng']); ?>"></div>
					</div>
				<?php endif; ?>

				<style type="text/css">

						.acf-map {
							width: 100%;
							height: 800px;
							border: #ccc solid 1px;
							margin: 20px 0;
						}

						// Fixes potential theme css conflict.
						.acf-map img {
							max-width: inherit !important;
						}
						</style>

						<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyC6BCon3WAqzUZpBlrCzG-ZuCFOqDPNvRM"></script>
						<script type="text/javascript">

						(function( jQuery ) {

								/**
								 * initMap
								 *
								 * Renders a Google Map onto the selected jQuery element
								 *
								 * @date    22/10/19
								 * @since   5.8.6
								 *
								 * @param   jQuery $el The jQuery element.
								 * @return  object The map instance.
								 */
								function initMap( $el ) {

									// Find marker elements within map.
									var $markers = $el.find('.marker');

									// Create gerenic map.
									var mapArgs = {
										zoom        : $el.data('zoom') || 16,
										mapTypeId   : google.maps.MapTypeId.ROADMAP
									};
									var map = new google.maps.Map( $el[0], mapArgs );

									// Add markers.
									map.markers = [];
									$markers.each(function(){
										initMarker( $(this), map );
									});

									// Center map based on markers.
									centerMap( map );

									// Return map instance.
									return map;
								}

								/**
								 * initMarker
								 *
								 * Creates a marker for the given jQuery element and map.
								 *
								 * @date    22/10/19
								 * @since   5.8.6
								 *
								 * @param   jQuery $el The jQuery element.
								 * @param   object The map instance.
								 * @return  object The marker instance.
								 */
								function initMarker( $marker, map ) {

									// Get position from marker.
									var lat = $marker.data('lat');
									var lng = $marker.data('lng');
									var latLng = {
										lat: parseFloat( lat ),
										lng: parseFloat( lng )
									};

									// Create marker instance.
									var marker = new google.maps.Marker({
										position : latLng,
										map: map,
										// icon:  'https://www.janecka.at/wp-content/themes/janecka/assets/logos/Logo_map-icon.png'
									});

									// Append to reference for later use.
									map.markers.push( marker );

									// If marker contains HTML, add it to an infoWindow.
									if( $marker.html() ){

										// Create info window.
										var infowindow = new google.maps.InfoWindow({
											content: $marker.html()
										});

										// Show info window when marker is clicked.
										google.maps.event.addListener(marker, 'click', function() {
											infowindow.open( map, marker );
										});
									}

								}


								/**
								 * centerMap
								 *
								 * Centers the map showing all markers in view.
								 *
								 * @date    22/10/19
								 * @since   5.8.6
								 *
								 * @param   object The map instance.
								 * @return  void
								 */
								function centerMap( map ) {

									// Create map boundaries from all map markers.
									var bounds = new google.maps.LatLngBounds();
									map.markers.forEach(function( marker ){
										bounds.extend({
											lat: marker.position.lat(),
											lng: marker.position.lng()
										});
									});

									// Case: Single marker.
									if( map.markers.length == 1 ){
										map.setCenter( bounds.getCenter() );

									// Case: Multiple markers.
									} else{
										map.fitBounds( bounds );
									}
								}

								// Render maps on page load.
								jQuery(document).ready(function(){
									$('.acf-map').each(function(){
										var map = initMap( $(this) );
									});
								});

								})(jQuery);
								</script>

			</div>

		</section>


		<section class="container">

			<div class="contact-area__zahlungsmittel">

				<h3>Zahlungsmöglichkeiten in dieser Filiale</h3>

				<div class="contact-area__zahlungsmittel-listing">

					<?php

						$terms = get_the_terms( get_the_ID(), 'filialen-zahlungsweisen' );

						if( ! empty( $terms ) ) : ?>

							<ul class="contact-area__zahlungsmittel-listing-list">

								<?php foreach( $terms as $term ) : ?>

								<li class="<?php echo $term->slug; ?> contact-area__zahlungsmittel-listing-list-item">

									<?php
										$image_id = get_field('zahlungsweisen-logo', $term, false);
										$image = wp_get_attachment_image_src($image_id);

									?>
									<div>
										<img class="contact-area__zahlungsmittel-icon" src="<?php echo $image[0]; ?>" />
										<p class="contact-area__zahlungsmittel-name"><?php echo $term->name; ?></p>
									</div>
								</li>

									<?php endforeach; ?>

							</ul>

					<?php
							endif;
					?>

				</div>

				<hr class="store-notification__hr">

			</div>

		</section>


		<section class="brands-area container">

			<div class="brands-area__introduction">
				<h2>An diesem Standort umfasst unser Sortiment folgende Marken</h2>

				<p><strong>Gerne reservieren wir unverbindlich Ihren Lieblingsartikel in Ihrer Wunschfiliale.</strong></p>

				<p>Falls Sie Ihren Lieblingsartikel in unserem Online-Shop nicht finden, organisieren wir Ihnen diesen unverbindlich.
							Kontaktieren Sie uns diesbezüglich gerne per <a href="mailto:info@janecka.at">Mail</a> oder <a href="+43 1 911 37 28">telefonisch</a>.</p>
			</div>


			<div class="brands-area__brands-listing">
				<?php

					$brandNames = get_field('store-brand-tag');

					if( $brandNames ):

						echo '<article class="article-produkte">';
						$i = 0; // Counter für Spalten-Anzahl //

						foreach($brandNames as $brandName) :
							setup_postdata($brandName);
							$image = get_field('brand-logo-main', $brandName);

							if($i == 0) {

									echo '<div class="columns brands-area__brands-listing-row">';
							}
						?>

									<div class="column brand-area__brand-logo">
										<a class="brand-area__link" href="<?php echo get_term_link( $brandName->slug, $brandName->taxonomy ); ?>">
											<?php
												if( $image ) : ?>
													<img class="brand-area__logo-image" src="<?php echo $image['url']; ?>" alt="" />
												<?php else :
													echo $brandName->name;
												endif; ?>
										</a>
									</div>

										<?php
											$i++;
											if($i == 5) {
												$i = 0;
												echo '</div>';
												echo '</article>';
											}
										?>

										<?php endforeach; ?>
										<?php wp_reset_postdata(); ?>
										<?php endif; ?>

									</div>


									<!--  <h3>Unsere Verkaufsräume</h3> -->

									<?php
										$j = 0;

										$images = get_field('store-bildergalerie');
										$size = 'large'; // (thumbnail, medium, large, full or custom size)

										if($j == 0) {
											echo '<div class="columns">';

											if( $images ): ?>

												<?php foreach( $images as $image ): ?>

													<div class="column">
														<img class="betrieb-gallerie-foto" src="<?php echo esc_url($image['sizes']['large']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
														<p><?php echo esc_html($image['caption']); ?></p>
													</div>

													<?php
														$j++;
														if($j == 3) {
															$j = 0;
															echo '</div>';
															echo '<div class="columns">';
														}
												endforeach; ?>
											</div>
											<?php endif;
										} ?>
			</div>
		</section>


		<section class="service-notice">
			<?php echo do_shortcode('[content_schmuckservice]'); ?>
		</section>

	</main>

<?php get_footer(); ?>