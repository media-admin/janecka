<?php

/**
* Template Name: Seite Shop Damenuhren
* Template Description: Shop-Bereich für Damenuhren
*/

get_header(); ?>

	<main class="site-main content">

		<h1 class="site-title"><?php the_title(); ?></h1>

		<?php the_content(); ?>

		<article class="shop-overview__columns--one-column" id="product-grid">
			<section class="container content-shop__column-filters--one-column">
				<aside class="sidebar-filters">
					<?php echo do_shortcode ('[yith_wcan_filters slug="uhren-damenuhren"]') ?>
				</aside>
			</section>

			<section class="container content-shop__column-products--one-column">
				<div class="container">
					<?php echo do_shortcode ('[products limit="60" columns="4" category="uhren" attribute="geschlecht" terms="damen" paginate="true"]') ?>
				</div>
			</section>
		</article>

		<section class="service-notice">
			<?php echo do_shortcode('[content_uhrenservice]'); ?>
		</section>

	</main>

<?php get_footer(); ?>