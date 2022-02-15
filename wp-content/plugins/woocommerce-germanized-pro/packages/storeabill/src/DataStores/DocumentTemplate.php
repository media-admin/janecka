<?php

namespace Vendidero\StoreaBill\DataStores;

use Exception;
use WC_Data_Store_WP;
use WC_Object_Data_Store_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Order Data Store: Stored in CPT.
 *
 * @version  3.0.0
 */
class DocumentTemplate extends WC_Data_Store_WP implements WC_Object_Data_Store_Interface {

	/**
	 * Internal meta type used to store order data.
	 *
	 * @var string
	 */
	protected $meta_type = 'post';

	/**
	 * Data stored in meta keys, but not considered "meta" for an order.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $internal_meta_keys = array(
		'_pdf_template_id',
		'_template_name',
		'_document_type',
		'_fonts',
		'_font_size',
		'_color',
		'_line_item_types'
	);

	/*
	|--------------------------------------------------------------------------
	| CRUD Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Method to create a new template in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Template $template Template object.
	 */
	public function create( &$template ) {

		$template->set_date_created( time() );

		if ( empty( $template->get_margins() ) ) {
			$template->set_margins( $template->get_default_margins() );
		}

		/**
		 * Note: addslashes() is needed if storing the data with wp_insert_post()
		 * because those strip slashes and \u003Ca (block attributes) looks like a slash that needs to be stripped unless it itself is slashed.
		 */
		$id = wp_insert_post(
			apply_filters(
				'storeabill_new_template_data',
				array(
					'post_date'     => gmdate( 'Y-m-d H:i:s', $template->get_date_created( 'edit' )->getOffsetTimestamp() ),
					'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $template->get_date_created( 'edit' )->getTimestamp() ),
					'post_type'     => $template->get_type(),
					'post_status'   => $this->get_post_status( $template ),
					'ping_status'   => 'closed',
					'post_author'   => 1,
					'post_title'    => $template->get_title( 'edit' ),
					'post_parent'   => is_callable( array( $template, 'get_parent_id' ) ) ? $template->get_parent_id( 'edit' ) : 0,
					'post_content'  => addslashes( $template->get_content( 'edit' ) ),
					'post_excerpt'  => '',
				)
			),
			true
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$template->set_id( $id );
			$this->update_post_meta( $template );
			$template->save_meta_data();
			$template->apply_changes();
			$this->clear_caches( $template );
		}
	}

	/**
	 * Method to read a template from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Template $template Template object.
	 *
	 * @throws Exception If passed template is invalid.
	 */
	public function read( &$template ) {
		$template->set_defaults();
		$post_object = get_post( $template->get_id() );

		if ( ! $template->get_id() || ! $post_object || ! in_array( $post_object->post_type, array( 'document_template' ), true ) ) {
			throw new Exception( _x( 'Invalid document template.', 'storeabill-core', 'woocommerce-germanized-pro' ) );
		}

		$template->set_props(
			array(
				'date_created'  => '0000-00-00 00:00:00' !== $post_object->post_date_gmt ? wc_string_to_timestamp( $post_object->post_date_gmt ) : null,
				'date_modified' => '0000-00-00 00:00:00' !== $post_object->post_modified_gmt ? wc_string_to_timestamp( $post_object->post_modified_gmt ) : null,
				'status'        => $post_object->post_status,
				'content'       => $post_object->post_content,
				'title'         => $post_object->post_title,
			)
		);

		if ( is_callable( array( $template, 'set_parent_id' ) ) ) {
			$template->set_parent_id( $post_object->post_parent );
		}

		$this->read_template_data( $template, $post_object );
		$template->read_meta_data();
		$template->set_object_read( true );
	}

	/**
	 * Method to update a template in the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Template $template Template object.
	 */
	public function update( &$template ) {
		$template->save_meta_data();

		if ( null === $template->get_date_created( 'edit' ) ) {
			$template->set_date_created( time() );
		}

		$changes = $template->get_changes();

		// Only update the post when the post data changes.
		if ( array_intersect( array( 'date_created', 'date_modified', 'status', 'title', 'parent_id', 'content' ), array_keys( $changes ) ) ) {
			$post_data = array(
				'post_date'         => gmdate( 'Y-m-d H:i:s', $template->get_date_created( 'edit' )->getOffsetTimestamp() ),
				'post_date_gmt'     => gmdate( 'Y-m-d H:i:s', $template->get_date_created( 'edit' )->getTimestamp() ),
				'post_status'       => $this->get_post_status( $template ),
				'post_title'        => $template->get_title( 'edit' ),
				'post_parent'       => is_callable( array( $template, 'get_parent_id' ) ) ? $template->get_parent_id( 'edit' ) : 0,
				'post_content'      => addslashes( $template->get_content( 'edit' ) ),
				'post_modified'     => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $template->get_date_modified( 'edit' )->getOffsetTimestamp() ) : current_time( 'mysql' ),
				'post_modified_gmt' => isset( $changes['date_modified'] ) ? gmdate( 'Y-m-d H:i:s', $template->get_date_modified( 'edit' )->getTimestamp() ) : current_time( 'mysql', 1 ),
			);

			/**
			 * When updating this object, to prevent infinite loops, use $wpdb
			 * to update data, since wp_update_post spawns more calls to the
			 * save_post action.
			 *
			 * This ensures hooks are fired by either WP itself (admin screen save),
			 * or an update purely from CRUD.
			 */
			if ( doing_action( 'save_post' ) ) {
				$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $template->get_id() ) );
				clean_post_cache( $template->get_id() );
			} else {
				wp_update_post( array_merge( array( 'ID' => $template->get_id() ), $post_data ) );
			}
			$template->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
		}

		$this->update_post_meta( $template );
		$template->apply_changes();
		$this->clear_caches( $template );
	}

	/**
	 * Method to delete a template from the database.
	 *
	 * @param \Vendidero\StoreaBill\Document\Template $template Template object.
	 * @param array    $args Array of args to pass to the delete method.
	 *
	 * @return void
	 */
	public function delete( &$template, $args = array() ) {
		$id   = $template->get_id();
		$args = wp_parse_args(
			$args,
			array(
				'force_delete' => false,
			)
		);

		if ( ! $id ) {
			return;
		}

		if ( $args['force_delete'] ) {
			wp_delete_post( $id );
			$template->set_id( 0 );
			do_action( 'storeabill_delete_document_template', $id );
		} else {
			wp_trash_post( $id );
			$template->set_status( 'trash' );
			do_action( 'storeabill_trash_document_template', $id );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the status to save to the post object.
	 *
	 * Plugins extending the order classes can override this to change the stored status/add prefixes etc.
	 *
	 * @param  \Vendidero\StoreaBill\Document\Template $template Template object.
	 *
	 * @return string
	 *@since 3.6.0
	 */
	protected function get_post_status( $template ) {
		$template_status = $template->get_status( 'edit' );

		if ( ! $template_status ) {
			$template_status = apply_filters( 'storeabill_default_document_template_status', 'draft' );
		}

		$post_status = $template_status;

		return $post_status;
	}

	/**
	 * Read template data. Can be overridden by child classes to load other props.
	 *
	 * @param \Vendidero\StoreaBill\Document\Template $template Template object.
	 * @param object   $post_object Post object.
	 *
	 * @since 3.0.0
	 */
	protected function read_template_data( &$template, $post_object ) {
		$id = $template->get_id();

		$template->set_props(
			array(
				'pdf_template_id' => get_post_meta( $id, '_pdf_template_id', true ),
				'margins'         => wp_parse_args( get_post_meta( $id, '_margins', true ), $template->get_default_margins() ),
				'template_name'   => get_post_meta( $id, '_template_name', true ),
			)
		);

		// Gets extra data associated with the order if needed.
		foreach ( $template->get_extra_data_keys() as $key ) {
			$function = 'set_' . $key;

			if ( is_callable( array( $template, $function ) ) ) {
				$value = get_post_meta( $template->get_id(), '_' . $key, true );

				$template->{ $function }( $value );
			}
		}
	}

	/**
	 * Helper method that updates all the post meta for an order based on it's settings in the WC_Order class.
	 *
	 * @param \Vendidero\StoreaBill\Document\Template $template Template object.
	 *
	 * @since 3.0.0
	 */
	protected function update_post_meta( &$template ) {
		$updated_props     = array();
		$meta_key_to_props = array(
			'_document_type'   => 'document_type',
			'_pdf_template_id' => 'pdf_template_id',
			'_margins'         => 'margins',
			'_fonts'           => 'fonts',
			'_font_size'       => 'font_size',
			'_color'           => 'color',
			'_line_item_types' => 'line_item_types',
			'_template_name'   => 'template_name'
		);

		$props_to_update = $this->get_props_to_update( $template, $meta_key_to_props );

		foreach ( $props_to_update as $meta_key => $prop ) {
			$getter = "get_$prop";

			if ( is_callable( array( $template, $getter ) ) ) {
				$value = $template->{ $getter }( 'edit' );
				$value = is_string( $value ) ? wp_slash( $value ) : $value;

				if ( 'margins' === $prop && $template->is_first_page() ) {
					unset( $value['left'] );
					unset( $value['right'] );
				}

				$updated = $this->update_or_delete_post_meta( $template, $meta_key, $value );

				if ( $updated ) {
					$updated_props[] = $prop;
				}
			}
		}

		do_action( 'storeabill_document_template_object_updated_props', $template, $updated_props );
	}

	/**
	 * Clear any caches.
	 *
	 * @param \Vendidero\StoreaBill\Document\Template $template Template object.
	 *
	 * @since 3.0.0
	 */
	protected function clear_caches( &$template ) {
		clean_post_cache( $template->get_id() );
		wp_cache_delete( $template->get_id(), $this->meta_type . '_meta' );
	}

	/**
	 * @param \Vendidero\StoreaBill\Document\Template $template
	 */
	public function get_first_page( $template ) {

		if ( $template->is_first_page() ) {
			return $template;
		} else {
			$children = get_posts( array(
				'post_parent' => $template->get_id(),
				'post_type'   => $template->get_type(),
				'post_status' => 'any',
				'numberposts' => 1,
			) );

			if ( ! empty( $children ) ) {
				return sab_get_document_template( $children[0], true );
			}
		}

		return false;
	}
}
