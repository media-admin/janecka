<?php
/**
 * The Template for displaying submit buttons for the multistep checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized-pro/checkout/multistep/submit.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://vendidero.de/dokument/template-struktur-templates-im-theme-ueberschreiben
 * @package Germanized/Pro/Templates
 * @version 1.0.1
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
?>

<?php do_action( 'woocommerce_gzdp_before_step_submit_content', $step ); ?>

<div class="step-buttons step-buttons-<?php echo $step->get_id(); ?>">

    <?php do_action( 'woocommerce_gzdp_step_submit_content', $step ); ?>

	<?php if ( $step->has_prev() ) : ?>

		<a class="prev-step-button step-trigger" id="prev-step-<?php echo $step->get_id();?>" data-href="<?php echo $step->prev->get_id(); ?>" href="#step-<?php echo $step->prev->get_id(); ?>"><?php echo sprintf( _x( 'Back to step %s', 'multistep', 'woocommerce-germanized-pro' ), $step->prev->number ); ?></a>

	<?php endif; ?>

	<?php if ( $step->has_next() ) : ?>

		<button class="button alt next-step-button" type="submit" name="woocommerce_gzdp_checkout_next_step" id="next-step-<?php echo $step->get_id();?>" data-current="<?php echo $step->get_id();?>" data-next="<?php echo $step->next->get_id(); ?>"><?php echo sprintf( _x( 'Continue with step %s', 'multistep', 'woocommerce-germanized-pro' ), $step->next->number ); ?></button>

	<?php endif; ?>

	<div class="clear"></div>

</div>