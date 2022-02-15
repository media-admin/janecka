<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_GZD_Admin_Note' ) ) {
	include_once WC_GERMANIZED_ABSPATH . 'includes/admin/notes/class-wc-gzd-admin-note.php';
}

/**
 * WC_Admin_Notes_Welcome_Message.
 */
class WC_GZDP_Admin_Note_Generator_Versions extends WC_GZD_Admin_Note {

	public function is_disabled() {
		$outdated_data = get_option( 'woocommerce_gzdp_generator_outdated_data' );

		if ( ! empty( $outdated_data ) && current_user_can( 'manage_woocommerce' ) ) {
			return parent::is_disabled();
		}

		return true;
	}

	public function enable_notices() {
		return true;
	}

	public function get_name() {
		$outdated_data = get_option( 'woocommerce_gzdp_generator_outdated_data' );
		$suffix        = '';

		if ( ! empty( $outdated_data ) ) {
		    foreach( $outdated_data as $data ) {
		        $suffix .= '_' . $data['new_version'];
            }
        }

		return 'generator_versions' . $suffix;
	}

	public function get_title() {
		return __( 'Generator update recommended', 'woocommerce-germanized-pro'  );
	}

	public function get_content() {
		$outdated_data = get_option( 'woocommerce_gzdp_generator_outdated_data' );
		ob_start();
		?>
		<?php foreach( $outdated_data as $generator => $generator_data ) : ?>
			<p><?php printf( __( 'A new generator version for your <a href="%1$s">%2$s</a> has been released. Please consider refreshing the <a href="%3$s">generator</a> to make sure your legal content is up to date.', 'woocommerce-germanized-pro' ), get_edit_post_link( $generator_data['page_id'] ), get_the_title( $generator_data['page_id'] ), esc_url( $this->get_generator_admin_url( $generator ) ) ); ?></p>
		<?php endforeach; ?>
		<?php
		return ob_get_clean();
	}

	protected function get_generator_admin_url( $generator ) {
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/class-wc-gzdp-admin-generator.php';

		return WC_GZDP_Admin_Generator::instance()->get_admin_url( $generator );
	}

	protected function get_generator_title( $generator ) {
		include_once WC_GERMANIZED_PRO_ABSPATH . 'includes/admin/class-wc-gzdp-admin-generator.php';
		$generators = WC_GZDP_Admin_Generator::instance()->get_generators();

		return isset( $generators[ $generator ] ) ? $generators[ $generator ] : $generator;
	}

	public function get_actions() {
		$outdated_data = get_option( 'woocommerce_gzdp_generator_outdated_data' );
		$actions       = array();

		foreach( $outdated_data as $generator => $generator_data ) {
			$actions[] = array(
				'url'        => $this->get_generator_admin_url( $generator ),
				'title'      => sprintf( __( 'Rerun %s now', 'woocommerce-germanized-pro' ), $this->get_generator_title( $generator ) ),
				'target'     => '_self',
				'is_primary' => true,
			);
		}

		return $actions;
	}
}
