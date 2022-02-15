<?php

namespace Vendidero\StoreaBill\sevDesk\API;

use Vendidero\StoreaBill\API\REST;
use Vendidero\StoreaBill\API\RESTResponse;
use Vendidero\StoreaBill\sevDesk\Package;
use Vendidero\StoreaBill\sevDesk\Sync;

defined( 'ABSPATH' ) || exit;

class Models extends REST {

	/**
	 * @var Sync
	 */
	protected $sync_helper = null;

	protected $countries = array();

	protected $country_code_to_id = array();

	protected $hook_prefix = '';

	public function __construct( $helper, $hook_prefix = '' ) {
		$this->sync_helper = $helper;
		$this->hook_prefix = $hook_prefix;
	}

	/**
	 * @return Sync
	 */
	protected function get_sync_helper() {
		return $this->sync_helper;
	}

	/**
	 * @return Auth $auth
	 */
	protected function get_auth() {
		return $this->get_sync_helper()->get_auth_api();
	}

	protected function get_basic_auth() {
		return $this->get_auth()->get_token();
	}

	public function get_url() {
		return Package::get_api_url();
	}

	public function get_content_type() {
		return 'application/x-www-form-urlencoded';
	}

	protected function maybe_encode_body( $body_args ) {
		if ( 'application/x-www-form-urlencoded' === $this->get_content_type() ) {
			return http_build_query( $body_args );
		}

		return $body_args;
	}

	public function ping() {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'Contact' ) );

		if ( ! is_wp_error( $result ) ) {
			return true;
		}

		return false;
	}

	public function get_contact( $id ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'Contact/' . $id ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	public function get_accounts() {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'CheckAccount' ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body()['objects'];
		} else {
			return array();
		}
	}

	public function get_categories() {
		$result     = $this->get_sync_helper()->parse_response( $this->get( 'AccountingType', array( 'onlyOwn' => false, 'parent' => array( 'objectName' => 'AccountingType', 'id' => '24' ), 'embed' => 'accountingSystemNumber' ) ) );
		$categories = array();

		if ( ! is_wp_error( $result ) ) {
			$categories = array_merge( $categories, $result->get_body()['objects'] );
		}

		$result = $this->get_sync_helper()->parse_response( $this->get( 'AccountingType', array( 'onlyOwn' => true, 'embed' => 'accountingSystemNumber' ) ) );

		if ( ! is_wp_error( $result ) ) {
			$categories = array_merge( $categories, $result->get_body()['objects'] );
		}

		return $categories;
	}

	public function get_address( $id ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'ContactAddress/' . $id ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	/**
	 * Somehow sevDesk expects data to be converted to string (e.g. 'null' instead of null).
	 *
	 * @param $url
	 * @param string $type
	 * @param array $body_args
	 * @param array $header
	 *
	 * @return array|RESTResponse|\WP_Error
	 */
	protected function get_response( $url, $type = 'GET', $body_args = array(), $header = array() ) {
		if ( ! isset( $body_args['file'] ) ) {
			$to_string = function( $data ) {
				return is_null( $data ) ? 'null' : strval( $data );
			};

			$body_args = $this->array_map_recursive( $to_string, $body_args );
		}

		return parent::get_response( $url, $type, $body_args, $header );
	}

	private function array_map_recursive( $callback, $array ) {
		$func = function ( $item ) use ( &$func, &$callback ) {
			return is_array( $item ) ? array_map( $func, $item ) : call_user_func( $callback, $item );
		};

		return array_map( $func, $array );
	}

	/**
	 * @param \WP_Error|boolean|mixed $api_result
	 *
	 * @return bool
	 */
	public function has_failed( $api_result ) {
		return ( is_wp_error( $api_result ) || false === $api_result ) ? true : false;
	}

	/**
	 * @param \WP_Error|boolean|mixed $result
	 *
	 * @return bool
	 */
	public function is_404( $result ) {
		return ( is_wp_error( $result ) && 404 === $result->get_error_code() );
	}

	public function search_contacts( $term ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'Search/search', array( 'searchType' => 'CONTACT', 'term' => $term, 'embed' => 'contact,contact.parent,parent,category' ) ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	public function book_voucher( $voucher_id, $amount, $args = array() ) {
		$args = wp_parse_args( $args, array(
			'date'        => current_time( 'Y-m-d' ),
			'type'        => 0,
			'account'     => '',
			'transaction' => '',
		) );

		$query_args = array(
			'date'       => $args['date'],
			'type'       => $args['type'],
			'ammount'    => $amount,
			'createFeed' => 'true'
		);

		if ( ! empty( $args['account'] ) ) {
			$query_args['checkAccount'] = array(
				'objectName' => 'CheckAccount',
				'id'         => $args['account']
			);
		}

		if ( ! empty( $args['transaction'] ) ) {
			$query_args['checkAccountTransaction'] = array(
				'objectName' => 'CheckAccountTransaction',
				'id'         => $args['transaction']
			);
		}

		return $this->get_sync_helper()->parse_response( $this->put( 'Voucher/' . $voucher_id . '/bookAmmount', $query_args ) );
	}

	public function search_transactions( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'amount_from' => null,
			'amount_to'   => null,
			'is_booked'   => false,
			'account'     => '',
			'limit'       => 100,
			'status'      => 100,
			'start_date'  => '',
			'end_date'    => '',
		) );

		$query_args = array(
			'limit'        => $args['limit'],
			'endAmount'    => $args['amount_to'],
			'startAmount'  => $args['amount_from'],
			'isBooked'     => $args['is_booked'] ? 'true' : 'false',
			'status'       => $args['status'],
			'hideFees'     => 'true',
			'orderBy'      => array(
				array(
					'field'       => 'entryDate',
					'arrangement' => 'desc'
				)
			),
		);

		if ( ! empty( $args['start_date'] ) ) {
			$query_args['startDate'] = $args['start_date'];
		}

		if ( ! empty( $args['end_date'] ) ) {
			$query_args['endDate'] = $args['end_date'];
		}

		if ( ! empty( $args['account'] ) ) {
			$query_args['checkAccount'] = array(
				'objectName' => 'CheckAccount',
				'id'         => $args['account']
			);
 		}

		$result = $this->get_sync_helper()->parse_response( $this->get( 'CheckAccountTransaction', apply_filters( "{$this->hook_prefix}search_transactions_query", $query_args, $this->sync_helper, $this ) ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	public function get_addresses( $contact_id, $type = 47 ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'ContactAddresses/' . $contact_id, array( 'category' => array( 'id' => $type, 'objectName' => 'Category' ) ) ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	public function create_contact( $data ) {
		return $this->get_sync_helper()->parse_response( $this->post( 'Contact', $data ) );
	}

	public function update_contact( $id, $data ) {
		return $this->get_sync_helper()->parse_response( $this->put( 'Contact/' . $id, $data ) );
	}

	public function create_address( $data ) {
		return $this->get_sync_helper()->parse_response( $this->post( 'ContactAddress', $data ) );
	}

	public function update_address( $id, $data ) {
		return $this->get_sync_helper()->parse_response( $this->put( 'ContactAddress/' . $id, $data ) );
	}

	public function get_communication_ways( $contact_id, $type = 'EMAIL' ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'CommunicationWay', array(
			'type' => $type,
			'contact' => array(
				'id'         => $contact_id,
				'objectName' => 'Contact'
			),
		) ) );

		if ( ! is_wp_error( $result ) ) {
			if ( sizeof( $result->get( 'objects' ) ) > 0 ) {
				return $result->get( 'objects' );
			} else {
				return false;
			}
		} else {
			return $result;
		}
	}

	protected function get_communication_way( $id, $type = 'EMAIL' ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'CommunicationWay', array(
			'communicationWay' => array(
				'id'         => $id,
				'objectName' => 'CommunicationWay'
			),
			'type' => $type,
		) ) );

		if ( ! is_wp_error( $result ) ) {
			if ( sizeof( $result->get( 'objects' ) ) > 0 ) {
				return $result->get( 'objects' )[0]['value'];
			} else {
				return false;
			}
		} else {
			return $result;
		}
	}

	public function get_email( $id ) {
		return $this->get_communication_way( $id, 'EMAIL' );
	}

	public function get_phone( $id ) {
		return $this->get_communication_way( $id, 'PHONE' );
	}

	protected function create_communication_way( $data ) {
		return $this->get_sync_helper()->parse_response( $this->post( 'CommunicationWay', $data ) );
	}

	protected function update_communication_way( $id, $data ) {
		return $this->get_sync_helper()->parse_response( $this->put( 'CommunicationWay/' . $id, $data ) );
	}

	public function create_email( $data ) {
		return $this->create_communication_way( $data );
	}

	public function update_email( $id, $data ) {
		return $this->update_communication_way( $id, $data );
	}

	public function create_phone( $data ) {
		return $this->create_communication_way( $data );
	}

	public function update_phone( $id, $data ) {
		return $this->update_communication_way( $id, $data );
	}

	public function get_voucher( $id ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'Voucher/' . $id, array(
			'origin' => array(
				'objectName' => 'Voucher',
				'id'         => $id,
			),
		) ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	public function mark_voucher_as_open( $id ) {
		$result = $this->get_sync_helper()->parse_response( $this->put( 'Voucher/' . $id . '/markAsOpen' ) );

		if ( ! is_wp_error( $result ) ) {
			return true;
		} else {
			return $result;
		}
	}

	public function update_voucher( $id, $data ) {
		$data['voucher']['id'] = $id;

		return $this->get_sync_helper()->parse_response( $this->post( 'Voucher/Factory/saveVoucher', $data ) );
	}

	public function create_voucher( $data ) {
		return $this->get_sync_helper()->parse_response( $this->post( 'Voucher/Factory/saveVoucher', $data ) );
	}

	public function get_voucher_items( $id ) {
		$result = $this->get_sync_helper()->parse_response( $this->get( 'Voucher/' . $id . '/getPositions' ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return $result;
		}
	}

	public function update_voucher_status( $id, $status ) {
		$result = $this->get_sync_helper()->parse_response( $this->put( 'Voucher/' . $id . '/changeStatus', array(
			'value' => $status,
		) ) );

		if ( ! is_wp_error( $result ) ) {
			return $result->get_body();
		} else {
			return false;
		}
	}

	/**
	 * Uploads a file and returns SevDesk internal filename in case of success.
	 *
	 * @param $file
	 *
	 * @return string|\WP_Error
	 */
	public function upload_voucher_file( $file, $voucherType = 'D' ) {
		try {
			$curl_file = new \CURLFile( $file, 'application/pdf' );

			/**
			 * Prevent WP from overriding CURLOPT_POSTFIELDS with string data.
			 *
			 * @param $handle
			 */
			$callback = function( $handle ) use ( $curl_file ) {
				if ( function_exists( 'curl_init' ) && function_exists( 'curl_exec' ) ) {
					curl_setopt( $handle, CURLOPT_POSTFIELDS, array( 'file' => $curl_file, 'type' => 'voucher' ) );
				}
			};

			add_action( 'http_api_curl', $callback, 10, 3 );
			$result = $this->get_sync_helper()->parse_response( $this->post( 'Voucher/Factory/uploadTempFile', array( 'file' => $curl_file ), array( 'Content-Type' => 'multipart/form-data' ) ) );
			remove_action( 'http_api_curl', $callback, 10 );

			if ( ! is_wp_error( $result ) ) {
				$filename = $result->get( 'objects' )['filename'];

				/**
				 * Create JPG preview
				 */
				/*$jpg_result = $this->post( 'Voucher/Factory/createFromPdf', array(
					'fileName'    => $filename,
					'mimeType'    => 'image/jpg',
					'creditDebit' => $voucherType,
				) );*/

				return $filename;
			}

			return $result;
		} catch( \Exception $e ) {}

		return new \WP_Error( 'api-error', _x( 'Error while uploading file to voucher', 'sevdesk', 'woocommerce-germanized-pro' ) );
	}

	public function get_countries() {
		if ( empty( $this->countries ) ) {
			$countries = $this->get_sync_helper()->parse_response( $this->get( 'StaticCountry', array( 'limit' => 999 ) ) );

			if ( ! is_wp_error( $countries ) ) {
				$this->countries = $countries->get( 'objects' );
			} else {
				$this->countries = array();
			}
		}

		return $this->countries;
	}

	public function get_country_id_by_code( $code ) {
		if ( array_key_exists( $code, $this->country_code_to_id ) ) {
			return $this->country_code_to_id[ $code ];
		} else {
			$countries = $this->get_countries();

			foreach( $countries as $country ) {
				if ( strtoupper( $country['code'] ) === $code ) {
					$this->country_code_to_id[ $code ] = absint( $country['id'] );
					break;
				}
			}
		}

		/**
		 * Fall back to Germany
		 */
		return array_key_exists( $code, $this->country_code_to_id ) ? $this->country_code_to_id[ $code ] : 1;
	}
}