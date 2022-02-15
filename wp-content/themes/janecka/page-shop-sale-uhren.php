<?php

/**
* Template Name: Seite Shop Sale Uhren
* Template Description: Shop-Bereich für Sale Uhren
*/

get_header(); ?>

	<main class="site-main content">

		<h1 class="site-title"><?php the_title(); ?></h1>

		<?php the_content(); ?>

		<article class="columns">

			<section class="content-shop container column is-one-quarter">

				<aside class="column column is-three-quarter">
					<?php echo do_shortcode ('[yith_wcan_filters slug="sale-uhren"]') ?>
				</aside>
			</section>

			<section class="content-shop container column is-three-quarters">
				<div class="container">
					<?php echo do_shortcode ('[products limit="9" columns="3" category = "uhren"  on_sale="true" paginate="true"]') ?>
				</div>
			</section>

		</article>

		<section class="service-notice">

			<?php echo do_shortcode('[content_uhrenservice]'); ?>

		</section>

	</main>

<?php get_footer(); ?>