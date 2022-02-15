<?php

/**
* Template Name: Seite Shop Hochzeit & Liebe
* Template Description: Shop-Bereich für Hochzeit & Liebe
*/

get_header(); ?>

	<main class="site-main content">

		<h1 class="site-title"><?php the_title(); ?></h1>

		<?php the_content(); ?>

		<article class="columns shop-overview__columns">
			<section class="content-shop container column content-shop__column-filters">
				<aside class="sidebar-filters">
					<?php echo do_shortcode ('[yith_wcan_filters slug="filter-set-verlobungsringe"]') ?>
				</aside>
			</section>

			<section class="content-shop container column content-shop__column-products">
				<div class="container">
					<?php echo do_shortcode ('[products limit="60" category="liebe-hochzeit" paginate="true"]') ?>
				</div>
			</section>
		</article>

			<section class="service-notice">
			<?php echo do_shortcode('[content_schmuckservice]'); ?>
		</section>

	</main>

<?php get_footer(); ?>