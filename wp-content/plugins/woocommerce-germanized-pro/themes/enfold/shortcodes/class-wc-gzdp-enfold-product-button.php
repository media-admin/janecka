<?php

if ( ! class_exists( 'aviaShortcodeTemplate' ) || ! class_exists( 'avia_sc_produc_button' ) )
    return;

class WC_GZDP_Enfold_Product_Button extends aviaShortcodeTemplate {

    private $org = null;

    public function __construct($builder) {

        if ( class_exists( 'avia_sc_produc_button' ) )
            $this->org = new avia_sc_produc_button($builder);
        else if ( class_exists( 'avia_sc_product_button' ) )
            $this->org = new avia_sc_product_button($builder);

        parent::__construct($builder);
    }

    /**
     * Create the config array for the shortcode button
     */
    function shortcode_insert_button() {
        // Partially this equals null on old Enfold versions
	    if ( ! $this->org ) {
		    return;
	    }

        $this->org->shortcode_insert_button();
        $this->config = $this->org->config;
        $this->config[ 'shortcode' ] = 'av_gzd_product_button';
        $this->config[ 'name' ] = __( 'Product Purchase Button GZD', 'woocommerce-germanized-pro' );
        $this->config[ 'tooltip' ] = __( 'Display the "Add to cart" button for the current product with legal information', 'woocommerce-germanized-pro' );
    }


    /**
     * Editor Element - this function defines the visual appearance of an element on the AviaBuilder Canvas
     * Most common usage is to define some markup in the $params['innerHtml'] which is then inserted into the drag and drop container
     * Less often used: $params['data'] to add data attributes, $params['class'] to modify the className
     *
     *
     * @param array $params this array holds the default values for $content and $args.
     * @return $params the return array usually holds an innerHtml key that holds item specific markup.
     */
    function editor_element($params) {
	    if ( ! $this->org ) {
		    return;
	    }

        return $this->org->editor_element($params);
    }


    /**
     * Frontend Shortcode Handler
     *
     * @param array $atts array of attributes
     * @param string $content text within enclosing form of shortcode element
     * @param string $shortcodename the shortcode found, when == callback name
     * @return string $output returns the modified html string
     */
    function shortcode_handler($atts, $content = "", $shortcodename = "", $meta = "") {

        global $woocommerce, $product;

        if( ! is_object( $woocommerce ) || ! is_object( $woocommerce->query ) || empty( $product ) )
            return;

        foreach( wc_gzd_get_single_product_shopmarks() as $shopmark ) {
            if ( ! $shopmark->is_enabled() ) {
                continue;
            }

	        add_action( 'woocommerce_gzdp_enfold_shortcode_product_add_to_cart_after_price', $shopmark->get_callback(), $shopmark->get_priority(), $shopmark->get_number_of_params() );
        }

        ob_start();

        ?>
        <div <?php post_class(); ?>>
            <div class="av-woo-purchase-button <?php echo $meta['el_class']; ?>">
                <?php woocommerce_template_single_price(); ?>
                <?php do_action( 'woocommerce_gzdp_enfold_shortcode_product_add_to_cart_after_price', $product ); ?>
                <span class="product_meta"></span>
                <?php woocommerce_template_single_add_to_cart(); ?>
            </div>
        </div>
        <?php
        $output = ob_get_clean();

        return $output;
    }
}