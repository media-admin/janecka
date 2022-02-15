<?php
/**
* Template Name: Login Seite
*/
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
	
	<head>
		
    	<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		
	    <title><?php wp_title(); ?> <?php bloginfo('name'); ?></title>   
	    
	    
	    <!-- Favicon und Tiles Varianten-->
	    <link rel="apple-touch-icon-precomposed" sizes="57x57" href="<?php bloginfo('template_directory'); ?>/img/favicon/apple-touch-icon-57x57.png" />
		<link rel="apple-touch-icon-precomposed" sizes="114x114" href="<?php bloginfo('template_directory'); ?>/img/favicon/apple-touch-icon-114x114.png" />
		<link rel="apple-touch-icon-precomposed" sizes="72x72" href="<?php bloginfo('template_directory'); ?>/img/favicon/apple-touch-icon-72x72.png" />
		<link rel="apple-touch-icon-precomposed" sizes="144x144" href="<?php bloginfo('template_directory'); ?>/img/favicon/apple-touch-icon-144x144.png" />
		<link rel="apple-touch-icon-precomposed" sizes="60x60" href="<?php bloginfo('template_directory'); ?>/img/favicon/apple-touch-icon-60x60.png" />
		<link rel="apple-touch-icon-precomposed" sizes="120x120" href="<?php bloginfo('template_directory'); ?>/img/favicon/apple-touch-icon-120x120.png" />
		<link rel="apple-touch-icon-precomposed" sizes="76x76" href="<?php bloginfo('template_directory'); ?>/img/favicon/apple-touch-icon-76x76.png" />
		<link rel="apple-touch-icon-precomposed" sizes="152x152" href="<?php bloginfo('template_directory'); ?>/img/favicon/apple-touch-icon-152x152.png" />
		<link rel="icon" type="image/png" href="<?php bloginfo('template_directory'); ?>/img/favicon/favicon-196x196.png" sizes="196x196" />
		<link rel="icon" type="image/png" href="<?php bloginfo('template_directory'); ?>/img/favicon/favicon-96x96.png" sizes="96x96" />
		<link rel="icon" type="image/png" href="<?php bloginfo('template_directory'); ?>/img/favicon/favicon-32x32.png" sizes="32x32" />
		<link rel="icon" type="image/png" href="<?php bloginfo('template_directory'); ?>/img/favicon/favicon-16x16.png" sizes="16x16" />
		<link rel="icon" type="image/png" href="<?php bloginfo('template_directory'); ?>/img/favicon/favicon-128.png" sizes="128x128" />
		<meta name="application-name" content="Autoglasdienst Martin Rottensteiner"/>
		<meta name="msapplication-TileColor" content="#FFFFFF" />
		<meta name="msapplication-TileImage" content="<?php bloginfo('template_directory'); ?>/img/favicon/mstile-144x144.png" />
		<meta name="msapplication-square70x70logo" content="<?php bloginfo('template_directory'); ?>/img/favicon/mstile-70x70.png" />
		<meta name="msapplication-square150x150logo" content="<?php bloginfo('template_directory'); ?>/img/favicon/mstile-150x150.png" />
		<meta name="msapplication-wide310x150logo" content="<?php bloginfo('template_directory'); ?>/img/favicon/mstile-310x150.png" />
		<meta name="msapplication-square310x310logo" content="<?php bloginfo('template_directory'); ?>/img/favicon/mstile-310x310.png" />

	    	     
	    <!-- Import Bulma Framework-->
	    <link rel="stylesheet" href='<?php bloginfo('template_directory'); ?>/vendor/bulma-0.9.0/css/bulma.min.css'>
	    
	    <!-- Import FontAwesome-->
	    <link rel="stylesheet" href='<?php bloginfo('template_directory'); ?>/vendor/fontawesome-free-5.14.0-web/css/all.css'>  
	     
	    
	    <!-- Import Styles-->
	    <link rel="stylesheet" href="<?php bloginfo( 'template_directory' ); ?>/style.css">
	    <link rel="stylesheet" href='<?php bloginfo('template_directory'); ?>/templates/login/css/style-login.css'>

		<?php wp_head(); ?>  
		
	</head>
  
	<body <?php body_class(); ?>>  
		
		<main>
			
			<section class="media-lab">
								
				<div class="container contact-infos">
				
					<h2 class="h2__first-row">Zuverlässigkeit</h2>
					<h2 class="h2__second-row">und Know-How</h2>
					<hr/>
					
					<p class="contact-description">
						Für Fragen und Hilfestellungen stehen wir Ihnen zu folgenden Zeiten und auf diesen Kanälen zur Verfügung:<br/><br/>
						<strong>MO</strong> bis <strong>DO</strong> von <strong>09:00-17:00</strong> Uhr<br/>und <strong>FR</strong> von <strong>09:00-15:30</strong> Uhr
					</p>
					
					<p class="contact-details">
						Mail <a href="mailto:office@media-lab.at">office@media-lab.at</a><br/>
						Telefon <a href="tel:0043263521096">+43 2635 21096</a><br/>
					</p>
					
					<p class="contact-website">
						<a href="https://www.media-lab.at/" target="_blank">www.<strong>media-lab</strong>.at</a>
					</p>
				
				</div>
		
			</section>
			
			

			
			<section class="customer">
		
		
				<!-- Check der Eingabe-Werte 
				
				<?php 
					
					$login  = (isset($_GET['login']) ) ? $_GET['login'] : 0; 
					
					if ( $login === "failed" ) {
					  echo '<p class="login-msg"><strong>ERROR:</strong> Invalid username and/or password.</p>';
					} elseif ( $login === "empty" ) {
					  echo '<p class="login-msg"><strong>ERROR:</strong> Username and/or Password is empty.</p>';
					} elseif ( $login === "false" ) {
					  echo '<p class="login-msg"><strong>ERROR:</strong> You are logged out.</p>';
					}
				?>
				
				-->
		
				<div id="login">
				
					<h1>
						<a href="<?php bloginfo(' SITEURL '); ?>"></a>
					</h1>
					  
					<div class="login-form">
						
						<?php
							
						$login  = (isset($_GET['login']) ) ? $_GET['login'] : 0;	
							
						$args = array(
						    'redirect' => home_url(),
						    'label_username' => __( 'Benutzername oder E-Mail-Adresse' ),
							'label_password' => __( 'Passwort' ), 
						    'id_username' => 'user',
						    'id_password' => 'pass',
						    'id_password' => 'pass',
						   ) 
						;?>
						
						<?php wp_login_form( $args ); ?>
						
					</div>
				
				</div>
				
			</section>
		
		</main>

	</body>
	
</html>