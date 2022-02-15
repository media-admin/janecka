<?php

namespace Vendidero\StoreaBill\ExternalSync;

defined( 'ABSPATH' ) || exit;

class SyncData {

	protected $id = '';

	protected $version = 1;

	protected $last_updated = null;

	protected $data = array();

	protected $handler_name = '';

	public function __construct( $handler_name, $args = array() ) {
		$this->handler_name = $handler_name;

		foreach( $args as $key => $arg ) {
			$this->set( $key, $arg );
		}
	}

	public function get_handler_name() {
		return $this->handler_name;
	}

	public function get_id() {
		return $this->id;
	}

	/**
	 * @return null|\WC_DateTime
	 */
	public function get_last_updated() {
		return $this->last_updated;
	}

	/**
	 * @return null|\WC_DateTime
	 */
	public function last_updated() {
		return $this->get_last_updated();
	}

	public function get_version() {
		return $this->version;
	}

	public function set_id( $id ) {
		$this->id = $id;
	}

	public function set_version( $version ) {
		$this->version = $version;
	}

	public function set_last_updated( $date ) {
		if ( is_numeric( $date ) ) {
			$date = gmdate("Y-m-d\TH:i:s\Z", $date );
		}

		$this->last_updated = sab_string_to_datetime( $date );
	}

	public function set( $prop, $value ) {
		$setter = 'set_' . $prop;

		if ( is_callable( array( $this, $setter ) ) ) {
			$this->$setter( $value );
		} else {
			$this->data[ $prop ] = $value;
		}
	}

	public function get( $prop ) {
		$getter = 'get_' . $prop;

		if ( is_callable( array( $this, $getter ) ) ) {
			return $this->$getter();
		} elseif ( array_key_exists( $prop, $this->data ) ) {
			return $this->data[ $prop ];
		} else {
			return null;
		}
	}

	public function get_data() {
		$data = array(
			'last_updated' => $this->get_last_updated() ? $this->get_last_updated()->getTimestamp() : null,
			'id'           => $this->get_id(),
			'version'      => $this->get_version(),
		);

		$data = array_merge( $data, $this->data );

		return $data;
	}
}