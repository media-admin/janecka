<?php

if ( ! defined( 'ABSPATH' ) )
	exit;
?>

<?php if ( ! empty( $errors ) ) : ?>
	<p style="margin-bottom: 5px;"><strong><?php _e( 'Some errors have occurred during import:', 'woocommerce-germanized-pro' ); ?></strong></p>
	<div class="wc-gzdp-error-wrapper" style="max-height: 200px; overflow-y: scroll; margin-bottom: 10px; background: #fff; border: 1px solid #e5e5e5; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 5px 10px; text-align: left">
<pre style="margin: 0">
<?php foreach( $errors as $error ) : ?>
<?php echo $error . "\n"; ?>
<?php endforeach; ?>
</pre>
	</div>
<?php endif; ?>