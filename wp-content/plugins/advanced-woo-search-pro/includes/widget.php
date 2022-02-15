<?php
/*
 * Initialized plugins widget
 */

add_action( 'widgets_init', 'aws_pro_register_widget' );

function aws_pro_register_widget() {
    register_widget("AWS_Widget");
}

class AWS_Widget extends WP_Widget {

    /*
     * Constructor
     */
    function __construct() {
        $widget_ops = array( 'description' => esc_html__('Advanced WooCommerce search widget', 'advanced-woo-search' ) );
        $control_ops = array( 'width' => 400 );
        parent::__construct( false, esc_html__( '&raquo; AWS Widget', 'advanced-woo-search' ), $widget_ops, $control_ops );
    }

    /*
     * Display widget
     */
    function widget( $args, $instance ) {
        extract( $args );

        $title = apply_filters( 'widget_title',
            ( ! empty( $instance['title'] ) ? $instance['title'] : '' ),
            $instance,
            $this->id_base
        );

        if ( ! isset( $instance['id'] ) ) {
            return;
        }

        $shortcode_atts = array( 'id' => $instance['id'] );

        echo $before_widget;

        if ( $title ) {
            echo $before_title;
            echo $title;
            echo $after_title;
        }

        // Generate search form markup
        echo AWS_PRO()->markup( $shortcode_atts );

        echo $after_widget;
    }

    /*
     * Update widget settings
     */
    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $params = array( 'title', 'id' );
        foreach ( $params as $k ) {
            $instance[$k] = strip_tags( $new_instance[$k] );
        }
        return $instance;
    }

    /*
     * Widget settings form
     */
    function form( $instance ) {
        global $shortname;
        $defaults = array(
            'title' => esc_html__( 'Search...', 'advanced-woo-search' ),
            'id'    => 1
        );
        $instance = wp_parse_args( (array) $instance, $defaults );

        $plugin_options = get_option( 'aws_pro_settings' );

        ?>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id('title') ); ?>"><?php esc_html_e( 'Title:', 'advanced-woo-search' ); ?></label>
            <input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id('title') ); ?>" name="<?php echo esc_attr( $this->get_field_name('title') ); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr( $this->get_field_id('id') ); ?>"><?php esc_html_e( 'Form ID:', 'advanced-woo-search' ); ?></label>
            <select name="<?php echo esc_attr( $this->get_field_name('id') ); ?>" id="<?php echo esc_attr( $this->get_field_id('id') ); ?>" style="width: 100%;display: block;">
                <option value selected disabled><?php esc_html_e( 'Select ID', 'advanced-woo-search' ); ?></option>
                <?php foreach ( $plugin_options as $instance_id => $instance_options ) { ?>
                    <option value="<?php echo $instance_id; ?>" <?php selected( $instance['id'], $instance_id ); ?>><?php echo $instance_id; ?></option>
                <?php } ?>
            </select>
        </p>

    <?php }

}
?>