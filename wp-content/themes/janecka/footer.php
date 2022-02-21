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



<script>
	window.onscroll = function() {stickyHeader()};

	var navbar = document.getElementById("site-header");
	var sticky = navbar.offsetTop;

	function stickyHeader() {
		if (window.pageYOffset >= sticky) {
			navbar.classList.add("sticky")
		} else {
			navbar.classList.remove("sticky");
		}
	}
</script>



<!-- Read More / Read Less Functionality  -->
<script>
	var $el, $ps, $up, totalHeight;

	jQuery(".sidebar-box .read-more__button").click(function() {

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


</body>

</html>
