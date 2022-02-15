<?php

/**
* Template für Standard-Seiten
*/

get_header(); ?>

	<main class="content">

		<h1 class="site-title"><?php the_title(); ?></h1>

		<?php the_content(); ?>

	</main>

<?php get_footer(); ?>