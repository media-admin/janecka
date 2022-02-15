<?php

/**
* Template Name: Seite Liebe-&-Hochzeit-Marken
* Template Description: Übersichtsseite sämtlicher Liebe-&-Hochzeit-Marken
*/

get_header(); ?>

	<main class="content">

		<h1 class="site-title"><?php the_title(); ?></h1>

		<?php the_content(); ?>

		<section class="brands-area brands-area--hochzeit container">

			<h3 class="has-text-centered">Liebe & Hochzeit</h3>

			<div class="brands-area__brands-listing">

			<?php

				$args = array(
					'taxonomy' => 'product_tag',
					'hide_empty' => false,
					'meta_query' => array(
						'relation' => 'AND',
						array(
							'key'  => 'brand-category',
							'value'   => 'Liebe & Hochzeit',
							'compare' => 'LIKE',
						),
						array(
							'key'  => 'brand-is-active',
							'value'   => true,
							'compare' => '==',
						),
					),
				);

				$brandNames = get_terms( $args );

				if( $brandNames ) :

					$i = 0; // Counter für Spalten-Anzahl //

					foreach($brandNames as $brandName) :

						$image = get_field('brand-logo-main', $brandName);

							if($i == 0) {
								echo '<article class="article-produkte">';
								echo '<div class="columns brands-area__brands-listing-row">';
							}
						?>

							<div class="column brand-area__brand-logo">

							<?php if( $image ) : ?>

								<a class="brand-area__link" href="<?php echo get_term_link( $brandName->slug, $brandName->taxonomy ); ?>">
									<img class="brand-area__logo-image" src="<?php echo $image['url']; ?>" alt="" />
								</a>

							<?php else : ?>

								<a class="brand-area__link brand-slider__link-text" href="<?php echo get_term_link( $brandName->slug, $brandName->taxonomy ); ?>">
									<?php echo $brandName->name; ?>
								</a>

							<?php endif; ?>

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

					<?php endif; ?>

					<?php wp_reset_postdata(); ?>

			</div>

		</section>

	</main>

<?php get_footer(); ?>