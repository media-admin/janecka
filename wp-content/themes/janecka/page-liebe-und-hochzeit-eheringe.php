<?php
/**
* Template: Shop Kategorie Eheringe
*/
?>

<?php get_header(); ?>

	<main class="content">

		<section class="content-shop container">

			<div class="container columns is-one-quarter">

				<aside class="column">

					<?php dynamic_sidebar( 'home_right_1' ); ?>

				</aside>

				<section class="site-content column is-three-quarters container">

					<?php echo do_shortcode ('[woocommerce_product_filter_context]') ?>

					<?php echo do_shortcode ('[products limit="9" columns="3"  paginate="true"]') ?>

				</section>

			</div>

		</section>

	</main>

<?php get_footer(); ?>