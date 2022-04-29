=== Advanced Woo Search ===
Contributors: Mihail Barinov
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=GSE37FC4Y7CEY
Tags: widget, plugin, woocommerce, search, product search, woocommerce search, ajax search, live search, custom search, ajax, shortcode, better search, relevance search, relevant search, search by sku, search plugin, shop, store, wordpress search, wp ajax search, wp search, wp search plugin, sidebar, ecommerce, merketing, products, category search, instant-search, search highlight, woocommerce advanced search, woocommerce live search, WooCommerce Plugin, woocommerce product search
Requires at least: 4.0
Tested up to: 5.9
Stable tag: 2.53
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

WooCommerce search plugin. Advanced search features. Support for both AJAX search and search results page.

== Description ==

Advanced Woo Search - powerful search plugin for WooCommerce. Supports **AJAX** search and **search results page** display.

[Plugin home page](https://advanced-woo-search.com/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo) | [Features List](https://advanced-woo-search.com/features/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo) | [Docs](https://advanced-woo-search.com/guide/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo)

## Main Features

* **Products search** - Search across all your WooCommerce products
* **Search in** - Search in product **title**, **content**, **excerpt**, **categories**, **tags**, **ID** and **sku**. Or just in some of them
* **Settings page** - User-friendly settings page with lot of options
* **Shortcode** and **widget** - Use shortcode and widget to place search box anywhere you want
* **Product image** - Each search result contains product image
* **Product price** - Each search result contains product price
* **Terms search** - Search for product categories and tags
* **Smart ordering** - Search results ordered by the priority of source where they were found
* **Fast** - Nothing extra. Just what you need for proper work
* **Stop Words** support to exclude certain words from search.
* Supports **variable products**
* **Search results page** support. Plugin search results will be integrated to your current page layout.
* Automatically synchronize all products data. No need to re-index all content manually after avery change.
* **Plurals** support
* **Synonyms** support
* Diacritical marks support
* Google Analytics support
* Seamless integration option for easy replacing your current search form
* **WPML**, **Polylang**, **WooCommerce Multilingual**, **qTranslate** support
* **WPML multi-currency** support
* Page builder plugins support: Gutenberg, Elementor, Beaver Builder, WPBakery, Divi Builder
* Custom Product Tabs for WooCommerce plugin support
* Search Exclude plugin support

## Premium Features

Additional features available only in PRO plugin version.
	
* Search **results layouts**
* Search **form layouts**
* **Filters**. Switch between tabs to show different search results
* **Unlimited** amount of search form instances
* Search for custom taxonomies and attributes **archive pages**
* Support for **variable products**: show child products, parent product or both in search results.
* Product **attributes** search ( including custom attributes)
* Product **custom taxonomies** search
* Product **custom fields** search
* **Users** search
* **Advanced settings page** with lot of options
* **Exclude/include** spicific products by its ids, taxonomies or attributes from search results
* Ability to specify **source of image** for search results: featured image, gallery, product content, product short description or set default image if there is no other images
* **Visibility/stock status option** - choose what catalog visibility and stock status must be for product to displayed in search results
* Show product **categories** and **tags** in search results
* AND or OR search logic
* **Add to cart** button in search results
* Support for [WooCommerce Brands plugin](https://woocommerce.com/products/brands/)
* Support for Advanced Custom Fields plugin
* Support for WCFM - WooCommerce Multivendor Marketplace plugin

[Features list](https://advanced-woo-search.com/features/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo)

### More Plugins From Us

Here are some additional plugins that were made with love.

* [Advanced Woo Labels](https://wordpress.org/plugins/advanced-woo-labels/) - advanced labels for WooCommerce products
* [Share This Image](https://wordpress.org/plugins/share-this-image/) - image sharing blugin

### More usefull links

* Plugin [homepage](https://advanced-woo-search.com/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo).
* Plugin [documentation](https://advanced-woo-search.com/guide/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo).
* Follow Advanced Woo Search on [Twitter](https://twitter.com/WooSearch)

== Installation ==

1. Upload advanced-woo-search to the /wp-content/plugins/ directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place the plugin shortcode [aws_search_form] into your template or post-page or just use build-in widget

== Frequently Asked Questions ==

Please visit our [Advanced Woo Search guide](https://advanced-woo-search.com/guide/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo) before requesting any support.

= What is Advanced Woo Search? =

Advanced Woo Search as is advanced search plugin for WooCommerce shops. Its packed with many usefull features like:

* Search by product title, content, short description, SKU, tags, categories, ID, custom fields, attributes, taxonomies.
* Support for variable product and its variations.
* Multilingual plugins support.
* Search and display product tags, categories, custom taxonomies.
* and many more...

Please visit [features page](https://advanced-woo-search.com/features/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo) for full list of available features.

= What are the requirements to use Advanced Woo Search? =

Advanced Woo Search is a plugin for self-hosted WordPress sites, or wordpress.com hosted sites that allow installation of third party plugins.
Plugin requires the following at minimum to work properly:

* WordPress 4.0 or greater
* WooCommerce 3.0.0 or greater
* PHP 5.5 or greater
* MySQL 5.6 or MariaDB 10.0 or greater
* Apache or Nginx server (recommended, but other options may work as well)

= How to insert search form? =

There are several ways you can add plugins search form on your site. The simplest way - is by turning on the **Seamless integration** option from the plugins settings page.

You can also use build-in widget to place plugins search form to your sidebar or any other available widget area.

Or just use shortcode for displaying form inside your post/page:

`[aws_search_form]`

Or insert this function inside php file ( often it used to insert form inside page templates files ):

`echo do_shortcode( '[aws_search_form]' );`

Also please read the guide article about search form placement: [Adding Search Form.](https://advanced-woo-search.com/guide/search-form/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo)

= What is the steps to make this plugin works on my site? =

In order to start using the plugin search form you need to take following steps:

* **Installation**. Install and activate the plugin. You can follow [these steps](https://advanced-woo-search.com/guide/installation/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo) if you face any problems.
* **Index plugin table**. Click on the **Reindex table** button inside the plugin settings page and wait till the index process is finished.
* **Set plugin settings**. Leave it to default values or customize some of them.
* **Add search form**. There are several ways you can add a search form to your site. Use the **Seamless integration** option, shortcode, widget or custom php function. Read more in the guide article: [Adding Search Form](https://advanced-woo-search.com/guide/search-form/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo).
* **Finish!** Now all is set and you can check your search form on the pages where you add it.

= Will this plugin work with my theme? =

Plugin search will works with most of the available WordPress themes. If you faced any problems using the plugin with your theme please [contact support](https://advanced-woo-search.com/contact/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo).

= Is it integrated with my plugin? =

Advanced Woo Search works with many plugins out-of-the-box. For some of the most popular plugins we manually check proper work of integration features. It is the plugins like **Advanced Custom Fields**, **WPML**, **Polylang**, **Elementor**, **Divi Builder**, **BeRocket AJAX Product Filters**, **FacetWP** and many more.

Please read some guide integrations articles: [Integrations](https://advanced-woo-search.com/guide-category/integrations/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo).

Note that if some of the plugin is not presented in the list it doesn't mean that it will not work with Advanced Woo Search. Many plugins will just work without any extra effort. But if you find any problem with your plugin and Advanced Woo Search please [contact support team](https://advanced-woo-search.com/contact/?utm_source=wp-repo&utm_medium=listing&utm_campaign=aws-repo).

= Is this plugin compatible with latest version of Woocommerce? =

Yep. This plugin is always compatible with the latest version of Woocommerce?

== Screenshots ==

1. Search from front-end view
1. Search form in sidebar added as widget
2. Plugin settings page. General options
3. Plugin settings page. Search form options
4. Plugin settings page. Search results options

== Changelog ==

= 2.53 ( 2022-04-25 ) =
* Update - Tested with WC 6.4
* Update - Support for Avada theme and Avada Builder
* Dev - Add aws_relevance_scores filter

= 2.52 ( 2022-04-11 ) =
* Update - Search results styles
* Update - Product description scrapper for search results
* Fix - Remove markup for search form input title

= 2.51 ( 2022-03-28 ) =
* Update - Hooks for index table products sync
* Fix - Search results page filters for default WooCommerce filter widgets
* Fix - Astra theme integration. Bug with search form close button

= 2.50 ( 2022-03-22 ) =
* Update - Tested with WC 6.3
* Update - Integration with Astra theme
* Dev - Add aws_search_data_parameters filter

= 2.49 ( 2022-03-07 ) =
* Update - Admin notices
* Fix - WCFM - WooCommerce Multivendor Marketplace plugin integration. Change store link
* Fix - Terms scrapping for index table
* Dev - Add aws_search_pre_filter_single_product filter
* Dev - Make get_products method public

= 2.48 ( 2022-02-21 ) =
* Add - New admin notices about integrations
* Update - Tested with WC 6.2
* Update - Integration for Woodmart theme
* Update - Support for WCFM - WooCommerce Multivendor Marketplace plugin
* Dev - Add aws_seamless_search_form_filter filter

= 2.47 ( 2022-02-07 ) =
* Update - Do not close AJAX results block when clicking on results item
* Update - Markup for AJAX search results
* Update - Styles for admin page welcome message
* Update - Support for WooCommerce Product Filter by WooBeWoo
* Fix - Bug with admin page scripts loader

= 2.46 ( 2022-01-26 ) =
* Update - Divi builder support. Index content from long description fields
* Fix - Bug with Divi visual builder that trigger error on save

= 2.45 ( 2022-01-24 ) =
* Update - Tested with WC 6.1
* Update - Divi theme support. Autofocus on search field in the header
* Update - Admin page 'Get started' notice
* Fix - Remove error notice on first search if cache table not exist
* Dev - Add aws_results_force_position js filter
* Dev - Remove unused parameter for wp_insert_post hook

= 2.44 ( 2022-01-10 ) =
* Fix - FacetWP plugin compatibility issue

= 2.43 ( 2021-12-20 ) =
* Add - Support for Orchid Store theme
* Update - Tested with WC 6.0
* Update - Integration for Product Visibility by User Role for WooCommerce plugin
* Update - Admin page notices display
* Update - Support for Basel theme

= 2.42 ( 2021-12-07 ) =
* Add - Support for Basel theme
* Add - Support for Rey theme
* Update - FacetWP plugin integration
* Update - Number of search results per page option
* Fix - Bug with search page query detection

= 2.41 ( 2021-11-22 ) =
* Add - Support for WooCommerce Protected Categories plugin
* Fix - JS seamless integration issue

= 2.40 ( 2021-11-10 ) =
* Fix - Bug with condition function

= 2.39 ( 2021-11-09 ) =
* Add - Support for Falang translation plugin
* Add - Support for WR Nitro theme
* Add - Support for Deals for WooCommerce plugin
* Update - Tested with WC 5.9
* Update - Seamless integration js script
* Update - Botiga theme support. Fix styles and back to top button
* Update - Support for OceanWP theme
* Fix - PHP 8.0 notice for Divi builder search module
* Fix - Prevent unexpected output when running search function
* Fix - URL for plugin assets. Remove additional slash
* Fix - Issue with SKU search for singular terms

= 2.38 ( 2021-10-25 ) =
* Add - Support for WooCommerce Product Filter by WooBeWoo plugin
* Add - Quantity change buttons for products search results. Visible on mobile devices
* Update - Tested with WC 5.8
* Fix - WOOCS - WooCommerce Currency Switcher plugin support for AJAX pricing update<

= 2.37 ( 2021-10-11 ) =
* Add - Support for B2BKing plugin
* Update - Hide out-of-stock items by default if corresponding WC option is enabled
* Update - Tested with WC 5.7

= 2.36 ( 2021-09-21 ) =
* Update - Woodmart theme support. Update search results page pagination links
* Update - Avada theme support. Set number of products per search results page
* Fix - Taxonomies search by description. Not strip special characters from the search query
* Fix - Error with not valid scripts loaded for new widgets block editor
* Fix - Ultimate Member plugin support
* Fix - Avada theme support for search results page

= 2.35 ( 2021-08-31 ) =
* Add - Seamless integration for Modern Store theme
* Update - WholeSale plugin support. Change products filter script to improve execution time
* Update - FacetWP plugin integration. Fix problem with search results page
* Update - Plugin settings page. Change performance options
* Update - Tested with WC 5.6
* Fix - Admin area descriptions spelling

= 2.34 ( 2021-08-16 ) =
* Add - New options for products search results page
* Update - Support for WPML plugin. Use default product language if no translation and fallback to default language option is enabled
* Fix - Error with special characters inside regular expressions
* Fix - Error during database creation that was caused by wrong COLLATION value
* Fix - Search results page output for Avada theme

= 2.33 ( 2021-08-02 ) =
* Fix - Parse errors during index process
* Fix - Possible parse errors during AJAX search
* Fix - Astra theme seamless integration search form markup

= 2.32 ( 2021-07-19 ) =
* Add - Support for Products Visibility by User Roles plugin
* Add - Support for WPBakery plugin. Added search form element for page builder
* Update - Settings page styles for tables
* Update - Tested with WC 5.5
* Fix - Bug with not working pagination for search results page that was created with Load Dynamic Content for Elementor plugin
* Fix - WooCommerce Product Filter by WooBeWoo plugin integration. Fix issue with filtering by attributes
* Fix - Search form styles for Divi theme
* Dev - Use new block_categories_all filter for blocks
* Dev - Add awsLoaded js event

= 2.31 ( 2021-07-05 ) =
* Update - Plugin settings page
* Dev - Add aws_create_index_table_sql filter

= 2.30 ( 2021-06-21 ) =
* Add - Support for Advanced Woo Labels plugin
* Update - WCFM plugin fix for vendors shop search. Fix searching for vendor taxonomies
* Update - Fix styles for YITH Wishlist plugin
* Update - Tested with WC 5.4

= 2.29 ( 2021-06-07 ) =
* Add - Woostify theme support
* Dev - Fix search page filters

= 2.28 ( 2021-05-24 ) =
* Update - Tested with WC 5.3
* Update - Index table improvement. Add a new index
* Fix - Bug with search results for translated terms archive pages

= 2.27 ( 2021-05-10 ) =
* Fix - WOOF - WooCommerce Products Filter plugin integration
* Dev - Fix posts_per_page filter for search results page
* Dev - Update files loading

= 2.26 ( 2021-04-27 ) =
* Update - Tested with WC 5.2
* Update - Wholesale Pricing plugin support
* Update - BeRocket WooCommerce AJAX Products Filter support. Fix bug with slugs in URL for attributes filters
* Update - Disable automatic re-index after WP All Import plugin finish importing
* Update - Notice for plugin settings page
* Update - Search form markup for label tag
* Fix - Ajax request issue with sending parameters
* Fix - CoCart plugin issue with empty cart when performing AJAX search
* Fix - Bug with the empty search results page

= 2.25 ( 2021-04-12 ) =
* Add - Royal theme integration
* Update - Aurum theme integration
* Update - Ultimate Member plugin support
* Dev - Add deleted_post hook for index table sync
* Dev - Add aws_js_seamless_searchbox_markup filter

= 2.24 ( 2021-03-25 ) =
* Fix - Parent theme name detection
* Dev - Update helper functions

= 2.23 ( 2021-03-22 ) =
* Add - Performance options
* Add - Support for TI WooCommerce Wishlist plugin
* Add - Support for Fury theme
* Add - Support for Salient theme
* Add - Support for Bazar theme
* Add - Support for Claue theme
* Add - Integration for Urna theme
* Update - BeRocket AJAX filters plugin support. Fix issue when used slugs for terms instead of IDs inside URL
* Update - BeRocket AJAX filter support. Include parent terms filters
* Update - Gutenberg block markup
* Update - Admin page styles
* Fix - If no search sources is set - do not show the products search results
* Dev - Add aws_admin_page_options_current filter

= 2.22 ( 2021-02-19 ) =
* Add - Support for WOOF - WooCommerce Products Filter plugin
* Fix - Search form widget layout
* Dev - Add aws_search_page_query filter

= 2.21 ( 2021-02-15 ) =
* Add - Plugin search form module for Gutenberg
* Add - Label element for search form
* Add - Integration for BoxShop theme
* Add - Aurum theme integration. Add search form to mobile menu
* Update - Search form widget. Remove title markup if title value is empty
* Update - Porto theme integration

= 2.20 ( 2021-02-02 ) =
* Add - Beaver Builder plugin support
* Update - Integration for Electro theme
* Update - Porto theme integration
* Fix - Hide 'Show all results' button if search results page is disabled
* Fix - Scrolling for search results inside pop-ups via 'Popups for Divi' plugin

= 2.19 ( 2021-01-18 ) =
* Add - Support for WooCommerce Product Filter by WooBeWoo plugin
* Add - Integration for Martfury theme
* Add - Integration for ATUM Inventory Management for WooCommerce plugin ( Product level addon ). Hide not sellable products
* Update - Dynamic strings translation. Load translation from .po file for default strings if no dynamic translation specified
* Update - WCFM - Multivendor Marketplace plugin integration. Add vendors shop name and logo inside search results list
* Fix - GA tracking code
* Fix - Do not index and search for password protected products
* Fix - Hide product with visibility = catalog from the AJAX search results
* Dev - Update taxonomies search results response. Add parent term value.
* Dev - Add aws_search_page_custom_data filter

= 2.18 ( 2021-01-04 ) =
* Add - Support for Walker WordPress theme
* Update - Elementor plugin support. Add integration with Elementskit plugin header search
* Update - Search results styles. Fit search results box to search form width
* Update - Admin menu item  position
* Update - Add Premium tab inside plugin settings page
* Dev - Update aws_show_mobile_layout filter. Change it to aws_show_modal_layout and give option to use modal layout on desktop

= 2.17 ( 2020-12-14 ) =
* Dev - Specify global $product variable for search results items
* Dev - Add aws_show_mobile_layout js filter

= 2.16 ( 2020-11-30 ) =
* Add - Welcome message
* Add - Support for Elessi Theme
* Update - Search results box scrollbar styles
* Dev - Add aws_results_append_to js filter
* Dev - Update constants declaration

= 2.15 ( 2020-11-16 ) =
* Update - Support for WCFM - WooCommerce Multivendor Marketplace plugin: add seamless integration for the vendors shop page, limit search results to vendor products only
* Update - Settings page styles
* Dev - Add aws_admin_page_options filter

= 2.14 ( 2020-11-02 ) =
* Update - Elementor search page support
* Update - Divi Builder search page support
* Update - Astra theme integration
* Update - Storefront theme integration for footer search form

= 2.13 ( 2020-10-19 ) =
* Add - FacetWP plugin integration. [Read more](https://advanced-woo-search.com/guide/facetwp/)
* Add - Support for 'Product Visibility by User Role for WooCommerce' plugin. [Read more](https://advanced-woo-search.com/guide/product-visibility-by-user-role-for-woocommerce/)
* Add - Support for Avada theme default shop filters
* Update - Search results page query
* Update - Divi builder search form styles
* Dev - Add aws_products_order_by filter
* Dev - Add aws_index_complete
* Dev - Add aws_create_index_table

= 2.12 ( 2020-10-05 ) =
* Fix - Results display for Divi builder search page template
* Fix - Plugin search module for Divi builder
* Fix - 'BeRocket Advanced AJAX Product Filters for WooCommerce' plugin integration
* Fix - Bug with 'Product Sort and Display for WooCommerce' plugin

= 2.11 ( 2020-09-21 ) =
* Add - Venedor theme support
* Update - Option to add description for archive pages inside search results
* Update - Support for OceanWP theme
* Update - Diacritic chars list
* Fix - Overwrite WooCommerce global products count if it is set to zero
* Fix - Bug with get_image_id function
* Fix - Settings page typos

= 2.10 ( 2020-09-07 ) =
* Update - Storefront theme integration
* Fix - Search results page output
* Dev - Add aws_results_layout js filter

= 2.09 ( 2020-08-23 ) =
* Update - Support for latest WooCommerce version
* Update - AJAX request fix

= 2.08 ( 2020-08-17 ) =
* Update - Strip some new special characters form search query

= 2.07 =
* Add - Seamless integration for Woodmart theme
* Update - Admin ajax requests
* Fix - Synonyms search for taxonomies archives

= 2.06 =
* Fix - Search results layout positions
* Fix - Search results page query
* Update - Wholesale plugin support. Add categories excluding
* Dev - Add aws_search_query_string filter
* Dev - Add aws_image_size filter

= 2.05 =
* Add - Support for Perfect Brands for WooCommerce plugin
* Update - Show ajax block in top if no space at bottom
* Update - Taxonomies search
* Dev - Add aws_results_html js filter

= 2.04 =
* Add - Add BeRocket WooCommerce AJAX Products Filter plugin support
* Add - Add WCFM - WooCommerce Multivendor Marketplace plugin support for users search
* Dev - Add aws_products_search_page_filtered filter
* Dev - Add aws_search_page_filters filter

= 2.03 =
* Add - Seamless integration for Elementor plugin search module
* Add - Widget for  Elementor plugin
* Add - Module for Divi Builder plugin
* Update - Styles for settings page

= 2.02 =
* Add - German language translation
* Update - Show re-index table notice only for relevant users

= 2.01 =
* Add - Support for Ultimate Member plugin
* Add - Support for WP all import plugin
* Add - Allow some html tags for "Nothing found" message
* Update - Remove Divi builder plugin dynamic text shortcodes from the product content
* Add - Jupiter theme seamless integration
* Fix - Bug with not sync product stock status with plugin index table on status change
* Fix - Quantity ordering for search results page
* Dev - aws_before_strip_shortcodes filter
* Dev - aws_search_page_results filter
* Dev - aws-focus class name when focus on search form

= 2.00 =
* Update - Search page ordering. Add order by quantity
* Update - Remove sql query from output
* Dev - Add aws_products_order filter

= 1.99 =
* Update - OceanWp theme integration
* Fix - Bug with product short description search
* Fix - SKU string translation
* Dev - Add aws_reindex_product action to re-index single product by its ID

= 1.98 =
* Update - Add SQL query inside responce
* Update - Seamless integration JS method
* Update - Divi theme integration

= 1.97 =
* Update - Fully compatible with WooCommerce 4.0
* Update - Increase memory and time limit for index process
* Update - Ocean WP theme integration update
* Dev - Add new parameter for aws_extracted_terms filter
* Dev - Update taxonomies search class

= 1.96 =
* Add - Mobile full screen search option
* Fix - Search form markup

= 1.95 =
* Fix - Ajax request cache problem
* Fix - Polylang plugin fix search results page URL

= 1.94 =
* Update - Ajax function
* Update - Twenty Twenty theme integration
* Update - Default settings values. Enable search page support by default
* Fix - WP AutoTerms plugin conflict
* Fix - Elementor plugin search page template
* Fix - Visibility get function for old WooCommerce versions

= 1.93 =
* Add - Synonyms support for taxonomies search
* Add - aws_tax_search_data filter
* Fix - Taxonomies search
* Fix - Remove potential link from product pricing

= 1.92 =
* Update - Flatsome theme support
* Update - Taxonomies search query
* Fix - Bug with exclude filter for taxonomies search
* Add - aws_search_tax_exclude filter

= 1.91 =
* Add - aws_terms_search_query filter
* Add - aws_search_terms_description filter
* Add - Support for Elementor pop-up templates
* Fix - taxonomies search with special characters bug
* Fix - bug with search results page cache
* Update - aws_searchbox_markup filter new parameter

= 1.90 =
* Update - Search query fix

= 1.89 =
* Add - Highlight option
* Fix - Index method bug

= 1.88 =
* Add - WooCommerce Product Table plugin support
* Add - aws_highlight_tag filter
* Update - Search query speed-up. Removed unused lines
* Update - Avada theme integration
* Update - Settings page text

= 1.87 =
* Fix - Bug with search results sorting
* Update - Hide disabled variations from search

= 1.86 =
* Update - Speed-up index process
* Update - Speed-up search
* Update - Generatepress theme integration
* Update - Ocean WP theme integration
* Update - Get shortcodes content during the index
* Add - awsShowingResults js event
* Fix - Search page bug with multiple searches per load

= 1.85 =
* Add - Seamless integration with Divi builder
* Add - Seamless integration for Shopkeeper theme
* Add - aws_js_seamless_selectors filter
* Update - When fail index process will start from latest added product, not from the start
* Update - Search results box layout fixes
* Update - qTranslate plugin fix
* Fix - Synonyms support for phrases

= 1.84 =
* Update - Add indexes for table
* Update - Cache query sql

= 1.83 =
* Add - Support for Maya shop theme
* Add - Support for Generatepress theme
* Update - Plurals support

= 1.82 =
* Fix - Bug with WooCommerce attributes filter widget. Now its display proper number of attributes on search page
* Fix - Filter by attributes on search results page. Now search results works proper with multiple attributes filters
* Fix - WooCommerce price filter widget bug
* Fix - Search page queries
* Add - aws_index_apply_filters filter
* Update - aws_indexed_data filter filter
* Update - Improve synonyms support

= 1.81 =
* Add - Support for Google Analytics site search feature
* Update - Plugin settings page
* Fix - Bug with search results page products count

= 1.80 =
* Add - Rtl text support
* Add - Uninstall file to clear all plugin data during uninstall
* Fix - Divi theme integrations

= 1.79 =
* Add - Synonyms support
* Add - Search by product ID
* Fix - Divi theme integration bug with double search form
* Fix - Bug with cache for search results
* Fix - Support for WooCommerce hooks
* Fix - Support for search exclude plugin

= 1.78 =
* Update - Better plurals search support
* Add - Divi theme seamless integration
* Add - Wholesale plugin support

= 1.77 =
* Fix - Order by price bug on search results page
* Update - plugin text domain
* Add - ru translation ( thanks to @hdelta045 )
* Update - styles for search form
* Update - styles for search form on mobile devices
* Update - search results image size
* Add - seamless integration for Astra theme
* Update - settings page text

= 1.76 =
* Update - Markup of search form
* Dev - Add new parameters for ajax call

= 1.75 =
* Fix - Brands filter for search results page
* Fix - Polylang plugin search results page URL
* Update - filters parameters

= 1.74 =
* Fix - Issue with not working search page when using Elementor page builder
* Fix - Issue with not working search page when using Divi page builder
* Update - Order by statement for products

= 1.73 =
* Add - Relevance search for terms
* Dev – Add aws_search_terms_number filter

= 1.72 =
* Fix - Tax search exact matching bug
* Fix - Empty tax in search results bug
* Update - Settings page text

= 1.71 =
* Fix - Index table sync for WPML translations

= 1.70 =
* Dev - Update security checks
* Dev - Update nonce check

= 1.69 =
* Dev - Update security checks
* Dev - Add aws_front_data_parameters filter

= 1.68 =
* Update - Styles for plugin settings page
* Dev - Add aws_search_results_tax_archives filter
* Dev - Clear code for all unused stuff

= 1.67 =
* Dev - Add aws_search_query_array filter
* Dev - Send page url with ajax request

= 1.66 =
* Update search page support

= 1.65 =
* Fix YITH WooCommerce Ajax Product Filter plugin support

= 1.64 =
* Fix issue with Polylang plugin support
* Fix filters for search results page
* Add aws_title_search_result filter
* Add aws_excerpt_search_result filter

= 1.63 =
* Update porto theme support
* Add aws_search_tax_results filter
* Add support for old WooCommerce versions ( 2.x )

= 1.62 =
* Fix Google Analytics events
* Fix jQuery errors
* Add Enfold theme support
* Add Porto theme support
* Add aws_indexed_data filter

= 1.61 =
* Fix stopwords
* Fix markup for finded words in products content

= 1.60 =
* Update cron job action
* Update Protected Categories plugin integration
* Update B2B Market plugin integration
* Add WC Marketplace plugin integration
* Add new option to disable auto sync for index page
* Add aws_filter_yikes_woo_products_tabs_sync filter
* Add aws_search_data_params filter
* Add aws_search_pre_filter_products filter

= 1.59 =
* Add aws_search_start action
* Update caching naming
* Update cron job action. Now its must works fine with large amount of products
* Fix singular form of terms
* Add aws_search_current_lang filter
* Add B2B Market plugin support
* Add Datafeedr WooCommerce Importer plugin support
* Add option to hide price for out-of-stock products

= 1.58 =
* Add option for preventing submit of empty search form
* Add support for Protected Categories plugin
* Add aws_exclude_products filter
* Add aws_terms_exclude_product_cat filter
* Add aws_terms_exclude_product_tag filter

= 1.57 =
* Update search query string
* Fix style for search icon
* Fix search field style
* Fix clear button style

= 1.56 =
* Update stopwords list
* Add search box layout options

= 1.55 =
* Update search behavior on text paste

= 1.54 =
* Update plugin index table
* Update WooCommerce version support

= 1.53 =
* Fix bug with search results page ordering
* Add svg loading icon
* Mark featured products in search results
* Add aws_search_results_products_ids filter

= 1.52 =
* Fix terms translation for multilingual plugins
* Update special chars filter
* Add diacritic chars filter

= 1.51 =
* Update seamless integration filter hook
* Fix WPML language select

= 1.50 =
* Fix bug with cyrillic letters search

= 1.49 =
* Add option to set text for 'show more' button
* add aws_search_terms filter

= 1.48 =
* Add option to display 'Clear search results' buttom on desktop devices 

= 1.47 =
* Add seamless integration option
* Fix styling

= 1.46 =
* Add support for WPML plugin multi currency
* Fix css styles

= 1.45 =
* Fix bug with re-index process ( too much requests error )
* Add timeout for keyup event
* Fix bug with special characters search

= 1.44 =
* Make SKU string in search results translatable
* Strip some new special chars from products content
* Add 'aws_extracted_string' and 'aws_extracted_terms' filters
* Fix bug with empty excerpt

= 1.43 =
* Add 'aws_search_results_all' filter
* Update WPML string translation
* Fix bug with term_source column in index table

= 1.42 =
* Add option to display ‘View All Results’ button in the bottom of search results list
* Fix bug with stop words option
* Fix bug with links in 'noresults' fiels
* Add 'aws_search_results_products', 'aws_search_results_categories', 'aws_search_results_tags' filters

= 1.41 =
* Add new column form index table - term_id. With id help it is possible to sunc any changes in product term
* Add shortcode to settings page
* Update search page integration

= 1.40 =
* Fix bug with not working stop-words for taxonomies
* Fix bug with hided search form if its parent node has fixed layout
* Add second argument for the_title and the_content filters
* Update view of settings page

= 1.39 =
* Add option to disable ajax search results

= 1.38 =
* Add 'Clear form' buttom for search form on mobile devices
* Fix bug with not srtiped shortcodes on product description
* Fix bug with aws_reindex_table action

= 1.37 =
* Add 'aws_indexed_content', 'aws_indexed_title', 'aws_indexed_excerpt' filters

= 1.36 =
* Update re-index function
* Add support for custom tabs content made with Custom Product Tabs for WooCommerce plugin
* Add support for Search Exclude plugin

= 1.35 =
* Fix issue with position of search results box
* Add 'aws_page_results' filter
* Add support for 'order by' box in search results page
* Fix issue fix re-index timeout

= 1.34 =
* Add arrows navigation for search results
* Fix bug with php 7+ vesion

= 1.33 =
* Fix re-index bug
* Fix bug with search page

= 1.32 =
* Fix shortcodes stripping from product content
* Fix qTranslate plugin issue with product name 
* Fix reindex issue

= 1.31 =
* Add WooCommerce version check

= 1.30 =
* Add qTranslate plugin support

= 1.29 =
* Fix bug with search results page

= 1.28 =
* Add caching table to store cached search result instead of store them in wp_options table
* Fix deprecated action 'woocommerce_variable_product_sync'

= 1.27 =
* Add option to show stock status in search results
* Add 'aws_special_chars' filter

= 1.26 =
* Add Polylang plugin support

= 1.25 =
* Add markdown support for 'Nothing found' field
* Fix WPML bug

= 1.24 =
* Add plurals support
* Fix Polylang plugin conflict
* Fix SKU search bug
* Add function for cron job

= 1.23 =
* Add 'Stop-words list' option

= 1.22 =
* Fix reindex bug
* Hide empty taxonomies from search results
* Add support for old WooCommerce versions

= 1.21 =
* Fix search page switching to degault language

= 1.20 =
* Add WPML, WooCommerce Multilingual support

= 1.19 =
* Fix bugs

= 1.18 =
* Fix bugs

= 1.17 =
* Fix layout bugs
* Fix bugs with older versions of WooCommerce
* Add Google Analytics support

= 1.16 =
* Option for 'Out of stock' products
* Fix bugs

= 1.15 =
* Exclude 'Out of stock' products from search
* Fix bugs

= 1.14 =
* Fix number of search results on search page
* Exclude draft products from search
* Fix bugs

= 1.13 =
* Add support for variable products
* Fix bugs

= 1.12 =
* Fix small bugs in search results output

= 1.11 =
* Fix issue with indexing large amount of products
* Fix bag with search page query

= 1.10 =
* Update search results page
* Fix some layout issues

= 1.09 =
* Make indexing of the products content much more fuster
* Fix several bugs

= 1.08 =
* Update check for active WooCommerce plugin
* Add hungarian translation ( big thanks to hunited! )

= 1.07 =
* Exclude hidden products from search
* Update translatable strings

= 1.06 =
* Cache search results to increase search speed

= 1.05 =
* Improve search speed

= 1.04 =
* Fix issue with SKU search
* Add option to display product SKU in search results

= 1.03 =
* Add search in product terms ( categories, tags )
* Fix issue with not saving settings

= 1.02 =
* Add single page search for 'product' custom post type
* Fix problem with dublicate products in the search results

= 1.01 =
* Fix problem with result block layout

= 1.00 =
* First Release