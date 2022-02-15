<!-- Start Brands Carousel -->

	<section class="has-carousel">

		<div id="brand-slider" class="responsive brand-slider">

			<?php

				$args = array(
					'taxonomy' => 'product_tag',
					'hide_empty' => false,
					'meta_query' => array(
							'key'  => 'brand-is-active',
							'value'   => true,
							'compare' => '==',
					),
				);

				$brandNames = get_terms( $args);

				foreach($brandNames as $brandName) :
					$image = get_field('brand-logo-main', $brandName);

					$brand_is_active = get_field('brand-is-active', $brandName);

					if( $brand_is_active == true ) {

				?>

				<div class='brand-slider__item has-background'>

						<?php if( $image ) : ?>

							<a class="brand-slider__link brand-slider__link-image" href="<?php echo get_term_link( $brandName->slug, $brandName->taxonomy ); ?>">
								<img src="<?php echo $image['url']; ?>" alt="" />
							</a>

						<?php else : ?>

							<a class="brand-slider__link brand-slider__link-text" href="<?php echo get_term_link( $brandName->slug, $brandName->taxonomy ); ?>">
								<?php echo $brandName->name; ?>
							</a>

						<?php endif; ?>

				</div>

				<?php } ?>

				<?php
					 endforeach;
				?>

		</div>

	</section>
	<!-- End Brands Carousel -->