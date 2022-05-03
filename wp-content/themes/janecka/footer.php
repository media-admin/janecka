</div>

<footer>

	<div class="container">

		<div class="has-text-left">

		<nav class="footer-navigation column">

			<div class="navbar-start columns">

				<?php
					wp_nav_menu(array(
						'menu' => 'Footernavigation',
						'theme_location' => 'footer-navigation',

						'container'=> false,
						'container_class' => '',

						'items_wrap'=> '<span id="%1$s" class="%2$s columns">%3$s</span>',
						'item_spacing' => 'preserve',

						'walker' => '',
						'fallback_cb' => false
					));
				?>



			</div> <!-- navbar-start -->

			<div class="footer-navigation-mobile">

				<?php
				/* Navigation Walker for Footer Navigation */

					$defaults = array(
						'menu'			=>	'Footernavigation',
						'theme-location' => 'footer-navigation', //change it according to your register_nav_menus() function
						'depth'		=>	2,

						'container'		=>	false,
						'container_class'		=>	false,

						'items_wrap'		=>	'%3$s',
						'link_after'	=> '',

						'walker'		=>	new Bulma_Navwalker(),
						'fallback_cb'		=>	false
					);
					wp_nav_menu( $defaults );
				?>


				<!-- Make the Dropdown Navigation work -->
				<script>

					document.addEventListener('DOMContentLoaded', function () {

						// Get all "navbar-burger" elements
						var $footernavDropdowns = Array.prototype.slice.call(document.querySelectorAll('.has-dropdown'), 0);

						// Check if there are any nav burgers
						if ($footernavDropdowns.length > 0) {

						// Add a click event on each of them
						$footernavDropdowns.forEach(function ($el) {
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

			</div>

			<div class="navbar-end"></div>

		</nav>


		<nav class="footer__menu columns">

			<div class="navbar-start">
				<?php
					wp_nav_menu(array(
						'menu' => 'Footermenü',
						'theme_location'=>'footer-menu',

						'container'=>'ul',
						'menu_class' => 'div',
						'items_wrap'=> '%3$s',

						'walker' => new Footer_Walker(),
						'fallback_cb'=>false
					));
				?>
			</div>

			<div class="navbar-end">

				<ul class="social-media-navigation">
					<li><a href="https://www.instagram.com/juwelier.janecka/?hl=de" target="_blank"><img src="<?php bloginfo( 'template_directory' ); ?>/assets/img/icons/icon-instagram.svg" alt="Logo Facebook"></a></li>
					<li><a href="https://de-de.facebook.com/juwelierjanecka/" target="_blank"><img src="<?php bloginfo( 'template_directory' ); ?>/assets/img/icons/icon-facebook.svg" alt="Logo Instagram"></a></li>
				</ul>

			</div>

		</nav>

		</div>

		<div class="footer__copyright">
			<p class="copyright">Copyright © <?php echo date("Y");?> Juwelier Janecka. Alle Rechte vorbehalten.</p>
		</div>

	</div>




</footer>

</div>






<?php wp_footer();?>

<!-- Header Slider Carousel -->
<script>

	jQuery(document).ready(function(){
		jQuery('.header__slider-carousel').slick({
			dots: true,
			infinite: true,
			speed: 4500,
			slidesToShow: 1,
			adaptiveHeight: false,
			arrows: true,
			autoplay: true,
			autoplaySpeed: 1800,
			fade: true,
			cssEase: 'ease-out'
		});
	});

</script>


<!-- Brand Slider Carousels -->
<script>

	jQuery(document).ready(function(){
		jQuery('.responsive').slick({
			dots: false,
			infinite: true,
			autoplay: true,
			speed: 300,
			slidesToShow: 4,
			slidesToScroll: 1,
			responsive: [
				{
					breakpoint: 1024,
					settings: {
						slidesToShow: 3,
						slidesToScroll: 3,
						infinite: true,
						dots: true
					}
				},
				{
					breakpoint: 600,
					settings: {
						slidesToShow: 2,
						slidesToScroll: 2
					}
				},
				{
					breakpoint: 480,
					settings: {
						slidesToShow: 1,
						slidesToScroll: 1
					}
				}
				// You can unslick at a given breakpoint now by adding:
				// settings: "unslick"
				// instead of a settings object
			]
		});
	});

</script>


<!-- Makes the Site Header sticky -->
<script>

	(function(){

		var doc = document.documentElement;
		var w = window;

		var prevScroll = w.scrollY || doc.scrollTop;
		var curScroll;
		var direction = 0;
		var prevDirection = 0;

		var header = document.getElementById('site-header');

		var checkScroll = function() {

			/*
			** Find the direction of scroll
			** 0 - initial, 1 - up, 2 - down
			*/

			curScroll = w.scrollY || doc.scrollTop;
			if (curScroll > prevScroll) {
				//scrolled up
				direction = 2;
			}
			else if (curScroll < prevScroll) {
				//scrolled down
				direction = 1;
			}

			if (direction !== prevDirection) {
				toggleHeader(direction, curScroll);
			}

			prevScroll = curScroll;
		};

		var toggleHeader = function(direction, curScroll) {
			if (direction === 2 && curScroll > 240) {

				header.classList.add('sticky');
				prevDirection = direction;
			}
			else if (direction === 1 && curScroll < 240) {
				header.classList.remove('sticky');
				prevDirection = direction;
			}
		};

		window.addEventListener('scroll', checkScroll, 200);

	})();


</script>







<!-- Spoiler Functionality  ----------- NOT USED -----------
<script>
	var $el, $ps, $up, totalHeight;

	jQuery(".spoiler-box .read-more__button").click(function() {

		totalHeight = 0

		$el = $(this);
		$p  = $el.parent();
		$up = $p.parent();
		$ps = $up.find("p:not('.read-more')");

		// measure how tall inside should be by adding together heights of all inside paragraphs (except read-more paragraph)
		$ps.each(function() {
			totalHeight += $(this).outerHeight() + 30;
		});

		$up
			.css({
				// Set height to prevent instant jumpdown when max height is removed
				"height": $up.height(),
				"max-height": 9999
			})
			.animate({
				"height": totalHeight
			});

		// fade out read-more
		$p.fadeOut();

		// prevent jump-down
		return false;

	});
</script>

-->


<!--Spoiler Functionality ALTERNATIVE -->

	<script>

		jQuery(".readmore-link").click( function(e) {
			// record if our text is expanded
			var isExpanded =  $(e.target).hasClass("expand");

			//close all open paragraphs
			jQuery(".readmore.expand").removeClass("expand");
			jQuery(".readmore-link.expand").removeClass("expand");

			// if target wasn't expand, then expand it
			if (!isExpanded){
				jQuery( e.target ).parent( ".readmore" ).addClass( "expand" );
				jQuery(e.target).addClass("expand");
			}
		});

	</script>

	<?php wp_footer();?>

</body>

</html>
