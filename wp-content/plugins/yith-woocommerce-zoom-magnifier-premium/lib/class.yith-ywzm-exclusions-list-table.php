<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * This file belongs to the YIT Plugin Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Implements the YWZM_Exclusions_List_Table class.
 *
 * @class   YWZM_Exclusions_List_Table
 * @package YITH
 * @since   2.0.0
 * @author  YITH
 * @extends WP_List_Table
 */
class YWZM_Exclusions_List_Table extends WP_List_Table {

	/**
	 * Class constructor method.
	 *
	 * @since 3.1.0
	 */
	public function __construct() {
		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => 'exclusion',     // singular name of the listed records.
				'plural'   => 'exclusions',    // plural name of the listed records.
				'ajax'     => false,          // does this table support ajax?.
			)
		);

		$this->handle_bulk_action();

	}


	/**
	 * Get bulk actions
	 *
	 * @return array
	 * @since  3.1.0
	 */
	public function get_bulk_actions() {
		return array(
			'remove' => __( 'Remove', 'yith-woocommerce-zoom-magnifier' ),
		);
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination, which
	 * includes our Filters: Customers, Products, Availability Dates
	 *
	 * @param string $which the placement, one of 'top' or 'bottom'.
	 * @since 3.1.0
	 * @see WP_List_Table::extra_tablenav();
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			// Customers, products.
			$this->render_type_filter();

			submit_button(
				__( 'Filter', 'yith-woocommerce-zoom-magnifier' ),
				'button',
				false,
				false,
				array(
					'id'    => 'post-query-submit',
					'class' => 'ywzm_filter_button',
				)
			);
		}
	}


	/**
	 * Show the filter for type.
	 */
	protected function render_type_filter() {

		$current_type = isset( $_REQUEST['type'] ) && ! empty( $_REQUEST['type'] ) ? $_REQUEST['type'] : '';  // phpcs:ignore
		$options      = array(
			''            => esc_html__( 'All types', 'yith-woocommerce-zoom-magnifier' ),
			'product'     => esc_html__( 'Products', 'yith-woocommerce-zoom-magnifier' ),
			'product_cat' => esc_html__( 'Categories', 'yith-woocommerce-zoom-magnifier' ),
			'product_tag' => esc_html__( 'Tags', 'yith-woocommerce-zoom-magnifier' ),
		);
		?>
		<div class="alignleft actions">
			<select name="type" id="type">
				<?php foreach ( $options as $key => $option ) : ?>
					<option
						value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_type, $key ); ?>><?php echo esc_html( $option ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}


	/* === COLUMNS METHODS === */

	/**
	 * Print default column content
	 *
	 * @param mixed  $item Item of the row.
	 * @param string $column_name Column name.
	 *
	 * @return string Column content
	 * @since 3.1.0
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'type':
				return $this->get_type_label( $item['type'] );
			case 'name':
				if ( 'product' === $item['type'] ) {
					$value = '<div class="ywzm-excl-name-wrapper">' . $item['image'] . ' ' . $item['name'] . '</div>';
					return $value;
				} else {
					return $item['name'];
				}
			case 'action':
				return '<ul class="actions"><li class="action__trash" data-field-type="' . $item['type'] . '" data-field-id="' . $item['id'] . '"></li></ul>';
		}
	}

	/**
	 * Print product column content
	 *
	 * @param mixed $item Item of the row.
	 *
	 * @return string Column content.
	 * @since 3.1.0
	 */
	public function column_product( $item ) {
		if ( ! isset( $item['name'] ) || empty( $item['name'] ) ) {
			return '';
		}

		$column = sprintf( '<strong><a href="%s">%s</a></strong>', get_edit_post_link( $item['id'] ), $item['name'] );

		return $column;
	}

	/**
	 * Print price column content
	 *
	 * @param mixed $item Item of the row.
	 *
	 * @return string Column content.
	 * @since 3.1.0
	 */
	public function column_price( $item ) {
		if ( ! isset( $item['price'] ) || empty( $item['price'] ) ) {
			return '';
		}

		$column = wc_price( $item['price'] );

		return $column;
	}


	/**
	 * Print actions column content
	 *
	 * @param mixed $item Item of the row.
	 *
	 * @return string Column content.
	 * @since 3.1.0
	 */
	public function column_actions( $item ) {

		$args = array(
			'remove_prod_exclusion' => $item['id'],
			'remove_nonce'          => wp_create_nonce( 'yith_ywzm_remove_exclusions_prod' ),
		);

		$column = sprintf( '<a href="%s" class="button button-secondary yith-ywzm-remove-exclusion">%s</a>', esc_url( add_query_arg( $args ) ), __( 'Delete', 'yith-woocommerce-zoom-magnifier' ) );

		return $column;
	}

	/**
	 * Returns columns available in table
	 *
	 * @return array Array of columns of the table.
	 * @since 3.1.0
	 */
	public function get_columns() {
		$columns = array(
			'cb'     => '<input type="checkbox" />',
			'name'   => __( 'Name', 'yith-woocommerce-zoom-magnifier' ),
			'type'   => __( 'Type', 'yith-woocommerce-zoom-magnifier' ),
			'action' => '',
		);

		return $columns;
	}

	/**
	 * Column cb.
	 *
	 * @param array $item Instance.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%1$s[%3$s-%2$s]" value="%2$s" />', $this->_args['singular'], $item['id'], $item['type'] );
	}

	/**
	 * Returns column to be sortable in table
	 *
	 * @return array Array of sortable columns.
	 * @since 3.1.0
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'product' => array( 'products_name', false ),
			'price'   => array( 'products_price', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Process Bulk Actions*
	 */
	public function handle_bulk_action() {

		$action = $this->current_action();

		$items = isset( $_REQUEST[ $this->_args['singular'] ] ) ? $_REQUEST[ $this->_args['singular'] ] : array(); //phpcs:ignored
		if ( ! empty( $action ) && -1 != $action && ! empty( $items ) ) { //phpcs:ignore

			$exclusions_prod = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-prod-list' ) ) );
			$exclusions_cat  = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-cat-list' ) ) );
			$exclusions_tag  = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-tag-list' ) ) );

			if ( 'remove' === $action ) {
				$update = array();

				foreach ( $items as $key => $item ) {
					$type = explode( '-', $key );
					if ( ! in_array( $type[0], $update ) ) { //phpcs:ignore
						$update[] = $type[0];
					}

					switch ( $type[0] ) {
						case 'product':
							$key = array_search( $item, $exclusions_prod ); //phpcs:ignore
							if ( false !== $key ) {
								unset( $exclusions_prod[ $key ] );
							}
							break;
						case 'product_cat':
							$key = array_search( $item, $exclusions_cat ); //phpcs:ignore
							if ( false !== $key ) {
								unset( $exclusions_cat[ $key ] );
							}
							break;
						case 'product_tag':
							$key = array_search( $item, $exclusions_tag ); //phpcs:ignore
							if ( false !== $key ) {
								unset( $exclusions_tag[ $key ] );
							}
							break;
					}
				}

				if ( in_array( 'product', $update, true ) ) {
					update_option( 'yith-ywzm-exclusions-prod-list', implode( ',', $exclusions_prod ) );
				}

				if ( in_array( 'product_cat', $update, true ) ) {
					update_option( 'yith-ywzm-exclusions-cat-list', implode( ',', $exclusions_cat ) );
				}

				if ( in_array( 'product_tag', $update, true ) ) {
					update_option( 'yith-ywzm-exclusions-tag-list', implode( ',', $exclusions_tag ) );
				}
			}
		}

	}

	/**
	 * Prepare items for table
	 *
	 * @return void
	 * @since 3.1.0
	 */
	public function prepare_items() {

		// sets pagination arguments.
		$per_page     = 20;
		$current_page = $this->get_pagenum();

		// sets columns headers.
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$exclusion_items = array();

		$current_type = isset( $_REQUEST['type'] ) && ! empty( $_REQUEST['type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['type'] ) ) : ''; //phpcs:ignore

		if ( empty( $current_type ) || 'product' === $current_type ) {
			$exclusions_products = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-prod-list' ) ) );
			if ( ! empty( $exclusions_products ) ) {
				foreach ( $exclusions_products as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( $product ) {
						$new_item = array(
							'type'  => 'product',
							'id'    => $product_id,
							'name'  => $product->get_formatted_name(),
							'image' => $product->get_image( 'shop_thumbnail' ),
						);

						$exclusion_items[] = $new_item;
					}
				}
			}
		}

		if ( empty( $current_type ) || 'product_cat' === $current_type ) {
			$exclusions_cat = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-cat-list' ) ) );
			if ( ! empty( $exclusions_cat ) ) {
				foreach ( $exclusions_cat as $cat_id ) {
					$category = get_term_by( 'id', $cat_id, 'product_cat' );
					if ( $category ) {
						$new_item = array(
							'type' => 'product_cat',
							'id'   => $cat_id,
							'name' => $category->name,
						);

						$exclusion_items[] = $new_item;
					}
				}
			}
		}

		if ( empty( $current_type ) || 'product_tag' === $current_type ) {
			$exclusions_tag = array_filter( explode( ',', get_option( 'yith-ywzm-exclusions-tag-list' ) ) );
			if ( ! empty( $exclusions_tag ) ) {
				foreach ( $exclusions_tag as $tag_id ) {
					$tag = get_term_by( 'id', $tag_id, 'product_tag' );
					if ( $tag ) {
						$new_item = array(
							'type' => 'product_tag',
							'id'   => $tag_id,
							'name' => $tag->name,
						);

						$exclusion_items[] = $new_item;
					}
				}
			}
		}

		$total_items = count( $exclusion_items );

		// retrieve data for table.

		$this->items = array_slice( $exclusion_items, ( $current_page - 1 ) * $per_page, $per_page );

		// sets pagination args.
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Return the label of the type
	 *
	 * @param string $key type of item.
	 *
	 * @return string
	 */
	private function get_type_label( $key ) {
		$type = array(
			'product'     => esc_html__( 'Product', 'yith-woocommerce-zoom-magnifier' ),
			'product_cat' => esc_html__( 'Category', 'yith-woocommerce-zoom-magnifier' ),
			'product_tag' => esc_html__( 'Tag', 'yith-woocommerce-zoom-magnifier' ),
		);

		return isset( $type[ $key ] ) ? $type[ $key ] : $key;
	}

}
