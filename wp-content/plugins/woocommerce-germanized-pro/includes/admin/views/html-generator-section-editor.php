<?php
/**
 * Admin View: Generator Section
 */

if ( ! defined( 'ABSPATH' ) )
	exit;
?>
<div class="wc-gzd-admin-settings wc-gzd-admin-settings-generator wc-gzd-admin-settings-generator-<?php echo $generator_id; ?>">
	<div class="wc-gzdp-generator-result">
		<?php wp_editor( $html, 'wc_gzdp_generator_content', array( 'media_buttons' => false ) ); ?>

		<p class="submit">
			<?php if ( $page_id = $generator->get_page_id( $generator_id ) ) : ?>

				<input type="hidden" name="generator_page_id" value="<?php echo $page_id; ?>" />
				<input type="hidden" name="generator" value="<?php echo esc_attr( $generator_id ); ?>" />
				<input type="submit" name="save" class="button-primary" value="<?php echo sprintf( _x( 'Save as %s', 'generator', 'woocommerce-germanized-pro' ), get_the_title( $page_id ) ); ?>" />

				<?php if ( 'agbs' !== $generator_id ) : ?>
					<div class="form-row">
                        <input type="checkbox" name="generator_page_append" id="generator_page_append" value="1" />
                        <?php echo _x( 'Append content instead of replacing it.', 'generator', 'woocommerce-germanized-pro' ); ?>
                    </div>
				<?php endif ; ?>
			<?php endif; ?>
		</p>
	</div>

    <?php $generator->delete_result( $generator_id ); ?>
</div>