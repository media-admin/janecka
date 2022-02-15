<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

class ExchangeAccessTokenTask extends BaseSyncTask
{
    /**
     * Refreshes CleverReach tokens.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     */
    public function execute()
    {
        $this->reportProgress(5);

        $configService = $this->getConfigService();
        $configService->setAccessTokenExpirationTime(10000);

        $result = $this->getProxy()->exchangeToken();

        $configService->setAuthInfo($result);

        $this->reportProgress(100);
    }
}