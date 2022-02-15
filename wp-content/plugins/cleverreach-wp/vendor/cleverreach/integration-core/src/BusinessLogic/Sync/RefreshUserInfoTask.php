<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\AuthInfo;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class RefreshUserInfoTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class RefreshUserInfoTask extends BaseSyncTask
{
    /**
     * Authentication info.
     *
     * @var AuthInfo
     */
    private $authInfo;

    /**
     * RefreshUserInfoTask constructor.
     *
     * @param AuthInfo $authInfo Authentication data.
     */
    public function __construct(AuthInfo $authInfo)
    {
        $this->authInfo = $authInfo;
    }

    /**
     * Transforms array into entity.
     *
     * @param array $array
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\Interfaces\Required\Serializable
     */
    public static function fromArray($array)
    {
        return new static(new AuthInfo(
            $array['accessToken'],
            $array['accessTokenDuration'],
            $array['refreshToken']
        ));
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize($this->authInfo);
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        $this->authInfo = Serializer::unserialize($serialized);
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        return array(
          'accessToken' => $this->authInfo->getAccessToken(),
          'accessTokenDuration' => $this->authInfo->getAccessTokenDuration(),
          'refreshToken' => $this->authInfo->getRefreshToken(),
        );
    }

    /**
     * Runs task execution.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        $this->reportProgress(5);

        $configService = $this->getConfigService();
        $userInfo = $this->getProxy()->getUserInfo($this->authInfo->getAccessToken());
        if (!empty($userInfo)) {
            $configService->setAuthInfo($this->authInfo);
            $configService->setUserInfo($userInfo);
        }

        $this->reportProgress(100);
    }
}
