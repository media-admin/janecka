<?php 

/**
* Template Name: Seite Ratgeber
* Template Description: FAQ-Bereich der Website 	
*/

get_header(); ?>
	  
	<main class="content">
		
		<h1 class="site-title"><?php the_title(); ?></h1>  
		
		<?php the_content(); ?>
		
			<section class="faq-area faq-area--allgemeine-fragen">
				
				<h2 class="faq-area__header">Allgemeine Fragen</h2>
			
				<?php
				 
					$args = array(  
					'post_status' => 'publish',
					'posts_per_page' => -1, 
					'post_type' => 'faq',
					'orderby'   => 'id',
					'order' => 'ASC',
					'faq-kategorien' => 'allgemeine-fragen'
				);
				
				$loop = new WP_Query( $args ); 
						
				while ( $loop->have_posts() ) : $loop->the_post(); ?>
					
					<article class="faq-area__article">
						
						<h3 class="faq-area__title"><?php the_title(); ?></h3>
						<!-- <hr class="faq-area__title-divider"> -->
						
						<p class="faq-area__content">
							<?php the_content(); ?>
						</p>
						
						<hr class="hr--darkgrey">
						
					</article>
					
				<?php	
					endwhile;
					wp_reset_postdata(); 
				?>
		
			</section>
		
		
		<section class="faq-area faq-area--onlinebestellungen">
			
			<h2 class="faq-area__header">Fragen zu Onlinebestellungen</h2>
			
			<?php
			 
				$args = array(  
				'post_status' => 'publish',
				'posts_per_page' => -1, 
				'post_type' => 'faq',
				'orderby'   => 'id',
				'order' => 'ASC',
				'faq-kategorien' => 'onlinebestellungen'
			);
			
			$loop = new WP_Query( $args ); 
					
			while ( $loop->have_posts() ) : $loop->the_post(); ?>
				
				<article class="faq-area__article">
					
					<h3 class="faq-area__title"><?php the_title(); ?></h3>
					<!-- <hr class="faq-area__title-divider"> -->
					
					<p class="faq-area__content">
						<?php the_content(); ?>
					</p>
					
					<hr class="hr--darkgrey">
					
				</article>
				
			<?php	
				endwhile;
				wp_reset_postdata(); 
			?>
	
		</section>
	
	
		<section class="faq-area faq-area--reparaturen">
			
			<h2 class="faq-area__header">Fragen zu Reparaturen</h2>
		
			<?php
			 
				$args = array(  
				'post_status' => 'publish',
				'posts_per_page' => -1, 
				'post_type' => 'faq',
				'orderby'   => 'id',
				'order' => 'ASC',
				'faq-kategorien' => 'reparaturen'
			);
			
			$loop = new WP_Query( $args ); 
					
			while ( $loop->have_posts() ) : $loop->the_post(); ?>
				
				<article class="faq-area__article">
					
					<h3 class="faq-area__title"><?php the_title(); ?></h3>
					<!-- <hr class="faq-area__title-divider"> -->
					
					<p class="faq-area__content">
						<?php the_content(); ?>
					</p>
					
					<hr class="hr--darkgrey">
					
				</article>
				
			<?php	
				endwhile;
				wp_reset_postdata(); 
			?>
		
		</section>
		
	</main>
		
<?php get_footer(); ?>