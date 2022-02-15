<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Ajax Search Widget
 *
 * @author YITH
 * @package YITH WooCommerce Ajax Search Premium
 * @version 1.2
 */

if ( ! defined( 'YITH_WCAS' ) ) {
	exit; } // Exit if accessed directly

if ( ! class_exists( 'YITH_WCAS_Ajax_Search_Widget' ) ) {
	/**
	 * YITH WooCommerce Ajax Navigation Widget
	 *
	 * @since 1.0.0
	 */
	class YITH_WCAS_Ajax_Search_Widget extends WP_Widget {
		/**
		 * Constructor.
		 *
		 * @access public
		 */
		public function __construct() {

			/* Widget variable settings. */
			$this->woo_widget_cssclass    = 'woocommerce widget_product_search yith_woocommerce_ajax_search';
			$this->woo_widget_description = esc_html__( 'An Ajax Search box for products only.', 'yith-woocommerce-ajax-search' );
			$this->woo_widget_idbase      = 'yith_woocommerce_ajax_search';
			$this->woo_widget_name        = esc_html__( 'YITH WooCommerce Ajax Product Search', 'yith-woocommerce-ajax-search' );

			/* Widget settings. */
			$widget_ops = array(
				'classname'   => $this->woo_widget_cssclass,
				'description' => $this->woo_widget_description,
			);

			/* Create the widget. */
			parent::__construct( 'yith_woocommerce_ajax_search', $this->woo_widget_name, $widget_ops );
		}


		/**
		 * Widget function.
		 *
		 * @see WP_Widget
		 * @access public
		 * @param array $args Array of arguments.
		 * @param array $instance Array of instance.
		 * @return void
		 */
		public function widget( $args, $instance ) {

			$title = isset( $instance['title'] ) ? $instance['title'] : '';
			$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

			$template      = ( isset( $instance['template'] ) && $instance['template'] ) ? 'template=wide' : '';
			$filters_above = ( isset( $instance['filters_above'] ) && $instance['filters_above'] ) ? 'class=filters-above' : '';

			echo $args['before_widget']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

			if ( $title ) {
				echo $args['before_title'] . $title . $args['after_title']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			}

			echo do_shortcode( '[yith_woocommerce_ajax_search ' . $template . ' ' . $filters_above . ']' );

			echo $args['after_widget']; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		}

		/**
		 * Update function.
		 *
		 * @see WP_Widget->update
		 * @access public
		 * @param array $new_instance New instance.
		 * @param array $old_instance Old instance.
		 * @return array
		 */
		public function update( $new_instance, $old_instance ) {

			$instance['title']         = isset( $new_instance['title'] ) ? wp_strip_all_tags( stripslashes( $new_instance['title'] ) ) : '';
			$instance['template']      = isset( $new_instance['template'] ) ? 1 : 0;
			$instance['filters_above'] = isset( $new_instance['filters_above'] ) ? 1 : 0;
			return $instance;
		}

		/**
		 * Form function.
		 *
		 * @see WP_Widget->form
		 * @access public
		 * @param array $instance Instance.
		 * @return void
		 */
		public function form( $instance ) {

			$defaults = array(
				'title'         => '',
				'template'      => 0,
				'filters_above' => 0,
			);

			$instance = wp_parse_args( (array) $instance, $defaults );
			$title    = isset( $instance['title'] ) ? $instance['title'] : '';
			?>
			<p><label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'woocommerce' ); ?></label>
				<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value=" <?php echo esc_attr( $title ); ?>" />
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'template' ) ); ?>"> <?php esc_html_e( 'Template wide', 'yith-woocommerce-ajax-search' ); ?></label>
				<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'template' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'template' ) ); ?>" value="1" <?php checked( $instance['template'], 1 ); ?> />

			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'filters_above' ) ); ?>"> <?php esc_html_e( 'Filters above', 'yith-woocommerce-ajax-search' ); ?></label>
				<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'filters_above' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'filters_above' ) ); ?>" value="1" <?php checked( $instance['filters_above'], 1 ); ?> />

			</p>
			<?php
		}
	}
}
