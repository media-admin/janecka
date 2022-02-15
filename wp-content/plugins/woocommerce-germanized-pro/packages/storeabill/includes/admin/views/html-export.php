<?php
/**
 * Admin View: Export
 *
 * @var \Vendidero\StoreaBill\Interfaces\Exporter $exporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

wp_enqueue_script( 'storeabill_admin_export' );
?>
<div class="wrap storeabill">
	<h1><?php echo esc_html_x( 'Export', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></h1>

	<div class="sab-exporter-wrapper">
		<form class="sab-exporter">
			<header>
				<span class="spinner is-active"></span>
				<h2><?php echo esc_html( $exporter->get_title() ); ?></h2>
				<p><?php echo esc_html( $exporter->get_description() ); ?></p>
			</header>
			<section>
				<table class="form-table sab-exporter-options">
					<tbody>
					<tr>
						<th scope="row">
							<label for="sab-exporter-start-date"><?php echo esc_html_x( 'Date range', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></label>
						</th>
						<td id="sab-exporter-date-range">
                            <input type="text" size="11" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( $exporter->get_default_setting( 'start_date' ) ); ?>" name="start_date" class="range_datepicker from" autocomplete="off" /><?php //@codingStandardsIgnoreLine ?>
                            <span>&ndash;</span>
                            <input type="text" size="11" placeholder="yyyy-mm-dd" value="<?php echo esc_attr( $exporter->get_default_setting( 'end_date' ) ); ?>" name="end_date" class="range_datepicker to" autocomplete="off" /><?php //@codingStandardsIgnoreLine ?>
                            <br/>
                            <a class="sab-exporter-date-adjuster" data-adjust="last_month" href="#"><?php echo esc_html_x( 'Last Month', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                            <a class="sab-exporter-date-adjuster" data-adjust="current_month" href="#"><?php echo esc_html_x( 'Current Month', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></a>
                        </td>
					</tr>
					<?php echo $exporter->render_filters(); ?>
					</tbody>
				</table>
                <div class="sab-notice-wrapper"></div>
				<progress class="sab-exporter-progress sab-progress-bar" max="100" value="0"></progress>
			</section>
			<div class="sab-actions">
				<button type="submit" class="sab-exporter-button button button-primary" value="<?php echo esc_attr_x( 'Start export', 'storeabill-core', 'woocommerce-germanized-pro' ); ?>"><?php echo esc_attr_x( 'Start export', 'storeabill-core', 'woocommerce-germanized-pro' ); ?></button>
			</div>
		</form>
	</div>
</div>
