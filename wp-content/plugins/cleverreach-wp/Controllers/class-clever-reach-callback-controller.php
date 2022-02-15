<?php
/**
 * CleverReach WordPress Integration.
 *
 * @package CleverReach
 */

namespace CleverReach\WordPress\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use CleverReach\WordPress\Components\InfrastructureServices\Config_Service;
use CleverReach\WordPress\Components\Sync\Initial_Sync_Task;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\AuthInfo;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Proxy\AuthProxy;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Configuration;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\RefreshUserInfoTask;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\BadAuthInfoException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Queue;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\QueueItem;
use CleverReach\WordPress\Components\Utility\Helper;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException;

/**
 * Class Clever_Reach_Callback_Controller
 *
 * @package CleverReach\WordPress\Controllers
 */
class Clever_Reach_Callback_Controller extends Clever_Reach_Base_Controller {

	/**
	 * Run the request
	 *
	 * @throws QueueStorageUnavailableException
	 * @throws InvalidConfigurationException
	 * @throws HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws RefreshTokenExpiredException
	 */
	public function run() {
		$this->validate_request();

		if ( $this->get_param( 'refresh' ) ) {
			$auth_info = $this->get_auth_info( true );
			$this->refresh_tokens( $auth_info );
		} else {
			$auth_info = $this->get_auth_info( false );
			$this->queue_refresh_user_task( $auth_info );
		}

		include dirname( __DIR__ ) . '/resources/views/close-frame.php';
	}

	/**
	 * Validates the request
	 */
	private function validate_request() {
		$code = $this->get_param( 'code' );

		if ( empty( $code ) ) {
			$this->die_json(
				array(
					'status'  => false,
					'message' => __( 'Wrong parameters. Code not set.' ),
				)
			);
		}
	}

	/**
	 * Returns auth info
	 *
	 * @param string $refresh_tokens Do refresh tokens.
	 *
	 * @return AuthInfo
	 */
	private function get_auth_info( $refresh_tokens ) {
		$result = null;
		$code   = $this->get_param( 'code' );

		$proxy        = ServiceRegister::getService( AuthProxy::CLASS_NAME );
		$redirect_url = $refresh_tokens ? Helper::get_refresh_auth_redirect_url() : Helper::get_auth_redirect_url();

		try {
			$result = $proxy->getAuthInfo( $code, $redirect_url );
		} catch ( BadAuthInfoException $e ) {
			$this->die_json(
				array(
					'status'  => false,
					'message' => $e->getMessage(),
				)
			);
		}

		return $result;
	}

	/**
	 * Refreshes user access tokens
	 *
	 * @param AuthInfo $auth_info User auth info object.
	 *
	 * @throws QueueStorageUnavailableException
	 * @throws InvalidConfigurationException
	 * @throws HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws RefreshTokenExpiredException
	 */
	private function refresh_tokens( $auth_info ) {
		$this->update_user_info_and_credentials( $auth_info );
	}

	/**
	 * @param AuthInfo $auth_info
	 *
	 * @throws QueueStorageUnavailableException
	 * @throws InvalidConfigurationException
	 * @throws HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws RefreshTokenExpiredException
	 */
	/**
	 * @param AuthInfo $auth_info
	 *
	 * @throws QueueStorageUnavailableException
	 * @throws InvalidConfigurationException
	 * @throws HttpCommunicationException
	 * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
	 * @throws RefreshTokenExpiredException
	 */
	private function update_user_info_and_credentials( AuthInfo $auth_info )
	{
		try {
			/** @var Config_Service $config */
			$config        = ServiceRegister::getService( Configuration::CLASS_NAME );
			$api_user_info = $this->get_user_info( $auth_info );
			$db_user_info  = $config->getUserInfo();
			if ( empty( $db_user_info[ 'id' ] ) ) {
				$config->setUserInfo( $api_user_info );
				$config->setAuthInfo( $auth_info );
				/** @var Queue $queue_service */
				$queue_service = ServiceRegister::getService( Queue::CLASS_NAME );
				$queue_service->enqueue( $config->getQueueName(), new Initial_Sync_Task() );

				return;
			}

			if ( $api_user_info[ 'id' ] === $db_user_info[ 'id' ] ) {
				$config->setUserInfo( $api_user_info );
				$config->setAuthInfo( $auth_info );
			}
		} catch (\Exception $exception) {
			return;
		}
	}

	/**
	 * Returns user info as array
	 *
	 * @param AuthInfo $auth_info
	 *
	 * @return array
	 * @throws HttpRequestException
	 * @throws InvalidConfigurationException
	 * @throws HttpCommunicationException
	 * @throws RefreshTokenExpiredException
	 */
	private function get_user_info( AuthInfo $auth_info ) {
		/** @var Proxy $proxy */
		$proxy         = ServiceRegister::getService( Proxy::CLASS_NAME );
		$api_user_info = $proxy->getUserInfo( $auth_info->getAccessToken() );

		if ( empty( $api_user_info ) ) {
			throw new HttpRequestException( 'Failed to fetch user info' );
		}

		return $api_user_info;
	}

	/**
	 * Queues refresh user info task
	 *
	 * @param AuthInfo $auth_info Auth info object.
	 */
	private function queue_refresh_user_task( $auth_info ) {
		/** @var Queue $queue */
		$queue  = ServiceRegister::getService( Queue::CLASS_NAME );
		/** @var Config_Service $config */
		$config = ServiceRegister::getService( Configuration::CLASS_NAME );

		try {
			$config->setAuthInfo( $auth_info );
			$queue->enqueue(
				$config->getQueueName(),
				new RefreshUserInfoTask( $auth_info ),
				'',
				QueueItem::PRIORITY_HIGH
			);
		} catch ( QueueStorageUnavailableException $e ) {
			$this->die_json(
				array(
					'status'  => false,
					'message' => $e->getMessage(),
				)
			);
		}
	}
}
