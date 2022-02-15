<?php
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\ZoomMagnifier
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


$image_settings = array(
	array(
		'type' => 'title',
		'desc' => '',
		'id'   => 'yith_wcmg_lightbox_settings',
	),
	array(
		'name'      => __( 'Enable lightbox on main product image', 'yith-woocommerce-zoom-magnifier' ),
		'desc'      => __( 'Enable the lightbox feature on the main product image.', 'yith-woocommerce-zoom-magnifier' ),
		'id'        => 'ywzm_enable_lightbox_feature',
		'std'       => 'yes',
		'default'   => 'yes',
		'type'      => 'yith-field',
		'yith-type' => 'onoff',
	),
	array(
		'name'      => __( 'Lightbox icon colors', 'yith-woocommerce-zoom-magnifier' ),
		'id'        => 'ywzm_lightbox_icon_colors_background',
		'type'      => 'yith-field',
		'yith-type' => 'multi-colorpicker',
		'colorpickers'  => array(
			array(
				'id' => 'background',
				'name' => __( 'Background', 'yith-woocommerce-zoom-magnifier' ),
				'default' => ' #ffffff'
			),
			array(
				'id' => 'background_hover',
				'name' => __( 'Background hover', 'yith-woocommerce-zoom-magnifier' ),
				'default' => '#000000'
			),
		),
		'deps'      => array(
			'id'    => 'ywzm_enable_lightbox_feature',
			'value' => 'yes',
		)
	),
	array(
		'desc'      => __( 'Set the lightbox icon colors.', 'yith-woocommerce-zoom-magnifier' ),
		'id'        => 'ywzm_lightbox_icon_colors_icon',
		'type'      => 'yith-field',
		'yith-type' => 'multi-colorpicker',
		'colorpickers'  => array(
			array(
				'id' => 'icon',
				'name' => __( 'Icon', 'yith-woocommerce-zoom-magnifier' ),
				'default' => ' #000000'
			),
			array(
				'id' => 'icon_hover',
				'name' => __( 'Icon hover', 'yith-woocommerce-zoom-magnifier' ),
				'default' => '#ffffff'
			),
		),
		'deps'      => array(
			'id'    => 'ywzm_enable_lightbox_feature',
			'value' => 'yes',
		)
	),
	array(
		'name'    => __( 'Lightbox icon size (px)', 'yith-woocommerce-zoom-magnifier' ),
		'desc'    => __( 'Set the lightbox icon size.', 'yith-woocommerce-zoom-magnifier' ),
		'id'      => 'ywzm_lightbox_icon_size',
		'type'    => 'yith-field',
		'yith-type' => 'number',
		'default'   => '25',
		'min'      => 0,
		'step'     => 1,
		'deps'      => array(
			'id'    => 'ywzm_enable_lightbox_feature',
			'value' => 'yes',
		)

	),
	array(
		'name'    => __( 'Lightbox icon radius (%)', 'yith-woocommerce-zoom-magnifier' ),
		'desc'    => __( 'Set the border radius to change the shape of the lightbox background.', 'yith-woocommerce-zoom-magnifier' ),
		'id'      => 'yith_wcmg_lightbox_radius',
		'type'    => 'yith-field',
		'yith-type' => 'number',
		'default'   => '0',
		'min'      => 0,
		'step'     => 1,
		'deps'      => array(
			'id'    => 'ywzm_enable_lightbox_feature',
			'value' => 'yes',
		)

	),
	array(
		'name'    => __( 'Lightbox icon position', 'yith-woocommerce-zoom-magnifier' ),
		'desc'    => __( 'Choose in which position to show the lightbox icon.', 'yith-woocommerce-zoom-magnifier' ),
		'id'      => 'ywzm_lightbox_icon_position',
		'std'     => 'top-right',
		'default' => 'top-right',
		'type'      => 'yith-field',
		'yith-type' => 'select',
		'class'   => 'wc-enhanced-select',
		'options' => array(
			'top-left'  => __( 'Top left', 'yith-woocommerce-zoom-magnifier' ),
			'top-right' => __( 'Top right', 'yith-woocommerce-zoom-magnifier' ),
			'bottom-left' => __( 'Bottom left', 'yith-woocommerce-zoom-magnifier' ),
			'bottom-right' => __( 'Bottom right', 'yith-woocommerce-zoom-magnifier' ),
		),
		'deps'      => array(
			'id'    => 'ywzm_enable_lightbox_feature',
			'value' => 'yes',
		)
	),
	array(
		'name'      => __( 'Lightbox overlay color', 'yith-woocommerce-zoom-magnifier' ),
		'desc'      => __( 'Set the color for the lightbox overlay.', 'yith-woocommerce-zoom-magnifier' ),
		'id'        => 'ywzm_lightbox_overlay_color',
		'type'      => 'yith-field',
		'yith-type' => 'colorpicker',
		'default' => '#000',
		'deps'      => array(
			'id'    => 'ywzm_enable_lightbox_feature',
			'value' => 'yes',
		)

	),
	array(
		'type' => 'sectionend',
		'id'   => 'yith_wcmg_lightbox_settings_end',
	),
);

return array( 'image-lightbox' => apply_filters( 'yith_wcmg_tab_options', $image_settings ) );
