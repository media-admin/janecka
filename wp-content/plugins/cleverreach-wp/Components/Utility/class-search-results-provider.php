<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Utility;

use CleverReach\WordPress\Components\Exceptions\Content_Type_Not_Found_Exception;
use CleverReach\WordPress\Components\Repositories\Article_Repository;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Exceptions\InvalidSchemaMatching;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\FilterParser;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\AuthorAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\EnumAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\ImageAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\NumberAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResult;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\SearchResultItem;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\SimpleCollectionAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\TextAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchResult\UrlAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Validator;

/**
 * Class Search_Results_Provider
 *
 * @package CleverReach\WordPress\Components\Utility
 */
class Search_Results_Provider {

	/**
	 * Schema provider
	 *
	 * @var Schema_Provider
	 */
	private $schema_provider;

	/**
	 * Article repository
	 *
	 * @var Article_Repository
	 */
	private $article_repository;

	/**
	 * Get article search result
	 *
	 * @param array $filters Array of filters.
	 *
	 * @return array
	 */
	public function get_standard_article_search_results( $filters ) {
		return $this->get_article_repository()->get_articles_by_id_and_title( $filters );
	}

	/**
	 * Get search result
	 *
	 * @param string $content_type Content type.
	 * @param string $raw_filter Raw filter.
	 *
	 * @return SearchResult
	 * @throws Content_Type_Not_Found_Exception Content type not found exception.
	 * @throws InvalidSchemaMatching Invalid schema matching.
	 */
	public function get_search_results( $content_type, $raw_filter ) {
		$search_result = new SearchResult();
		$articles      = $this->get_filtered_articles( $content_type, $raw_filter );

		if ( empty( $articles ) ) {
			return $search_result;
		}

		foreach ( $articles as $article ) {
			$this->create_article( $content_type, $article, $search_result );
		}

		return $search_result;
	}

	/**
	 * Gets filtered articles
	 *
	 * @param string $content_type Content type.
	 * @param string $raw_filter Raw filter.
	 *
	 * @return array
	 * @throws Content_Type_Not_Found_Exception Content type not found exception.
	 * @throws InvalidSchemaMatching Invalid schema matching.
	 */
	private function get_filtered_articles( $content_type, $raw_filter ) {
		$schema = $this->get_schema_provider()->get_schema( $content_type );

		$filter_parser = new FilterParser();
		$filters       = $filter_parser->generateFilters( $content_type, null, rawurlencode( $raw_filter ) );

		$filter_validator = new Validator();
		$filter_validator->validateFilters( $filters, $schema );

		$filter_by = array();
		foreach ( $filters as $filter ) {
			$field_code  = $filter->getAttributeCode();
			$field_value = $filter->getAttributeValue();
			$condition   = $filter->getCondition();
			$filter_by[] = array(
				'field'     => $field_code,
				'value'     => $field_value,
				'condition' => $condition,
			);
		}

		return $this->get_article_repository()->get_filtered_articles( $filter_by, $content_type );
	}

	/**
	 * Create article
	 *
	 * @param string       $content_type Content type.
	 * @param array        $article Array article.
	 * @param SearchResult $search_result Search result object.
	 */
	private function create_article( $content_type, $article, SearchResult $search_result ) {
		$specific_attributes = array();
		if ( 'post' === $content_type ) {
			$specific_attributes = $this->get_post_attributes( $article );
		} elseif ( 'page' === $content_type ) {
			$specific_attributes = $this->get_page_attributes( $article );
		}

		if ( ! empty( $specific_attributes ) ) {
			$attributes = array_merge( $specific_attributes, $this->get_common_attributes( $article ) );
			$search_result->addSearchResultItem(
				new SearchResultItem(
					$content_type,
					$article['ID'],
					$article['post_title'],
					new \DateTime( '@' . strtotime( $article['post_date'] ) ),
					$attributes
				)
			);
		}
	}

	/**
	 * Get common attributes
	 *
	 * @param array $article Array article.
	 *
	 * @return array
	 */
	private function get_common_attributes( $article ) {
		$author             = $this->get_article_repository()->get_author_name( $article['post_author'] );
		$visibility         = $this->get_article_repository()->get_visibility_status( $article );
		$custom_field_names = $this->get_article_repository()->get_custom_field_names( $article['ID'] );
		$featured_image     = $this->get_article_repository()->get_featured_image( $article['ID'] );

		return array(
			new NumberAttribute( 'ID', $article['ID'] ),
			new AuthorAttribute( 'author', $author ),
			new TextAttribute( 'content', $article['post_content'] ),
			new UrlAttribute( 'article_url', $article['guid'] ),
			new ImageAttribute( 'mainImage', $featured_image ?: '' ),
			new TextAttribute( 'post_status', $article['post_status'] ),
			new TextAttribute( 'post_name', $article['post_name'] ),
			new EnumAttribute( 'comment_status', $article['comment_status'] ),
			new EnumAttribute( 'ping_status', $article['ping_status'] ),
			new EnumAttribute( 'visibility', $visibility ),
			new SimpleCollectionAttribute( 'custom_field_name', $custom_field_names ),
		);
	}

	/**
	 * Get page specific attributes
	 *
	 * @param array $article Array article.
	 *
	 * @return array
	 */
	private function get_page_attributes( $article ) {
		$page_parent = $this->get_article_repository()->get_page_parent( $article['post_parent'] );

		return array(
			new NumberAttribute( 'menu_order', $article['menu_order'] ),
			new TextAttribute( 'post_parent', $page_parent ),
		);
	}

	/**
	 * Get post specific attributes
	 *
	 * @param array $article Array article.
	 *
	 * @return array
	 */
	private function get_post_attributes( $article ) {
		$post_format     = $this->get_article_repository()->get_post_format( $article['ID'] );
		$post_categories = $this->get_article_repository()->get_post_categories( $article['ID'] );
		$post_tags       = $this->get_article_repository()->get_post_tags( $article['ID'] );
		$post_trackbacks = $this->get_article_repository()->get_post_trackbacks( $article );

		return array(
			new SimpleCollectionAttribute( 'send_trackbacks', $post_trackbacks ),
			new TextAttribute( 'post_excerpt', $article['post_excerpt'] ),
			new EnumAttribute( 'post_format', $post_format ),
			new SimpleCollectionAttribute( 'category', $post_categories ),
			new SimpleCollectionAttribute( 'tags', $post_tags ),
		);
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
	 * Gets article repository
	 *
	 * @return Article_Repository
	 */
	private function get_article_repository() {
		if ( null === $this->article_repository ) {
			$this->article_repository = new Article_Repository();
		}

		return $this->article_repository;
	}
}
