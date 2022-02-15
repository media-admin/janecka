<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;


/**
 * Class GroupSyncTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class GroupSyncTask extends BaseSyncTask
{
    /**
     * Runs task execution.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        /** @var string $serviceName */
        $serviceName = $this->getConfigService()->getIntegrationListName();

        $this->reportAlive();
        $this->validateServiceName($serviceName);
        $this->reportProgress(50);

        $groupId = $this->getProxy()->getGroupId($serviceName);

        if ($groupId === null) {
            $this->reportProgress(75);
            $newGroupId = $this->getProxy()->createGroup($serviceName);
            $this->getConfigService()->setIntegrationId($newGroupId);
        } else {
            $this->getConfigService()->setIntegrationId($groupId);
        }

        $this->reportProgress(100);
    }

    /**
     * Validates if integration list name parameter is set in integration.
     *
     * @param string $serviceName Integration list name.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     *   When integration list name is not provided in integration.
     */
    private function validateServiceName($serviceName)
    {
        if (empty($serviceName)) {
            throw new \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException(
                'Integration name not set in Configuration Service'
            );
        }
    }
}
