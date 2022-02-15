<?php

namespace Vendidero\StoreaBill\Document;

use Vendidero\StoreaBill\Admin\Notices;

defined( 'ABSPATH' ) || exit;

/**
 * Shipment Order
 *
 * @class 		WC_GZD_Shipment_Order
 * @version		1.0.0
 * @author 		Vendidero
 */
abstract class BulkActionHandler {

	/**
	 * Step being handled
	 *
	 * @var integer
	 */
	protected $step = 1;

	protected $ids = array();

	protected $type = '';

	protected $id = '';

	protected $reference_type = '';

	public function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'object_type'   => 'invoice',
			'step'          => 1,
			'ids'           => array(),
			'id'            => '',
		) );

		foreach( $args as $arg => $data ) {
			$setter = 'set_'  .$arg;

			if ( is_callable( array( $this, $setter ) ) ) {
				$this->$setter( $data );
			}
		}
	}

	abstract public function get_title();

	abstract public function get_action_name();

	public function set_id( $id ) {
		$this->id = $id;
	}

	public function get_id() {
		return $this->id;
	}

	public function set_reference_type( $reference_type ) {
		$this->reference_type = $reference_type;
	}

	public function get_reference_type() {
		return $this->reference_type;
	}

	public function get_action() {
		$action = substr( $this->get_action_name(), 0, 5 ) !== 'bulk_' ? 'bulk_' . $this->get_action_name() : $this->get_action_name();

		return sanitize_key( $action );
	}

	abstract public function get_limit();

	abstract public function handle();

	/**
	 * When executing a bulk action and the default date sort takes place
	 * ids are parsed in a descending order (e.g. newest documents first).
	 * Many bulk actions (sync, finalize invoices) should rather parse in a
	 * ascending order (e.g. oldest documents first).
	 *
	 * @return bool
	 */
	public function parse_ids_ascending() {
		return true;
	}

	public function get_id_order_by_column() {
		return 'date';
	}

	public function finish() {
		Notices::output( $this->get_notice_screen_id(), 'success' );
		Notices::output( $this->get_notice_screen_id(), 'error' );

		$this->reset();
	}

	public function get_notices( $type = 'error' ) {
		return Notices::get( $this->get_notice_screen_id(), $type );
	}

	public function add_notice( $notice, $type = 'error' ) {
		Notices::add( $notice, $type, $this->get_notice_screen_id() );
	}

	public function get_nonce_action() {
		$action = sanitize_key( $this->get_action_name() );

		return "sab-bulk-{$this->get_object_type()}-{$action}";
	}

	public function set_object_type( $type ) {
		$this->type = $type;
	}

	public function get_object_type() {
		return $this->type;
	}

	protected function get_admin_url() {
		return admin_url( 'admin.php?page=sab-accounting' );
	}

	public function get_done_redirect_url() {
		$page = $this->get_admin_url();
		$page = add_query_arg( array(
			'object_type'          => $this->get_object_type(),
			'document_type'        => $this->get_object_type(),
			'bulk_action_handling' => 'finished',
			'current_bulk_action'  => sanitize_key( $this->get_action() ),
		), $page );

		return html_entity_decode( wp_nonce_url( $page, $this->get_done_nonce_action() ) );
	}

	public function get_done_nonce_action() {
		return $this->get_nonce_action() . '-done';
	}

	public function get_step() {
		return $this->step;
	}

	public function set_step( $step ) {
		$this->step = absint( $step );
	}

	public function get_success_message() {
		return _x( 'Successfully processed documents.', 'storeabill-core', 'woocommerce-germanized-pro' );
	}

	public function get_max_step() {
		return (int) ceil( sizeof( $this->get_ids() ) / $this->get_limit() );
	}

	public function get_total() {
		return sizeof( $this->get_ids() );
	}

	public function set_ids( $ids ) {
		$this->ids = $ids;
	}

	public function get_ids() {
		return $this->ids;
	}

	public function get_current_ids() {
		return array_slice( $this->get_ids(), ( $this->get_step() - 1 ) * $this->get_limit(), $this->get_limit() );
	}

	/**
	 * Get count of records exported.
	 *
	 * @since 3.0.6
	 * @return int
	 */
	public function get_total_processed() {
		return ( $this->get_step() * $this->get_limit() );
	}

	/**
	 * Get total % complete.
	 *
	 * @since 3.0.6
	 * @return int
	 */
	public function get_percent_complete() {
		return floor( ( $this->get_total_processed() / $this->get_total() ) * 100 );
	}

	public function is_last_step() {
		$current_step = $this->get_step();
		$max_step     = $this->get_max_step();

		if ( $max_step === $current_step ) {
			return true;
		}

		return false;
	}

	protected function get_notice_screen_id() {
		return sanitize_key( 'sab-bulk-action-' . $this->get_object_type() . '-' . $this->get_action_name() );
	}

	public function reset() {
		Notices::remove( $this->get_notice_screen_id() );
		$this->set_step( 1 );
	}
}