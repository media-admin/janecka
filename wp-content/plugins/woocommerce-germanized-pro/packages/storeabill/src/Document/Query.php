<?php

namespace Vendidero\StoreaBill\Document;

use WC_Object_Query;
use WP_Meta_Query;
use WP_Date_Query;
use wpdb;

defined( 'ABSPATH' ) || exit;

/**
 * Abstract Document Query Class
 *
 * Extended by classes to provide a query abstraction layer for safe object searching.
 *
 * @version  1.0.0
 * @package  StoreaBill/Abstracts
 */
abstract class Query extends WC_Object_Query {

	protected $args = array();

	protected $query_fields = array();

	protected $query_from = '';

	protected $query_where = '';

	protected $meta_query = null;

	protected $query_limit = '';

	protected $query_orderby = '';

	protected $request = '';

	protected $results = null;

	protected $total_documents = 0;

	protected $max_num_pages = 0;

	abstract public function get_document_type();

	public function get_type() {
		return 'simple';
	}

	/**
	 * Get the default allowed query vars.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array(
			'status'            => array_keys( sab_get_document_statuses( $this->get_document_type() ) ),
			'parent_id'         => '',
			'type'              => $this->get_type(),
			'number'            => '',
			'formatted_number'  => '',
			'date_created'      => '',
			'date_modified'     => '',
			'date_custom'       => '',
			'date_custom_extra' => '',
			'customer_id'       => '',
			'author_id'         => '',
			'country'           => '',
			'reference_id'      => '',
			'reference_type'    => '',
			'order'             => 'DESC',
			'orderby'           => 'date_created',
			'return'            => 'objects',
			'page'              => 1,
			'limit'             => get_option( 'posts_per_page' ),
			'offset'            => '',
			'search'            => '',
			'search_columns'    => array(),
			'paginate'          => false,
		);
	}

	public function get_query_var( $var ) {
		return array_key_exists( $var, $this->query_vars ) ? $this->query_vars[ $var ] : null;
	}

	public function get_max_num_pages() {
		return $this->max_num_pages;
	}

	/**
	 * Get documents matching the current query vars.
	 *
	 * @return Document[] Array containing Documents.
	 */
	public function get_documents() {
		/**
		 * Filter to adjust query arguments passed to a document query.
		 *
		 * @param array $args The arguments passed.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		$args = apply_filters( 'storeabill_document_query_args', $this->get_query_vars() );
		$args = sab_load_data_store( $this->get_document_type() . '-' . $this->get_type() )->get_query_args( $args );

		$this->query( $args );

		/**
		 * Filter to adjust the document query result.
		 *
		 * @param Document[] $results Document results.
		 * @param array      $args The arguments passed.
		 *
		 * @since 1.0.0
		 * @package Vendidero/StoreaBill
		 */
		return apply_filters( 'storeabill_document_query', $this->results, $args );
	}

	public function get_total() {
		return $this->total_documents;
	}

	/**
	 * Query documents.
	 *
	 * @param array $query_args
	 */
	protected function query( $query_args ) {
		global $wpdb;

		$this->args = $query_args;

		$this->parse_query();
		$this->prepare_query();

		$qv =& $this->args;

		$this->results = null;

		if ( null === $this->results ) {
			$found_rows = '';

			if ( $qv['paginate'] && ! empty( $this->query_limit ) ) {
				$found_rows = 'SQL_CALC_FOUND_ROWS';
			}

			$this->request = "SELECT $found_rows $this->query_fields $this->query_from $this->query_where $this->query_orderby $this->query_limit";

			if ( is_array( $qv['return'] ) || 'objects' == $qv['return'] ) {
				$this->results = $wpdb->get_results( $this->request );
			} else {
				$this->results = $wpdb->get_col( $this->request );
			}

			$this->set_found_objects( $qv, $this->query_limit );
		}

		if ( ! $this->results ) {
			return;
		}

		if ( 'objects' == $qv['return'] ) {
			foreach ( $this->results as $key => $document ) {
				$this->results[ $key ] = sab_get_document( $document );
			}
		}
	}

	protected function set_found_objects( $qv, $limits ) {
		global $wpdb;

		if ( ( is_array( $this->results ) && ! $this->results ) ) {
			return;
		}

		if ( $qv['paginate'] && ! empty( $limits ) ) {
			$this->total_documents = $wpdb->get_var( apply_filters_ref_array( "storeabill_found_{$this->get_document_type()}_query", array( 'SELECT FOUND_ROWS()', &$this ) ) );
		} else {
			if ( is_array( $this->results ) ) {
				$this->total_documents = count( $this->results );
			} else {
				if ( null === $this->results ) {
					$this->total_documents = 0;
				} else {
					$this->total_documents = 1;
				}
			}
		}

		$this->total_documents = apply_filters_ref_array( "storeabill_found_{$this->get_document_type()}", array( $this->total_documents, &$this ) );

		if ( ! empty( $limits ) ) {
			$this->max_num_pages = ceil( $this->total_documents / $qv['limit'] );
		}
	}

	/**
	 * Parse the query before preparing it.
	 */
	protected function parse_query() {

		if ( isset( $this->args['date'] ) ) {
			$this->args['date_created'] = $this->args['date'];
		}

		if ( isset( $this->args['reference_id'] ) ) {
			$this->args['reference_id'] = absint( $this->args['reference_id'] );
		}

		if ( isset( $this->args['reference_type'] ) ) {
			$this->args['reference_type'] = sab_clean( $this->args['reference_type'] );
		}

		if ( isset( $this->args['parent_id'] ) ) {
			$this->args['parent_id'] = absint( $this->args['parent_id'] );
		}

		if ( isset( $this->args['customer_id'] ) ) {
			$this->args['customer_id'] = absint( $this->args['customer_id'] );
		}

		if ( isset( $this->args['author_id'] ) ) {
			$this->args['author_id'] = absint( $this->args['author_id'] );
		}

		if ( isset( $this->args['number'] ) ) {
			$this->args['number'] = sab_clean( $this->args['number'] );
		}

		if ( isset( $this->args['formatted_number'] ) ) {
			$this->args['formatted_number'] = sab_clean( $this->args['formatted_number'] );
		}

		if ( isset( $this->args['status'] ) ) {
			$this->args['status'] = (array) $this->args['status'];
			$this->args['status'] = array_map( 'sanitize_key', $this->args['status'] );
		}

		if ( isset( $this->args['country'] ) ) {
			$countries = isset( WC()->countries ) ? WC()->countries : false;

			if ( $countries && is_a( $countries, 'WC_Countries' ) ) {

				// Reverse search by country name
				if ( $key = array_search( $this->args['country'], $countries->get_countries() ) ) {
					$this->args['country'] = $key;
				}
			}

			// Country Code ISO
			$this->args['country'] = strtoupper( substr( $this->args['country'], 0, 2 ) );
		}

		$this->args['type'] = isset( $this->args['type'] ) ? (array) $this->args['type'] : array( 'simple' );
		$this->args['type'] = array_map( array( $this, 'clear_type' ), $this->args['type'] );

		/**
		 * Support all/any status parameter
		 */
		if ( in_array( 'all', $this->args['status'] ) || in_array( 'any', $this->args['status'] ) ) {
			$this->args['status'] = array_keys( sab_get_document_statuses( $this->args['type'][0] ) );
		}

		if ( isset( $this->args['search'] ) ) {
			$this->args['search'] = sab_clean( $this->args['search'] );

			if ( ! isset( $this->args['search_columns'] ) ) {
				$this->args['search_columns'] = array();
			}

			$this->args['search_columns'] = array_map( array( $this, 'maybe_prefix_column' ), $this->args['search_columns'] );
		}
	}

	public function maybe_prefix_column( $column ) {
		if ( substr( $column, 0, 9 ) !== 'document_' ) {
			$column = 'document_' . $column;
		}

		return $column;
	}

	public function clear_type( $type ) {
		$type                 = sab_clean( $type );
		$document_type        = $this->get_document_type();
		$document_type_prefix = $document_type . '_';

		if ( $document_type !== $type ) {
			if ( 'simple' === $type ) {
				$type = $this->get_document_type();
			} elseif ( substr( $type, 0, strlen( $document_type_prefix ) ) !== $document_type_prefix ) {
				$type = $this->get_document_type() . '_' . $type;
			}
		}

		return $type;
	}

	/**
	 * Prepare the query for DB usage.
	 */
	protected function prepare_query() {
		global $wpdb;

		if ( is_array( $this->args['return'] ) ) {
			$this->args['return'] = array_unique( $this->args['return'] );
			$this->query_fields   = array();

			foreach ( $this->args['return'] as $field ) {
				$field                = 'ID' === $field ? 'document_id' : sanitize_key( $field );
				$this->query_fields[] = "$wpdb->storeabill_documents.$field";
			}

			$this->query_fields = implode( ',', $this->query_fields );

		} elseif ( 'objects' == $this->args['return'] ) {
			$this->query_fields = "$wpdb->storeabill_documents.*";
		} else {
			$this->query_fields = "$wpdb->storeabill_documents.document_id";
		}

		$this->query_from  = "FROM $wpdb->storeabill_documents";
		$this->query_where = 'WHERE 1=1';

		// ref id
		if ( isset( $this->args['reference_id'] ) ) {
			$this->query_where .= $wpdb->prepare( ' AND document_reference_id = %d', $this->args['reference_id'] );
		}

		// ref type
		if ( isset( $this->args['reference_type'] ) ) {
			$this->query_where .= $wpdb->prepare( ' AND document_reference_type = %s', $this->args['reference_type'] );
		}

		// parent id
		if ( isset( $this->args['parent_id'] ) ) {
			$this->query_where .= $wpdb->prepare( " AND document_parent_id = %d", $this->args['parent_id'] );
		}

		// customer id
		if ( isset( $this->args['customer_id'] ) ) {
			$this->query_where .= $wpdb->prepare( " AND document_customer_id = %d", $this->args['customer_id'] );
		}

		// author id
		if ( isset( $this->args['author_id'] ) ) {
			$this->query_where .= $wpdb->prepare( " AND document_author_id = %d", $this->args['author_id'] );
		}

		// number
		if ( isset( $this->args['number'] ) ) {
			$this->query_where .= $wpdb->prepare( " AND document_number IN ('%s')", $this->args['number'] );
		}

		// number
		if ( isset( $this->args['formatted_number'] ) ) {
			$this->query_where .= $wpdb->prepare( " AND document_formatted_number IN ('%s')", $this->args['formatted_number'] );
		}

		// country
		if ( isset( $this->args['country'] ) ) {
			$this->query_where .= $wpdb->prepare( " AND document_country IN ('%s')", $this->args['country'] );
		}

		// type
		if ( isset( $this->args['type'] ) ) {
			$types    = $this->args['type'];
			$p_types  = array();

			foreach( $types as $type ) {
				$p_types[] = $wpdb->prepare( "document_type = '%s'", $type );
			}

			$where_type = implode( ' OR ', $p_types );

			if ( ! empty( $where_type ) ) {
				$this->query_where .= " AND ($where_type)";
			}
		}

		// status
		if ( isset( $this->args['status'] ) ) {
			$stati    = $this->args['status'];
			$p_status = array();

			foreach( $stati as $status ) {
				$p_status[] = $wpdb->prepare( "document_status = '%s'", $status );
			}

			$where_status = implode( ' OR ', $p_status );

			if ( ! empty( $where_status ) ) {
				$this->query_where .= " AND ($where_status)";
			}
		}

		// Search
		$search = '';

		if ( isset( $this->args['search'] ) ) {
			$search = trim( $this->args['search'] );
		}

		if ( $search ) {

			$leading_wild  = ( ltrim( $search, '*' ) != $search );
			$trailing_wild = ( rtrim( $search, '*' ) != $search );

			if ( $leading_wild && $trailing_wild ) {
				$wild = 'both';
			} elseif ( $leading_wild ) {
				$wild = 'leading';
			} elseif ( $trailing_wild ) {
				$wild = 'trailing';
			} else {
				$wild = false;
			}
			if ( $wild ) {
				$search = trim( $search, '*' );
			}

			$search_columns = array();

			if ( $this->args['search_columns'] ) {
				$search_columns = array_intersect( $this->args['search_columns'], array( 'document_id', 'document_number', 'document_country', 'document_formatted_number', 'document_reference_id', 'document_author_id', 'document_customer_id' ) );
			}

			if ( ! $search_columns ) {
				if ( is_numeric( $search ) ) {
					$search_columns = array( 'document_id', 'document_reference_id', 'document_author_id', 'document_customer_id', 'document_number' );
				} elseif ( strlen( $search ) === 2 ) {
					$search_columns = array( 'document_country' );
				} else {
					$search_columns = array( 'document_id', 'document_formatted_number' );
				}
			}

			/**
			 * Filters the columns to search in a document query search.
			 *
			 * The default columns depend on the search term, and include 'document_id', 'document_number',
			 * 'document_formatted_number', 'document_reference_id', 'document_author_id' and 'document_customer_id'.
			 *
			 * @param string[]      $search_columns Array of column names to be searched.
			 * @param string        $search         Text being searched.
			 * @param Query $this The current DocumentQuery instance.
			 *
			 * @since 1.0.0
			 *
			 * @package Vendidero/StoreaBill
			 */
			$search_columns = apply_filters( 'storeabill_document_search_columns', $search_columns, $search, $this );

			$this->query_where .= $this->get_search_sql( $search, $search_columns, $wild );
		}

		// Parse and sanitize 'include', for use by 'orderby' as well as 'include' below.
		if ( ! empty( $this->args['include'] ) ) {
			$include = wp_parse_id_list( $this->args['include'] );
		} else {
			$include = false;
		}

		// Meta query.
		$this->meta_query = new WP_Meta_Query();
		$this->meta_query->parse_query_vars( $this->args );

		if ( ! empty( $this->meta_query->queries ) ) {
			$clauses            = $this->meta_query->get_sql( 'storeabill_document', $wpdb->storeabill_documents, 'document_id', $this );
			$this->query_from  .= $clauses['join'];
			$this->query_where .= $clauses['where'];

			if ( $this->meta_query->has_or_relation() ) {
				$this->query_fields = 'DISTINCT ' . $this->query_fields;
			}
		}

		// sorting
		$this->args['order'] = isset( $this->args['order'] ) ? strtoupper( $this->args['order'] ) : '';
		$order               = $this->parse_order( $this->args['order'] );

		if ( empty( $this->args['orderby'] ) ) {
			// Default order is by 'user_login'.
			$ordersby = array( 'date_created' => $order );
		} elseif ( is_array( $this->args['orderby'] ) ) {
			$ordersby = $this->args['orderby'];
		} else {
			// 'orderby' values may be a comma- or space-separated list.
			$ordersby = preg_split( '/[,\s]+/', $this->args['orderby'] );
		}

		$orderby_array = array();

		foreach ( $ordersby as $_key => $_value ) {
			if ( ! $_value ) {
				continue;
			}

			if ( is_int( $_key ) ) {
				// Integer key means this is a flat array of 'orderby' fields.
				$_orderby = $_value;
				$_order   = $order;
			} else {
				// Non-integer key means this the key is the field and the value is ASC/DESC.
				$_orderby = $_key;
				$_order   = $_value;
			}

			$parsed = $this->parse_orderby( $_orderby );

			if ( ! $parsed ) {
				continue;
			}

			$orderby_array[] = $parsed . ' ' . $this->parse_order( $_order );
		}

		// If no valid clauses were found, order by user_login.
		if ( empty( $orderby_array ) ) {
			$orderby_array[] = "document_id $order";
		}

		$this->query_orderby = 'ORDER BY ' . implode( ', ', $orderby_array );

		// limit
		if ( isset( $this->args['limit'] ) && $this->args['limit'] > 0 ) {
			if ( isset( $this->args['offset'] ) ) {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $this->args['offset'], $this->args['limit'] );
			} else {
				$this->query_limit = $wpdb->prepare( 'LIMIT %d, %d', $this->args['limit'] * ( $this->args['page'] - 1 ), $this->args['limit'] );
			}
		}

		if ( ! empty( $include ) ) {
			// Sanitized earlier.
			$ids                = implode( ',', $include );
			$this->query_where .= " AND $wpdb->storeabill_documents.document_id IN ($ids)";
		} elseif ( ! empty( $this->args['exclude'] ) ) {
			$ids                = implode( ',', wp_parse_id_list( $this->args['exclude'] ) );
			$this->query_where .= " AND $wpdb->storeabill_documents.document_id NOT IN ($ids)";
		}

		// Date queries are allowed for the user_registered field.
		if ( ! empty( $this->args['date_query'] ) && is_array( $this->args['date_query'] ) ) {
			$date_query         = new WP_Date_Query( $this->args['date_query'], "$wpdb->storeabill_documents.document_date_created" );
			$this->query_where .= $date_query->get_sql();
		}
	}

	/**
	 * Used internally to generate an SQL string for searching across multiple columns
	 *
	 * @since 3.0.6
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $string
	 * @param array  $cols
	 * @param bool   $wild   Whether to allow wildcard searches. Default is false for Network Admin, true for single site.
	 *                       Single site allows leading and trailing wildcards, Network Admin only trailing.
	 * @return string
	 */
	protected function get_search_sql( $string, $cols, $wild = false ) {
		global $wpdb;

		$searches      = array();
		$leading_wild  = ( 'leading' == $wild || 'both' == $wild ) ? '%' : '';
		$trailing_wild = ( 'trailing' == $wild || 'both' == $wild ) ? '%' : '';
		$like          = $leading_wild . $wpdb->esc_like( $string ) . $trailing_wild;

		foreach ( $cols as $col ) {
			if ( 'ID' == $col ) {
				$searches[] = $wpdb->prepare( "$col = %s", $string );
			} else {
				$searches[] = $wpdb->prepare( "$col LIKE %s", $like );
			}
		}

		return ' AND (' . implode( ' OR ', $searches ) . ')';
	}

	/**
	 * Parse orderby statement.
	 *
	 * @param string $orderby
	 * @return string
	 */
	protected function parse_orderby( $orderby ) {
		global $wpdb;

		$meta_query_clauses = $this->meta_query->get_clauses();

		$_orderby = '';

		if ( in_array( $orderby, array( 'number', 'status', 'country', 'date_created', 'date_modified', 'date_custom', 'date_custom_extra' ) ) ) {
			$_orderby = 'document_' . $orderby;
		} elseif( 'date' == $orderby ) {
			$_orderby = 'document_date_created';
		} elseif ( 'ID' == $orderby || 'id' == $orderby ) {
			$_orderby = 'document_id';
		} elseif ( 'meta_value' == $orderby || $this->get( 'meta_key' ) == $orderby ) {
			$_orderby = "$wpdb->storeabill_documentmeta.meta_value";
		} elseif ( 'meta_value_num' == $orderby ) {
			$_orderby = "$wpdb->storeabill_documentmeta.meta_value+0";
		} elseif ( 'include' === $orderby && ! empty( $this->args['include'] ) ) {
			$include     = wp_parse_id_list( $this->args['include'] );
			$include_sql = implode( ',', $include );
			$_orderby    = "FIELD( $wpdb->storeabill_documents.document_id, $include_sql )";
		} elseif ( isset( $meta_query_clauses[ $orderby ] ) ) {
			$meta_clause = $meta_query_clauses[ $orderby ];
			$_orderby    = sprintf( 'CAST(%s.meta_value AS %s)', esc_sql( $meta_clause['alias'] ), esc_sql( $meta_clause['cast'] ) );
		}

		return $_orderby;
	}

	/**
	 * Parse order statement.
	 *
	 * @param string $order
	 * @return string
	 */
	protected function parse_order( $order ) {
		if ( ! is_string( $order ) || empty( $order ) ) {
			return 'DESC';
		}

		if ( 'ASC' === strtoupper( $order ) ) {
			return 'ASC';
		} else {
			return 'DESC';
		}
	}
}
