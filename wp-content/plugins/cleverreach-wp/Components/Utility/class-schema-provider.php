<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Components\Utility;

use CleverReach\WordPress\Components\Exceptions\Content_Type_Not_Found_Exception;
use CleverReach\WordPress\Components\Repositories\Article_Repository;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Conditions;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema\EnumSchemaAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema\SchemaAttributeTypes;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema\SimpleCollectionSchemaAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema\SimpleSchemaAttribute;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchItem\SearchableItem;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\SearchItem\SearchableItems;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\ArticleSearch\Schema\SearchableItemSchema;

/**
 * Class Schema_Provider
 *
 * @package CleverReach\WordPress\Components\Utility
 */
class Schema_Provider {

	/**
	 * Article repository
	 *
	 * @var Article_Repository
	 */
	private $article_repository;

	/**
	 * Gets all supported searchable items
	 *
	 * @return SearchableItems
	 *   Object containing all searchable items supported by module.
	 */
	public function get_searchable_items() {
		$searchable_items = new SearchableItems();
		$content_types    = $this->get_content_types();

		foreach ( $content_types as $code => $name ) {
			$searchable_items->addSearchableItem( new SearchableItem( $code, $name ) );
		}

		return $searchable_items;
	}

	/**
	 * Gets schema for content type
	 *
	 * @param string $content_type Content type.
	 *
	 * @return SearchableItemSchema
	 * @throws Content_Type_Not_Found_Exception Content type not found exception.
	 */
	public function get_schema( $content_type ) {
		if ( ! array_key_exists( $content_type, $this->get_content_types() ) ) {
			throw new Content_Type_Not_Found_Exception( "Content type '$content_type' is not found." );
		}

		$schema = $this->get_base_schema();
		switch ( $content_type ) {
			case 'page':
				$schema = array_merge( $schema, $this->get_page_attributes() );
				break;
			case 'post':
				$schema = array_merge( $schema, $this->get_post_attributes() );
				break;
		}

		return new SearchableItemSchema( $content_type, $schema );
	}

	/**
	 * Gets array of content types
	 *
	 * @return array
	 */
	private function get_content_types() {
		return array(
			'page' => 'Page',
			'post' => 'Post',
		);
	}

	/**
	 * Gets base schema for all content types
	 *
	 * @return array
	 */
	private function get_base_schema() {
		return array(
			new SimpleSchemaAttribute(
				'ID',
				'ID',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				SchemaAttributeTypes::NUMBER
			),
			new SimpleSchemaAttribute(
				'author',
				'Author',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				SchemaAttributeTypes::AUTHOR
			),
			new SimpleSchemaAttribute(
				'title',
				'Title',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL, Conditions::CONTAINS ),
				SchemaAttributeTypes::TEXT
			),
			new EnumSchemaAttribute(
				'post_status',
				'Status',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				$this->get_article_repository()->get_all_statuses()
			),
			new EnumSchemaAttribute(
				'visibility',
				'Visibility',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				$this->get_article_repository()->get_all_visibilities()
			),
			new SimpleSchemaAttribute(
				'date',
				'Published on',
				true,
				array( Conditions::EQUALS, Conditions::GREATER_EQUAL, Conditions::GREATER_THAN, Conditions::LESS_EQUAL, Conditions::LESS_THAN ),
				SchemaAttributeTypes::DATE
			),
			new SimpleSchemaAttribute(
				'mainImage',
				'Featured Image',
				false,
				array(),
				SchemaAttributeTypes::IMAGE
			),
			new SimpleSchemaAttribute(
				'post_name',
				'Slug',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL, Conditions::CONTAINS ),
				SchemaAttributeTypes::TEXT
			),
			new EnumSchemaAttribute(
				'comment_status',
				'Allow comments',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				$this->get_article_repository()->get_boolean_statuses()
			),
			new EnumSchemaAttribute(
				'ping_status',
				'Allow trackbacks and pingbacks',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				$this->get_article_repository()->get_boolean_statuses()
			),
			new SimpleCollectionSchemaAttribute(
				'custom_field_name',
				'Custom field name',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				SchemaAttributeTypes::TEXT
			),
			new SimpleSchemaAttribute(
				'article_url',
				'Article URL',
				false,
				array(),
				SchemaAttributeTypes::URL
			),
			new SimpleSchemaAttribute(
				'content',
				'Content',
				false,
				array(),
				SchemaAttributeTypes::HTML
			),
		);
	}

	/**
	 * Gets schema specific attributes for page
	 *
	 * @return array
	 */
	private function get_page_attributes() {
		return array(
			new EnumSchemaAttribute(
				'post_parent',
				'Parent',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				$this->get_article_repository()->get_all_parents()
			),
			new SimpleSchemaAttribute(
				'menu_order',
				'Order',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				SchemaAttributeTypes::NUMBER
			),
		);
	}

	/**
	 * Gets schema specific attributes for post
	 *
	 * @return array
	 */
	private function get_post_attributes() {
		return array(
			new EnumSchemaAttribute(
				'post_format',
				'Format',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				$this->get_article_repository()->get_all_formats()
			),
			new SimpleCollectionSchemaAttribute(
				'category',
				'Categories',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL ),
				SchemaAttributeTypes::TEXT
			),
			new SimpleCollectionSchemaAttribute(
				'tags',
				'Tags',
				true,
				array( Conditions::CONTAINS ),
				SchemaAttributeTypes::TEXT
			),
			new SimpleCollectionSchemaAttribute(
				'send_trackbacks',
				'Send trackbacks',
				false,
				array(),
				SchemaAttributeTypes::URL
			),
			new SimpleSchemaAttribute(
				'post_excerpt',
				'Excerpt',
				true,
				array( Conditions::EQUALS, Conditions::NOT_EQUAL, Conditions::CONTAINS ),
				SchemaAttributeTypes::TEXT
			),
		);
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
