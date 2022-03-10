<?php

/**
* Template Name: Seite Shop Uhren Anlässe
* Template Description: Shop-Bereich für Uhren-Anlässe
*/

get_header(); ?>

	<main class="site-main content">

		<h1 class="site-title"><?php the_title(); ?></h1>

		<?php the_content(); ?>

		<article class="shop-overview__columns--one-column" id="product-grid">
			<section class="container content-shop__column-filters--one-column">
				<aside class="sidebar-filters">
					<?php echo do_shortcode ('[yith_wcan_filters slug="anlaesse-uhren"]') ?>
				</aside>
			</section>

			<section class="container content-shop__column-products--one-column">
				<div class="container">
					<?php echo do_shortcode ('[products limit="60" columns="4" category="uhren" attribute="anlass" terms="taufe,firmung,geburtstag,jubilaeum,morgengabe,valentinstag" paginate="true"]') ?>
				</div>
			</section>
		</article>

		<section class="service-notice">
			<?php echo do_shortcode('[content_schmuckservice]'); ?>
		</section>

	</main>

<?php get_footer(); ?>