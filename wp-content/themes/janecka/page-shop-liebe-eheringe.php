<?php

/**
* Template Name: Seite Shop Liebe Eheringe
* Template Description: Shop-Bereich für Eheringe
*/

get_header(); ?>

	<main class="site-main content">

		<h1 class="site-title"><?php the_title(); ?></h1>

		<?php the_content(); ?>

		<article class="columns shop-overview__columns">
			<section class="content-shop container column content-shop__column-filters">
				<aside class="sidebar-filters">
					<?php echo do_shortcode ('[yith_wcan_filters slug="hochzeit-liebe-eheringe"]') ?>
				</aside>
			</section>

			<section class="content-shop container column content-shop__column-products">
				<div class="container">
					<?php wc_get_template( 'archive-product-cat-eheringe.php' ); ?>
				</div>
				</section>

			</article>

	</main>

<?php get_footer(); ?>