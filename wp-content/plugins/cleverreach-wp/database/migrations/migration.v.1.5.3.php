<?php

namespace CleverReach\WordPress\Database\Migrations;

use CleverReach\WordPress\Components\Utility\Update\Update_Schema;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Proxy;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\FormCacheSyncTask;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync\RefreshUserInfoTask;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class Migration_1_5_3
 *
 * @package CleverReach\WordPress\Database\Migrations
 */
class Migration_1_5_3 extends Update_Schema {

	/**
	 * @inheritDoc
	 * @throws QueueStorageUnavailableException
	 */
	public function update() {
		$this->recover_account_id_if_not_set();
		$this->queue_service->enqueue($this->config_service->getQueueName(), new FormCacheSyncTask());
	}

	/**
	 * Set user id if not exits in config table (extracted from access token)
	 */
	private function recover_account_id_if_not_set() {
		$account_id   = $this->config_service->getUserAccountId();
		$access_token = $this->config_service->getAccessToken();

		if ( empty( $account_id ) ) {
			if ( !empty( $access_token ) ) {
				$this->fetch_user_info_from_api( $access_token );
			} else {
				$this->extract_id_from_refresh_user_info_task();
			}
		}
	}

	/**
	 * Extracts user id from user info task
	 */
	private function extract_id_from_refresh_user_info_task() {
		$queue_item = $this->queue_service->findLatestByType( RefreshUserInfoTask::getClassName() );
		if ( $queue_item ) {
			/** @var RefreshUserInfoTask $refresh_user_info_task */
			$refresh_user_info_task = Serializer::unserialize( $queue_item->getSerializedTask() );
			$task_array             = $refresh_user_info_task->toArray();
			$access_token           = $task_array[ 'accessToken' ];
			$decomposed_token       = explode( '.', $access_token );
			$data                   = json_decode( base64_decode( $decomposed_token[ 1 ] ), true );
			if ( ! empty( $data[ 'client_id' ] ) ) {
				$user_info         = $this->config_service->getUserInfo();
				$user_info[ 'id' ] = (string) $data[ 'client_id' ];
				$this->config_service->setUserInfo( $user_info );
			}
		}
	}

	/**
	 * Fetch user info from CleverReach API
	 *
	 * @param string $access_token
	 */
	private function fetch_user_info_from_api($access_token)
	{
		/** @var Proxy $proxy */
		$proxy = ServiceRegister::getService( Proxy::CLASS_NAME );
		try {
			$user_info = $proxy->getUserInfo( $access_token );
			if ( empty( $user_info ) ) {
				throw new HttpRequestException( 'User info is empty!' );
			}

			$this->config_service->setUserInfo( $user_info );
		} catch ( \Exception $exception ) {
			Logger::logError( "An error occurred during fetching user info: {$exception->getMessage()}", 'Integration' );
			$this->extract_id_from_refresh_user_info_task();
		}
	}
}
