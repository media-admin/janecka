<?php

/**
* Template Name: Seite Shop Ohrschmuck
* Template Description: Shop-Bereich für Ohrschmuck
*/

get_header(); ?>

	<main class="site-main content">

		<h1 class="site-title"><?php the_title(); ?></h1>

		<?php the_content(); ?>

		<article class="columns shop-overview__columns">
			<section class="content-shop container column content-shop__column-filters">
				<aside class="sidebar-filters">
					<?php echo do_shortcode ('[yith_wcan_filters slug="schmuck-ohrschmuck"]') ?>
				</aside>
			</section>

			<section class="content-shop container column content-shop__column-products">
				<div class="container">
					<?php echo do_shortcode ('[products limit="60" columns="3" category="ohrschmuck-schmuck" paginate="true"]') ?>
				</div>
			</section>
		</article>

		<section class="service-notice">
			<?php echo do_shortcode('[content_schmuckservice]'); ?>
		</section>

	</main>

<?php get_footer(); ?>