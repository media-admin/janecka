<?php

namespace Vendidero\StoreaBill\sevDesk\API;

use Vendidero\StoreaBill\Interfaces\TokenAuth;
use Vendidero\StoreaBill\sevDesk\Sync;

defined( 'ABSPATH' ) || exit;

class Auth implements TokenAuth {

	/**
	 * @var null|Sync
	 */
	protected $sync_helper = null;

	public function __construct( $sync_helper ) {
		$this->sync_helper = $sync_helper;
	}

	public function get_description() {
		return _x( 'To connect your sevDesk account with StoreaBill, login to your sevDesk dashboard and navigate to settings > users. Choose your profile and copy the API token to the field above.', 'sevdesk', 'woocommerce-germanized-pro' );
	}

	public function get_type() {
		return 'token';
	}

	public function ping() {
		return $this->get_sync_helper()->get_api()->ping();
	}

	/**
	 * @return Sync
	 */
	protected function get_sync_helper() {
		return $this->sync_helper;
	}

	public function get_token() {
		return $this->sync_helper->get_auth_token();
	}

	public function get_token_url() {
		return 'https://my.sevdesk.de/#/admin/userManagement';
	}
}