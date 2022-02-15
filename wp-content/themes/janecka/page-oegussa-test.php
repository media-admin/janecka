<?php 
/**
* Template Name: Seite Ögussa (TEST)
*/

get_header(); ?>
	
<main class="content">
	
	
	<section>
			
		<?php
			$_url = "https://www.oegussa.at/de/charts/tageskurse";
		
			$_buffer = implode('', file($_url));
			echo $_buffer;
		?>
		
		</section>	
	

	
	
	
	


</main>
		
<?php get_footer(); ?>