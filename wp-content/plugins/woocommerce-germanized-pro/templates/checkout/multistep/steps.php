<?php
/**
 * The Template for displaying step navigation for the multistep checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/checkout/multistep/steps.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://vendidero.de/dokument/template-struktur-templates-im-theme-ueberschreiben
 * @package Germanized/Pro/Templates
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$current = $multistep->get_current_step();
?>

<?php if ( ! empty( $multistep->steps ) ) : ?>

	<ul class="step-nav nav-wizard">
		
	<?php foreach ( $multistep->steps as $key => $step ) : ?>
		<?php if ( $step->is_activated() ) : ?>

		<li class="<?php echo $current->get_id() === $step->get_id() ? 'active' : ''; ?>">
			<a <?php echo ( ( $current->number >= $step->number ) ? 'href="#step-' . $step->get_id() . '"' : '' ); ?> data-href="<?php echo $step->get_id(); ?>" class="step step-<?php echo $step->number; ?> step-<?php echo $step->get_id(); ?>">
				<span class="step-number"><?php echo $step->number; ?></span>
				<span class="step-title"><?php echo $step->get_title(); ?></span>
			</a>
		</li>

		<?php endif; ?>
	<?php endforeach; ?>

	</ul>

<?php endif; ?>