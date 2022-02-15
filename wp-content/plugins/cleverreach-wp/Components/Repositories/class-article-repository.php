<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Repositories;

use CleverReach\WordPress\Components\Utility\Database;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Operators;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema\Enum;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\TextAttribute;

/**
 * Class Article_Repository
 *
 * @package CleverReach\WordPress\Components\Repositories
 */
class Article_Repository extends Base_Repository_Legacy {

	/**
	 * Mapping array for filter conditions.
	 *
	 * @var array
	 */
	static private $condition_mapping = array(
		Conditions::EQUALS        => '=',
		Conditions::NOT_EQUAL     => '<>',
		Conditions::GREATER_THAN  => '>',
		Conditions::LESS_THAN     => '<',
		Conditions::LESS_EQUAL    => '<=',
		Conditions::GREATER_EQUAL => '>=',
		Conditions::CONTAINS      => 'LIKE',
	);

	/**
	 * Mapping array for field names.
	 *
	 * @var array
	 */
	static private $field_mapping = array(
		'author'    => 'post_author',
		'title'     => 'post_title',
		'date'      => 'post_date',
		'mainImage' => 'featured_image',
		'tags'      => 'post_tag',
	);

	/**
	 * Article_Repository constructor
	 */
	public function __construct() {
		parent::__construct();

		$this->table_name = Database::table( Database::POSTS_TABLE );
	}

	/**
	 * Gets all statuses for posts and pages
	 *
	 * @return array of Enum
	 */
	public function get_all_statuses() {
		$formatted_statuses = array();
		$statuses           = get_post_stati();
		foreach ( $statuses as $status_code => $status_value ) {
			$formatted_statuses[] = new Enum( $status_value, $status_code );
		}

		return $formatted_statuses;
	}

	/**
	 * Gets all visibilities for posts and pages
	 *
	 * @return array of Enum
	 */
	public function get_all_visibilities() {
		return array(
			new Enum( __( 'Private' ), 'private' ),
			new Enum( __( 'Password protected' ), 'password' ),
			new Enum( __( 'Public' ), 'public' ),
		);
	}

	/**
	 * Gets all boolean type statuses
	 *
	 * @return array of Enum
	 */
	public function get_boolean_statuses() {
		return array(
			new Enum( __( 'Open' ), 'open' ),
			new Enum( __( 'Closed' ), 'closed' ),
		);
	}

	/**
	 * Gets all parents for pages
	 *
	 * @return array of Enum
	 */
	public function get_all_parents() {
		$formatted_page_parents = array();
		$page_parents           = get_pages();
		foreach ( $page_parents as $page_parent ) {
			$formatted_page_parents[] = new Enum( $page_parent->post_title, $page_parent->ID );
		}

		return $formatted_page_parents;
	}

	/**
	 * Gets all formats for posts
	 *
	 * @return array of Enum
	 */
	public function get_all_formats() {
		$formatted_formats = array();
		$formats           = get_post_format_strings();
		foreach ( $formats as $format_code => $format_value ) {
			$formatted_formats[] = new Enum( $format_value, $format_code );
		}

		return $formatted_formats;
	}

	/**
	 * Gets author name by id
	 *
	 * @param int $author_id User id.
	 *
	 * @return string
	 */
	public function get_author_name( $author_id ) {
		$author = get_user_by( 'id', $author_id );

		return $author->user_login;
	}

	/**
	 * Gets visibility status for article
	 *
	 * @param array $article Article array.
	 *
	 * @return string
	 */
	public function get_visibility_status( $article ) {
		return ! empty( $article['post_password'] ) ?
			__( 'Password protected' ) :
			( 'private' === $article['post_status'] ? __( 'Private' ) : __( 'Public' ) );
	}

	/**
	 * Gets custom field names for article
	 *
	 * @param int $article_id Article id.
	 *
	 * @return array
	 */
	public function get_custom_field_names( $article_id ) {
		$custom_fields           = $this->get_content_possible_custom_field_names( $article_id );
		$custom_fields_formatted = array();

		foreach ( $custom_fields as $custom_field ) {
			$custom_fields_formatted[] = new TextAttribute( 'key', $custom_field );
		}

		return $custom_fields_formatted;
	}

	/**
	 * Gets featured image for article
	 *
	 * @param int $article_id Article id.
	 *
	 * @return false|string
	 */
	public function get_featured_image( $article_id ) {
		return get_the_post_thumbnail_url( $article_id );
	}

	/**
	 * Gets parent page name for parent id
	 *
	 * @param int $parent_id Parent id.
	 *
	 * @return string
	 */
	public function get_page_parent( $parent_id ) {
		$page_parent = get_post( $parent_id );

		return ! empty( $page_parent->post_title ) ? $page_parent->post_title : __( '(no parent)' );
	}

	/**
	 * Gets format for post
	 *
	 * @param int $article_id Article id.
	 *
	 * @return string
	 */
	public function get_post_format( $article_id ) {
		$post_format = get_post_format( $article_id );

		return $post_format ?: __( 'standard' );
	}

	/**
	 * Gets categories for post
	 *
	 * @param int $article_id Article id.
	 *
	 * @return array
	 */
	public function get_post_categories( $article_id ) {
		$category_ids         = wp_get_post_categories( $article_id );
		$categories_formatted = array();

		foreach ( $category_ids as $category_id ) {
			$categories_formatted[] = new TextAttribute( 'name', get_cat_name( $category_id ) );
		}

		return $categories_formatted;
	}

	/**
	 * Gets tags for post
	 *
	 * @param int $article_id Article id.
	 *
	 * @return array
	 */
	public function get_post_tags( $article_id ) {
		$tags           = wp_get_post_tags( $article_id );
		$tags_formatted = array();

		foreach ( $tags as $tag ) {
			$tags_formatted[] = new TextAttribute( 'name', $tag->name );
		}

		return $tags_formatted;
	}

	/**
	 * Gets trackbacks for post
	 *
	 * @param array $article Article array.
	 *
	 * @return array
	 */
	public function get_post_trackbacks( $article ) {
		$to_ping    = explode( PHP_EOL, $article['to_ping'] );
		$pinged     = explode( PHP_EOL, $article['pinged'] );
		$trackbacks = array_merge( $to_ping, $pinged );

		$trackbacks_fromatted = array();
		foreach ( $trackbacks as $trackback ) {
			if ( ! empty( $trackback ) ) {
				$trackbacks_fromatted[] = new TextAttribute( 'url', $trackback );
			}
		}

		return $trackbacks_fromatted;
	}

	/**
	 * Gets articles by id or/and title
	 *
	 * @param array $filters Array of string filters.
	 *
	 * @return array
	 */
	public function get_articles_by_id_and_title( $filters ) {
		$glue_for_implode = ' ' . Operators::AND_OPERATOR . ' ';
		$where_clause     = implode( $glue_for_implode, $filters );

		return empty( $where_clause ) ?
			array() :
			$this->db->get_results( "SELECT * FROM $this->table_name WHERE $where_clause", ARRAY_A );
	}

	/**
	 * Gets filtered articles by type
	 *
	 * @param array  $filters Filters for search.
	 * @param string $type Article type.
	 *
	 * @return array
	 */
	public function get_filtered_articles( $filters, $type ) {
		$filters      = array_column( $filters, null, 'field' );
		$where_clause = $this->build_where_clause( $filters, $type );

		return empty( $where_clause ) ?
			array() :
			$this->db->get_results( "SELECT * FROM $this->table_name WHERE $where_clause", ARRAY_A );
	}

	/**
	 * Gets possible custom field names for posts and pages
	 *
	 * @param int $post_id Post id.
	 *
	 * @return array
	 */
	private function get_content_possible_custom_field_names( $post_id = 0 ) {
		$table_name           = $this->db->postmeta;
		$limit                = apply_filters( 'postmeta_form_limit', 30 );
		$post_id_where_clause = ! empty( $post_id ) ? "AND post_id = $post_id" : '';

		$sql = "SELECT DISTINCT meta_key
			FROM $table_name
			WHERE (meta_key NOT BETWEEN '_' AND '_z') $post_id_where_clause
			HAVING meta_key NOT LIKE %s
			ORDER BY meta_key
			LIMIT %d";

		return $this->db->get_col( $this->db->prepare( $sql, $this->db->esc_like( '_' ) . '%', $limit ) );
	}

	/**
	 * Build where clause for article search
	 *
	 * @param array  $filters Filters for search.
	 * @param string $type Article type.
	 *
	 * @return string
	 */
	private function build_where_clause( $filters, $type ) {
		$where_clause   = 'post_type = "' . $type . '"';
		$post_ids_array = array();

		$fields_for_getting_post_ids = array( 'custom_field_name', 'post_format', 'category', 'post_tag' );
		foreach ( $filters as $filter ) {
			$value     = $filter['value'];
			$field     = array_key_exists( $filter['field'], self::$field_mapping ) ?
				self::$field_mapping[ $filter['field'] ] : $filter['field'];
			$condition = array_key_exists( $filter['condition'], self::$condition_mapping ) ?
				self::$condition_mapping[ $filter['condition'] ] : $filter['condition'];

			if ( 'itemCode' === $field ) {
				continue;
			}

			if ( in_array( $field, $fields_for_getting_post_ids, true ) ) {
				$filter['field'] = $field;
				$post_ids        = 'custom_field_name' === $field ?
					$this->get_post_or_page_ids_for_custom_field_name( $type, $value, $condition ) :
					$this->get_post_ids_for_where_clause( $filter );

				if ( empty( $post_ids ) ) {
					// No results were found, and in every case it should have some result if search was successful.
					return '';
				}

				$post_ids_array[] = $post_ids;
				continue;
			}

			if ( 'visibility' === $field ) {
				$where_clause_for_visibility_status = $this->get_where_clause_for_visibility_status( $condition, $value );
				if ( empty( $where_clause_for_visibility_status ) ) {
					return '';
				}

				$where_clause .= ' ' . Operators::AND_OPERATOR . ' ' . $where_clause_for_visibility_status;
			} elseif ( 'post_author' === $field ) {
				$where_clause_for_author = $this->get_where_clause_for_author( $condition, $value );
				if ( empty( $where_clause_for_author ) ) {
					if ( self::$condition_mapping[ Conditions::EQUALS ] === $condition ) {
						// This means that author is not even found, and since is "equal" condition return no result for search.
						return '';
					}

					// This means that author is not even found, and since is "not equal" condition just skip this filter.
					continue;
				} else {
					$where_clause .= ' ' . Operators::AND_OPERATOR . ' ' . $where_clause_for_author;
				}
			} else {
				$value         = 'LIKE' === $condition ? "%$value%" : $value;
				$where_clause .= ' ' . Operators::AND_OPERATOR . ' ' . $field . ' ' . $condition . ' ' . $this->escape_value( $value );
			}
		}

		if ( ! empty( $post_ids_array ) ) {
			// For all post ids result we need intersections to find which posts fulfill all filters.
			$post_ids = $post_ids_array[0];
			foreach ( $post_ids_array as $post_ids_element ) {
				$post_ids = array_intersect( $post_ids, $post_ids_element );
			}

			$where_clause .= ' ' . Operators::AND_OPERATOR . ' ID IN (' . implode( ',', $post_ids ) . ')';
		}

		return $where_clause;
	}

	/**
	 * Gets where clause for visibility statuses attribute
	 *
	 * @param string $condition Condition for search.
	 * @param string $value Value to search.
	 *
	 * @return string
	 */
	private function get_where_clause_for_visibility_status( $condition, $value ) {
		$where_clause = '';
		if ( 'public' === $value ) {
			$where_clause = self::$condition_mapping[ Conditions::EQUALS ] === $condition ?
				"(post_status != 'private' AND post_password = '')" :
				"(post_status = 'private' OR post_password <> '')";
		} elseif ( 'private' === $value ) {
			$where_clause = self::$condition_mapping[ Conditions::EQUALS ] === $condition ?
				"(post_status = 'private')" :
				"(post_status <> 'private')";
		} elseif ( 'password' === $value ) {
			$where_clause = self::$condition_mapping[ Conditions::EQUALS ] === $condition ?
				"(post_password <> '')" :
				"(post_password = '')";
		}

		return $where_clause;
	}

	/**
	 * Gets where clause for author
	 *
	 * @param string $condition Condition for search.
	 * @param string $value Value to search.
	 *
	 * @return string
	 */
	private function get_where_clause_for_author( $condition, $value ) {
		$author = get_user_by( 'login', $value );
		$id     = ! empty( $author ) ? $author->ID : 0;

		return ! empty( $id ) ? "post_author $condition $id" : '';
	}

	/**
	 * Gets post or page ids by custom field name
	 *
	 * @param string $type Article type.
	 * @param string $custom_field_name Custom field name.
	 * @param string $condition Condition for search.
	 *
	 * @return array
	 */
	private function get_post_or_page_ids_for_custom_field_name( $type, $custom_field_name, $condition ) {
		$postmeta_table_name = $this->db->postmeta;
		$sql                 = "SELECT post_id FROM $postmeta_table_name WHERE meta_key = '$custom_field_name'";

		if ( $condition === self::$condition_mapping[ Conditions::NOT_EQUAL ] ) {
			$sql = "SELECT ID FROM $this->table_name WHERE ID NOT IN ($sql) AND post_type = '$type' GROUP BY ID";
		}

		return $this->db->get_col( $sql );
	}

	/**
	 * Gets post ids for where clause
	 *
	 * @param array $filter Filter for search.
	 *
	 * @return array
	 */
	private function get_post_ids_for_where_clause( $filter ) {
		$post_ids = array();
		if ( 'category' === $filter['field'] || 'post_tag' === $filter['field'] ) {
			$post_ids = $this->get_post_ids_for_taxonomy( $filter['field'], $filter['value'], $filter['condition'] );

			// If there are no results for category field and not equal condition, that means that category doesn't even exit
			// so return all posts.
			if ( empty( $post_ids ) && 'category' === $filter['field'] && Conditions::NOT_EQUAL === $filter['condition'] ) {
				$post_ids = $this->get_all_post_ids();
			}
		} elseif ( 'post_format' === $filter['field'] ) {
			$post_ids = $this->get_post_id_for_format( $filter['field'], $filter['value'], $filter['condition'] );
		}

		return $post_ids;
	}

	/**
	 * Gets post id for tags
	 *
	 * @param string $field Field name.
	 * @param string $value Value to check.
	 * @param string $condition Condition for search.
	 *
	 * @return array
	 */
	private function get_post_ids_for_taxonomy( $field, $value, $condition ) {
		$tag_ids = array();
		$names   = explode( ',', $value );
		foreach ( $names as $name ) {
			$tag = get_term_by( 'name', $name, $field );
			if ( null === $tag->term_id ) {
				return array();
			}

			$tag_ids[] = $tag->term_id;
		}

		return $this->get_post_ids_by_taxonomy_ids( $field, $tag_ids, $condition, true );
	}

	/**
	 * Gets post id for post format
	 *
	 * @param string $field Field name.
	 * @param string $value Value to check.
	 * @param string $condition Condition for search.
	 *
	 * @return array
	 */
	private function get_post_id_for_format( $field, $value, $condition ) {
		if ( 'standard' === $value ) {
			return $this->get_post_id_for_standard_format( $field, $condition );
		}

		// By default all posts are in standard format, so first we get if format is defined in terms
		// which means that there are posts that have that format.
		$format = get_term_by( 'name', 'post-format-' . $value, $field );
		if ( null === $format->term_id ) {
			// This means that there are no posts with searched format.
			if ( Conditions::EQUALS === $condition ) {
				// Return no result if condition was equals, since format is not found.
				return array();
			} else {
				// Return all post ids since format is not even found which means all posts are not in searched format.
				return $this->get_all_post_ids();
			}
		}

		return $this->get_post_ids_by_taxonomy_ids( $field, $format->term_id, $condition );
	}

	/**
	 * Gets post id for standard post format
	 *
	 * @param string $field Field name.
	 * @param string $condition Condition for search.
	 *
	 * @return array
	 */
	private function get_post_id_for_standard_format( $field, $condition ) {
		// By default all posts are in standard format, so first we get all formats defined in terms
		// which means that there are posts that have some other format.
		$format_ids = get_terms(
			array(
				'hide_empty' => false,
				'name__like' => 'post-format-',
				'taxonomy'   => $field,
				'fields'     => 'ids',
			)
		);
		if ( Conditions::NOT_EQUAL === $condition ) {
			if ( empty( $format_ids ) ) {
				// This means we couldn't find any posts that are not in standard format.
				return array();
			}

			// Reverse condition so we get all posts that are not in standard format.
			$condition = Conditions::EQUALS;
		} else {
			if ( empty( $format_ids ) ) {
				// This means we couldn't find any posts that are not in standard format.
				// Return all post ids, since all are in standard format.
				return $this->get_all_post_ids();
			}

			// Reverse condition so we get all posts that are in standard format.
			$condition = Conditions::NOT_EQUAL;
		}

		return $this->get_post_ids_by_taxonomy_ids( $field, $format_ids, $condition );
	}

	/**
	 * Get post ids by taxonomy ids
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param mixed  $ids Taxonomy ids.
	 * @param string $condition Condition for search.
	 * @param bool   $and_operator Use and operator.
	 *
	 * @return array
	 */
	private function get_post_ids_by_taxonomy_ids( $taxonomy, $ids, $condition, $and_operator = false ) {
		$query = array(
			'numberposts' => -1, // Get all posts.
			'tax_query'   => array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => is_array( $ids ) ? $ids : array( (int) $ids ),
					'operator' => $and_operator && count( $ids ) > 1 ? 'AND' : ( Conditions::NOT_EQUAL === $condition ? 'NOT IN' : 'IN' ),
				),
			),
			'fields'      => 'ids', // Only get post IDs.
			'post_status' => 'any',
		);

		return get_posts( $query );
	}

	/**
	 * Gets all post ids
	 *
	 * @return array
	 */
	private function get_all_post_ids() {
		$query = array(
			'numberposts' => -1, // Get all posts.
			'fields'      => 'ids', // Only get post IDs.
			'post_status' => 'any',
		);

		return get_posts( $query );
	}
}
