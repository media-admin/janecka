<?php
/**
 * Checkout Order Step Customer Data
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanizedPro/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$payment_gateway = false;
$gateways        = WC()->payment_gateways()->get_available_payment_gateways();
$method          = WC()->session->get( 'chosen_payment_method' );

if ( $method && isset( $gateways[ $method ] ) )
    $payment_gateway = $gateways[ $method ];

?>

<div class="woocommerce-gzdp-checkout-verify-data">

	<div class="row addresses">

		<div class="columns large-3">

			<header class="title">
					<h4><?php echo _x( 'Billing Details', 'multistep', 'woocommerce-germanized-pro' ); ?> <a href="#step-address" class="edit step-trigger" data-href="address"><?php echo _x( 'edit', 'multistep', 'woocommerce-germanized-pro' ); ?></a></h4>
			</header>

			<address>
				<?php
					if ( ! $multistep->get_formatted_billing_address() ) {
						echo _x( 'N/A', 'multistep', 'woocommerce-germanized-pro' );
					} else {
						echo $multistep->get_formatted_billing_address();
					}
				?>
				<?php 
					if ( WC()->checkout->get_value( 'billing_email' ) )  {
						echo "<br/>" . WC()->checkout->get_value( 'billing_email' );
					}
				?>

                <?php do_action( 'woocommerce_gzdp_multistep_confirmation_after_billing_address', $multistep ); ?>
			</address>

        </div><!-- /.col-1 -->

		<div class="columns large-3">

			<header class="title">
				<h4><?php echo _x( 'Shipping Address', 'multistep', 'woocommerce-germanized-pro' ); ?> <a href="#step-address" class="edit step-trigger" data-href="address"><?php echo _x( 'edit', 'multistep', 'woocommerce-germanized-pro' ); ?></a></h4>
			</header>

			<address>
				<?php
					if ( ! $multistep->get_formatted_shipping_address() ) {
						echo _x( 'Same as billing address', 'multistep', 'woocommerce-germanized-pro' );
					} else {
						echo $multistep->get_formatted_shipping_address();
					}
				?>

                <?php do_action( 'woocommerce_gzdp_multistep_confirmation_after_shipping_address', $multistep ); ?>
			</address>

        </div><!-- /.col-2 -->

		<?php if ( $payment_gateway ) : ?>

			<div class="columns large-3">

				<header class="title">
					<h4><?php echo _x( 'Payment Method', 'multistep', 'woocommerce-germanized-pro' ); ?> <a href="#step-payment" class="edit step-trigger" data-href="payment"><?php echo _x( 'edit', 'multistep', 'woocommerce-germanized-pro' ); ?></a></h4>
				</header>

                <p class="wc-gzdp-payment-gateway"><?php echo $payment_gateway->get_title(); ?></p>
            </div>

		<?php endif; ?>

	</div><!-- /.col2-set -->

</div>