<?php

/**
* Template Name: Seite Shop Sale Gesamt
* Template Description: Shop-Bereich für Sales
*/

get_header(); ?>

	<main class="site-main content">

		<h1 class="site-title"><?php the_title(); ?></h1>

		<?php the_content(); ?>

		<article class="columns shop-overview__columns">
			<section class="content-shop container column content-shop__column-filters">
				<aside class="sidebar-filters">
					<?php echo do_shortcode ('[yith_wcan_filters slug="sale-gesamt"]') ?>
				</aside>
			</section>

			<section class="content-shop container column content-shop__column-products">
				<div class="container">
					<?php echo do_shortcode ('[products limit="60" columns="3" category = "uhren, schmuck" on_sale="true" paginate="true"]') ?>
				</div>
			</section>
		</article>

		<section class="service-notice">
			<?php echo do_shortcode('[content_uhrenservice]'); ?>
		</section>

	</main>

<?php get_footer(); ?>