<?php

namespace Vendidero\StoreaBill\Lexoffice\API;

use Vendidero\StoreaBill\API\REST;
use Vendidero\StoreaBill\API\RESTResponse;
use Vendidero\StoreaBill\ExternalSync\Helper;
use Vendidero\StoreaBill\Interfaces\OAuth;
use Vendidero\StoreaBill\Lexoffice\Package;
use Vendidero\StoreaBill\Lexoffice\Sync;

defined( 'ABSPATH' ) || exit;

class Auth extends REST implements OAuth {

	/**
	 * @var null|Sync
	 */
	protected $sync_helper = null;

	public function __construct( $sync_helper ) {
		$this->sync_helper = $sync_helper;
	}

	public function get_type() {
		return 'oauth';
	}

	public function ping() {
		return $this->get_sync_helper()->get_api()->ping();
	}

	public function get_description() {
		return _x( 'Click on the button and login to lexoffice to authorize Germanized. After that you will be redirected to a page showing an authorization code. Please copy & paste that code into the field below.', 'lexoffice', 'woocommerce-germanized-pro' );
	}

	/**
	 * @return Sync
	 */
	protected function get_sync_helper() {
		return $this->sync_helper;
	}

	public function get_url() {
		return Package::get_auth_url();
	}

	protected function get_basic_auth() {
		return 'Basic ' . base64_encode( Package::get_client_id() . ':' . Package::get_client_secret() );
	}

	/**
	 * @return null|\DateTime
	 */
	public function get_expires_on() {
		$expires_on = $this->get_sync_helper()->get_setting( 'expires_on' );

		if ( ! empty( $expires_on ) ) {
			try {
				$expires_on = new \DateTime( "@$expires_on" );
			} catch( \Exception $e ) {}
		}

		return $expires_on;
	}

	public function has_expired() {
		if ( $expires = $this->get_expires_on() ) {
			return ( new \DateTime() > $expires );
		}

		return false;
	}

	public function get_refresh_code_expires_on() {
		$expires_on = $this->get_sync_helper()->get_setting( 'refresh_expires_on' );

		if ( ! empty( $expires_on ) ) {
			try {
				$expires_on = new \DateTime( "@$expires_on" );
			} catch( \Exception $e ) {}
		}

		return $expires_on;
	}

	public function refresh_code_has_expired() {
		if ( $expires = $this->get_refresh_code_expires_on() ) {
			return ( new \DateTime() > $expires );
		}

		return false;
	}

	public function get_authorization_url() {
		$url = add_query_arg( array(
			'client_id'     => Package::get_client_id(),
			'response_type' => 'code',
			'redirect_uri'  => '/api/oauth2/authorization_code',
			'scopes'        => 'profile.read,vouchers.read,vouchers.write,contacts.read,contacts.write,files.write,transaction-assignment-hint.write'
		), $this->get_url() . 'authorize' );

		if ( $this->is_connected() ) {
			$url = add_query_arg( array( 'reauthorize' => '' ), $url );
		}

		return $url;
	}

	public function is_manual_authorization() {
		return true;
	}

	protected function get_content_type() {
		return 'application/x-www-form-urlencoded';
	}

	protected function update_access_token( $token ) {
		$this->get_sync_helper()->update_setting( 'access_token', $token );
	}

	protected function update_refresh_token( $token ) {
		$this->get_sync_helper()->update_setting( 'refresh_token', $token );
	}

	/**
	 * @param \DateTime|null $expires_in
	 */
	protected function update_expires_on( $expires_in ) {
		$this->get_sync_helper()->update_setting( 'expires_on', $expires_in ? $expires_in->getTimestamp() : null );
	}

	/**
	 * @param \DateTime|null $expires_in
	 */
	protected function update_refresh_token_expires_on( $expires_in ) {
		$this->get_sync_helper()->update_setting( 'refresh_expires_on', $expires_in ? $expires_in->getTimestamp() : null );
	}

	public function auth( $authorization_code = '' ) {
		$result = $this->get_sync_helper()->parse_response( $this->post( 'token', array(
			'grant_type'   => 'authorization_code',
			'code'         => $authorization_code,
			'redirect_uri' => '/api/oauth2/authorization_code',
		) ) );

		if ( ! is_wp_error( $result ) && $result->get( 'access_token' ) ) {
			$this->update_access_token( $result->get( 'access_token' ) );
			$this->update_refresh_token( $result->get( 'refresh_token' ) );

			$expires_in = absint( $result->get( 'expires_in' ) );

			$expires = new \DateTime();
			$expires->setTimestamp( time() + $expires_in );
			$expires->modify( '-5 minutes' );

			$this->update_expires_on( $expires );

			$refresh_token_expires = new \DateTime();
			$refresh_token_expires->modify( '+23 months' );

			$this->update_refresh_token_expires_on( $refresh_token_expires );

			Helper::auth_successful( $this->get_sync_helper() );

			return true;
		} else {
			return $result;
		}
	}

	public function disconnect() {
		$this->get_sync_helper()->get_api()->revoke();

		$this->update_refresh_token( '' );
		$this->update_access_token( '' );
		$this->update_expires_on( null );
		$this->update_refresh_token_expires_on( null );

		return true;
	}

	public function get_access_token() {
		return $this->get_sync_helper()->get_setting( 'access_token' );
	}

	public function get_refresh_token() {
		return $this->get_sync_helper()->get_setting( 'refresh_token' );
	}

	public function refresh() {
		$result = $this->get_sync_helper()->parse_response( $this->post( 'token', array(
			'grant_type'    => 'refresh_token',
			'refresh_token' => $this->get_refresh_token()
		) ) );

		if ( ! is_wp_error( $result ) && $result->get( 'access_token' ) ) {
			$this->update_access_token( $result->get( 'access_token' ) );

			$expires_in = absint( $result->get( 'expires_in' ) );
			$expires    = new \DateTime();

			$expires->setTimestamp( time() + $expires_in );
			$expires->modify( '-5 minutes' );

			$this->update_expires_on( $expires );

			return true;
		} else {
			return $result;
		}
	}

	public function is_connected() {
		$token = $this->get_access_token();
		$ping  = $this->ping();

		return ( ! empty( $token ) && ! $this->refresh_code_has_expired() && true === $ping ) ? true : false;
	}
}