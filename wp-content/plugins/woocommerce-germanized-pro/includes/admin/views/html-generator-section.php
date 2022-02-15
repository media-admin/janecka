<?php
/**
 * Admin View: Generator Section
 */

if ( ! defined( 'ABSPATH' ) )
	exit;
?>
<div class="wc-gzd-admin-settings wc-gzd-admin-settings-has-sidebar wc-gzd-admin-settings-generator wc-gzd-admin-settings-generator-<?php echo $generator_id; ?>">
    <div class="wc-gzd-admin-settings-fields wc-gzdp-generator wc-gzdp-generator-loading">
	    <?php
		    WC_Admin_Settings::output_fields( $settings );
		    $generator->delete_result( $generator_id );
        ?>

        <p class="submit">
			<input type="hidden" name="generator" value="<?php echo esc_attr( $generator_id ); ?>" />
			<input name="save" class="button-primary wc-gzdp-generator-submit" type="submit" value="<?php echo sprintf( _x( 'Start %s', 'generator', 'woocommerce-germanized-pro' ), $generator->get_title( $generator_id ) ); ?>" />
		</p>

		<div class="version"><p>Version <?php echo $generator->get_version( $generator_id );?></p></div>
	</div>
    <div class="wc-gzd-admin-settings-sidebar">
        <div class="wc-gzd-admin-settings-sidebar-inner sticky">
            <div class="wc-gzdp-generator-sidebar">
                <div class="wc-gzdp-info info"></div>
            </div>
        </div>
    </div>
</div>