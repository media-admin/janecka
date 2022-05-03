<!DOCTYPE html>
<html <?php language_attributes(); ?>>

	<head>

		<!-- Meta Data -->
			<meta charset="<?php bloginfo( 'charset' ); ?>">

			<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
			<meta http-equiv="content-type" content="text/html; charset=macintosh" />

			<meta name="viewport" content="width=device-width, initial-scale=1.0">

			<!-- Site Title -->
			<?php if (is_front_page() ) : ?>
				<title>Startseite | <?php bloginfo( 'name' ); ?></title>
			<?php else : ?>
				<title><?php wp_title($sep = ''); ?> | <?php bloginfo( 'name' ); ?></title>
			<?php endif; ?>

		<meta property="og:image" content="<?php the_post_thumbnail();?>

		<?php wp_enqueue_script( 'jquery' ); ?>

		<?php wp_head(); ?>

	</head>

	<body <?php body_class(); ?> >

		<div class="container outer-container">

			<?php if(!empty($post->post_content)) { ?>

			<?php } else { ?>

			<?php

			   $args = array(
			   'post_status' => 'publish',
			   'posts_per_page' => 1,
			   'post_type' => 'hinweis',
			   'orderby'   => 'date',
			   'order' => 'DESC',
			   );

			$loop = new WP_Query( $args );

			while ( $loop->have_posts() ) : $loop->the_post(); ?>

				<article class="message">

					<div class="message-header">
						<h2 class=""><?php the_title(); ?></h2>
					</div>

				<div class="message-body">
					<p class="">
						<?php the_content(); ?>
					</p>
				</div>

				</article>

			<?php
			endwhile;
			wp_reset_postdata();
			?>

			<?php };?>



		<script>
			jQuery(document).ready(() => {
				const modal = jQuery('.modal');
				jQuery('#showModal').click(function(){
					modal.addClass('is-active');
			});
			jQuery('.modal-close').click(function(){
					modal.removeClass('is-active');
			});
			});
		</script>

		<header class="site-header is-fixed-top" id="site-header">

			<div class="header-container container fullwidth">

				<nav class="navbar-brand">

					<div role="button" class="navbar-burger burger column column--menu" aria-label="menu" aria-expanded="false" data-target="navbar-main">
						<span aria-hidden="true"></span>
						<span aria-hidden="true"></span>
						<span aria-hidden="true"></span>
					</div>

					<div class="search-area">
						<a id="close" class="" onclick="SEARCH_DIALOG.classList.toggle('is-active')">
							<img class="" src="<?php bloginfo( 'template_directory' ); ?>/assets/img/icons/icon-search.svg" alt="Suchen Icon">
							</a>

							<div id="SEARCH_DIALOG" class="modal">
								<div class="modal-background"></div>
								<div class="modal-content">
									<?php /* echo do_shortcode('[yith_woocommerce_ajax_search]'); */ ?>
									<?php aws_get_search_form( true ); ?>
								</div>
								<button id="close" class="modal-close is-large" aria-label="close"></button>
							</div>
					</div>


					<div id="navbar-main" class="navbar-menu">

						<div class="navbar-start">

							<?php
							/* Navigation Walker für Hauptnavigation */

								$defaults = array(
									'theme-location' => 'nav-menu-main', //change it according to your register_nav_menus() function
									 'depth'		=>	2,
									 'menu'			=>	'Hauptnavigation',
									 'container'		=>	'',
									 'menu_class'		=>	'',
									 'items_wrap'		=>	'%3$s',
									 'link_after'	=> '',
									 'walker'		=>	new MegaMenu_Navwalker(),
									 'fallback_cb'		=>	'MegaMenu_Navwalker::fallback'
								);
								wp_nav_menu( $defaults );
							?>


							<!-- Make the Burger Navigation work -->
							<script>
								document.addEventListener('DOMContentLoaded', function () {

									// Get all "navbar-burger" elements
									var $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

									// Check if there are any nav burgers
									if ($navbarBurgers.length > 0) {

									// Add a click event on each of them
									$navbarBurgers.forEach(function ($el) {
										$el.addEventListener('click', function () {

											// Get the target from the "data-target" attribute
											var target = $el.dataset.target;
											var $target = document.getElementById(target);

											// Toggle the class on both the "navbar-burger" and the "navbar-menu"
											$el.classList.toggle('is-active');
											$target.classList.toggle('is-active');

											});

										});
									}

								});
							</script>


							<!-- Make the Dropdown Navigation work -->
							<script>

								document.addEventListener('DOMContentLoaded', function () {

									// Get all "navbar-burger" elements
									var $navbarDropdowns = Array.prototype.slice.call(document.querySelectorAll('.has-dropdown'), 0);

									// Check if there are any nav burgers
									if ($navbarDropdowns.length > 0) {

									// Add a click event on each of them
									$navbarDropdowns.forEach(function ($el) {
										$el.addEventListener('click', function () {

											// Get the target from the "data-target" attribute
											var target = $el.dataset.target;
											var $target = document.getElementById(target);

											// Toggle the class on both the "navbar-burger" and the "navbar-menu"
											$el.classList.toggle('is-active-dropdown');
											$target.classList.toggle('is-active-dropdown');

											});

										});
									}

								});

							</script>



							<!-- Make the MegaMenu appear -->
							<script>
								document.addEventListener('DOMContentLoaded', function () {

									// Get all "navbar-burger" elements
									var $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.is-mega'), 0);

									// Check if there are any nav burgers
									if ($navbarBurgers.length > 0) {

									// Add a click event on each of them
									$navbarBurgers.forEach(function ($el) {
										$el.addEventListener('click', function () {

											// Get the target from the "data-target" attribute
											var target = $el.dataset.target;
											var $target = document.getElementById(target);

											// Toggle the class on both the "navbar-burger" and the "navbar-menu"
											$el.classList.toggle('is-active');
											$target.classList.toggle('is-active');

										});

									});
								}

							});
						</script>

						<div class="navbar-end"></div>

				</nav>


				<section class="navbar-shop">
					<aside class="webshop-navbar">
						<nav>
							<ul>
								<li>
									<a href="/mein-konto">
										<img class="" src="<?php bloginfo( 'template_directory' ); ?>/assets/img/icons/icon-user.svg" alt="Kundenkonto Icon">
									</a>
								</li>

								<li>
									<a href="/meine-wunschliste">
										<img class="" src="<?php bloginfo( 'template_directory' ); ?>/assets/img/icons/icon-heart.svg" alt="Wunschzettel Icon">
									</a>
								</li>

								<li>
									<a href="/mein-warenkorb">
										<img class="" src="<?php bloginfo( 'template_directory' ); ?>/assets/img/icons/icon-bag.svg" alt="Warenkorb Icon">
									</a>
								</li>

							</ul>
						</nav>
					</aside>
				</section>


				<section class="logo-container container fullwidth">

					<a class="logo" href="<?php echo get_home_url(); ?>">
						<img class="brand-logo" src="<?php bloginfo( 'template_directory' ); ?>/assets/img/logos/janecka-logos/logo-Janecka-2lines.svg" alt="Logo JANECKA - Juweliere seit 1924">
					</a>

				</section>

			</div>

		</header>


		<?php if (is_front_page() ) : ?>

		<section class="hero hero-slider has-carousel">

			<div id="carousel-slider" class="hero-carousel header__slider-carousel">

				<?php

				   $args = array(
				   'post_status' => 'publish',
				   'posts_per_page' => -1,
				   'post_type' => 'slider',
				   'orderby'   => 'date',
				   'order' => 'ASC',
				   );

				$loop = new WP_Query( $args );

				while ( $loop->have_posts() ) : $loop->the_post(); ?>

					<div class='carousel-item has-background'>
						<?php $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id(), 'full');?>

						<!-- <div class="hero-head"><h2><?php the_title(); ?></h2></div> -->
						<!-- <div class="hero-body"><?php the_content(); ?></div> -->

						<img class="is-background" src="<?=$thumbnail[0];?>" alt="">
					</div>

				<?php
				endwhile;
				wp_reset_postdata();
				?>

			</div>

		</section>
		<!-- End Hero Carousel -->

		<?php endif; ?>



		<div class="container">

		<?php woocommerce_breadcrumb(); ?>