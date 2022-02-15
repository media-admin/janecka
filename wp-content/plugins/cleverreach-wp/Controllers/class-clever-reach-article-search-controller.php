<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Controllers;

use CleverReach\WordPress\Components\Utility\Schema_Provider;
use CleverReach\WordPress\Components\Utility\Search_Results_Provider;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Conditions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Clever_Reach_Article_Search_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Article_Search_Controller extends Clever_Reach_Base_Controller {

	/**
	 * Schema provider
	 *
	 * @var Schema_Provider
	 */
	private $schema_provider;

	/**
	 * Search results provider
	 *
	 * @var Search_Results_Provider
	 */
	private $search_results_provider;

	/**
	 * Clever_Reach_Article_Search_Controller constructor
	 */
	public function __construct() {
		$this->is_internal = false;
	}

	/**
	 * Gets all searchable items
	 */
	public function cleverreach_items() {
		$this->die_json( $this->get_schema_provider()->get_searchable_items()->toArray() );
	}

	/**
	 * Gets searchable schema for specific type
	 */
	public function cleverreach_schema() {
		try {
			$this->die_json( $this->get_schema_provider()->get_schema( $this->get_param( 'type' ) )->toArray() );
		} catch ( \Exception $exception ) {
			$this->die_json(
				array(
					'status'  => 'error',
					'message' => $exception->getMessage(),
				)
			);
		}
	}

	/**
	 * Gets search item results based on type and filters
	 */
	public function cleverreach_search() {
		try {
			$type   = $this->get_param( 'type' );
			$filter = $this->get_param( 'filter' );
			$id     = $this->get_param( 'id' );
			if ( null !== $id ) {
				$equal_condition = Conditions::EQUALS;
				$filter          = "ID $equal_condition $id";
			}

			$this->die_json( $this->get_search_results_provider()->get_search_results( $type, $filter )->toArray() );
		} catch ( \Exception $exception ) {
			$this->die_json(
				array(
					'status'  => 'error',
					'message' => $exception->getMessage(),
				)
			);
		}
	}

	/**
	 * Public endpoint for article
	 */
	public function run() {
		$action = $this->get_param( 'get' );

		$this->validate_request( $action );

		if ( 'filter' === $action ) {
			$response = $this->get_filters();
		} else {
			$response = $this->search();
		}

		$this->die_json( $response );
	}

	/**
	 * Checks if all parameters are set as required.
	 *
	 * @param string $action Requested action.
	 */
	private function validate_request( $action ) {
		$id    = $this->get_param( 'id' );
		$title = $this->get_param( 'title' );

		if ( null === $action
			|| ( 'search' === $action && empty( $id ) && empty( $title ) )
			|| ! $this->is_post()
			|| ! in_array( $action, array( 'filter', 'search' ), true )
		) {
			status_header( 404 );

			exit();
		}
	}

	/**
	 * Returns search filters, used to search for data.
	 *
	 * @return array
	 */
	private function get_filters() {
		return array(
			array(
				'name'        => __( 'Article ID', 'cleverreach-wp' ),
				'description' => '',
				'required'    => false,
				'query_key'   => 'id',
				'type'        => 'input',
			),
			array(
				'name'        => __( 'Article Title', 'cleverreach-wp' ),
				'description' => '',
				'required'    => false,
				'query_key'   => 'title',
				'type'        => 'input',
			),
		);
	}

	/**
	 * Performs a search using search term provided in the request.
	 *
	 * @return array
	 */
	private function search() {
		$filters = array( "post_type IN ('post', 'page')" );

		$id = $this->get_param( 'id' );
		if ( ! empty( $id ) ) {
			$filters[] = "ID = $id";
		}

		$title = $this->get_param( 'title' );
		if ( ! empty( $title ) ) {
			$filters[] = "post_title LIKE '%$title%'";
		}

		$articles = $this->get_search_results_provider()->get_standard_article_search_results( $filters );

		return array(
			'settings' => array(
				'type'                => 'content',
				'link_editable'       => false,
				'link_text_editable'  => true,
				'image_size_editable' => true,
			),
			'items'    => $this->format_articles( $articles ),
		);
	}

	/**
	 * Retrieves products by their IDs and prepares them in appropriate format for the response.
	 *
	 * @param array $articles Array of articles.
	 *
	 * @return array
	 */
	private function format_articles( $articles ) {
		$results = array();
		foreach ( $articles as $article ) {
			$image     = get_the_post_thumbnail_url( $article['ID'] );
			$results[] = array(
				'title'       => $article['post_title'],
				'description' => wp_strip_all_tags( $article['post_content'] ),
				'content'     => '<!--#html #-->' . $article['post_content'] . '<!--#/html#-->',
				'image'       => false !== $image ? $image : '',
				'url'         => $article['guid'],
			);
		}

		return $results;
	}

	/**
	 * Gets schema provider
	 *
	 * @return Schema_Provider
	 */
	private function get_schema_provider() {
		if ( null === $this->schema_provider ) {
			$this->schema_provider = new Schema_Provider();
		}

		return $this->schema_provider;
	}

	/**
	 * Gets search result provider
	 *
	 * @return Search_Results_Provider
	 */
	private function get_search_results_provider() {
		if ( null === $this->search_results_provider ) {
			$this->search_results_provider = new Search_Results_Provider();
		}

		return $this->search_results_provider;
	}
}
