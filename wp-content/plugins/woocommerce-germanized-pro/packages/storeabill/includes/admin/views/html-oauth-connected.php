<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="sab-oauth-connected sab-oauth-wrapper">
    <span class="sab-status sab-status-green"><?php _ex( 'Connected', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></span>
	<p class="description sab-additional-desc"><?php echo $description; ?></p>
	<button class="button button-secondary sab-oauth-disconnect-button"><?php echo esc_attr( _x( 'Disconnect', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?></button>
    <input type="hidden" name="<?php echo esc_attr( $input_name ); ?>" id="<?php echo esc_attr( $input_name ); ?>" class="sab-oauth-disconnect-input" />

    <a class="button button-primary sab-oauth-button sab-oauth-refresh-button" href="<?php echo esc_url( $refresh_url ); ?>" target="_blank"><?php echo esc_attr( _x( 'Refresh', 'storeabill-core', 'woocommerce-germanized-pro' ) ); ?></a>
    <span class="expires-on"><?php printf( _x( 'Expires on %s', 'storeabill-core', 'woocommerce-germanized-pro' ), date_i18n( get_option( 'date_format' ), $expires_on ) ); ?></span>

	<?php if ( $is_manual ) : ?>
        <div class="authorization-code" style="display: none">
            <label for="<?php echo esc_attr( $authorization_input_name ); ?>"><?php _ex( 'Authorization Code', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
            <input type="text" name="<?php echo esc_attr( $authorization_input_name ); ?>" id="<?php echo esc_attr( $authorization_input_name ); ?>" value="" />

            <button class="button button-primary sab-oauth-submit-code"><?php _ex( 'Save', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></button>
        </div>
	<?php endif; ?>
</div>
