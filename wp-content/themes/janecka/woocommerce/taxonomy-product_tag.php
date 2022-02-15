<?php
/**
 * The Template for displaying products in a product category. Simply includes the archive template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/taxonomy-product-cat.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     4.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}
?>

<?php

  get_header();

  // Query Variables
  $current_taxonomy  = 'product_tag';
  $brand_tag      = $GLOBALS['wp_query']->get_queried_object();
  $brand_name = $brand_tag->name;
  $brand_slug = $brand_tag->slug;


  // Output Variables
  $brand_banner = get_field('brand-banner', $brand_tag);
  $brand_description = get_field('brand-description', $brand_tag);
  $brand_category = get_field('brand-category', $brand_tag);

?>

<main class="site-main content">

    <h1 class="site-title"><?php echo $brand_name; ?></h1>

    <img src="<?php echo $brand_banner['url']; ?>" alt="" />

    <?php echo $brand_description; ?>

    <article class="columns" id="product-grid">
      <section class="content-shop container column is-one-quarter">

        <aside class="column column is-three-quarter">
          <?php

            if ( $brand_category[0] == 'Liebe & Hochzeit' ){
              echo do_shortcode ('[yith_wcan_filters slug="hochzeit-liebe-marken"]');
            }

            elseif ( $brand_category[0] == 'Hochzeit' ){
              echo do_shortcode ('[yith_wcan_filters slug="hochzeit-liebe-marken"]');
            }

            elseif ( $brand_category[0] == 'Verlobung' ){
              echo do_shortcode ('[yith_wcan_filters slug="hochzeit-liebe-marken"]');
            }

            elseif ( $brand_category[0] == 'Uhren' ){
              echo do_shortcode ('[yith_wcan_filters slug="uhren-marken"]');
            }

            elseif ( $brand_category[0] == 'Schmuck' ) {
              echo do_shortcode ('[yith_wcan_filters slug="schmuck-marken"]');
            }
          ?>

        </aside>
      </section>

      <section class="content-shop container column is-three-quarters">
        <div class="container">
         <?php echo do_shortcode ('[products limit="9" tag="' . $brand_slug . '" paginate="true"]' ) ?>
        </div>
      </section>

    </article>

    <section class="service-notice">

      <?php

        if ( $brand_category[0] == 'Hochzeit' ){
          echo do_shortcode ('[content_schmuckservice]');
        }

        if ( $brand_category[0] == 'Verlobung' ){
          echo do_shortcode ('[content_schmuckservice]');
        }


        elseif ( $brand_category[0] == 'Schmuck' ) {
          echo do_shortcode ('[content_schmuckservice]');
        }

        elseif ( $brand_category[0] == 'Uhren' ){
          echo do_shortcode ('[content_uhrenservice]');
        }

      ?>

    </section>

  </main>

<?php get_footer(); ?>