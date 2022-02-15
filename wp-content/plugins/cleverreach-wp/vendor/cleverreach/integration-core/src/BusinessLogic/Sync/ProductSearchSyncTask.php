<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\Infrastructure\Logger\Logger;

/**
 * Class ProductSearchSyncTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class ProductSearchSyncTask extends BaseSyncTask
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
        $productSearchParameters = array_merge(
            $this->getConfigService()->getProductSearchParameters(),
            $this->getConfigService()->getAdditionalProductSearchParameters()
        );

        $this->validateProductSearchParameters($productSearchParameters);

        $id = $this->getProxy()->addOrUpdateProductSearch($productSearchParameters);
        $this->getConfigService()->setProductSearchContentId($id);

        $this->reportProgress(100);
    }

    /**
     * Validate if all product search parameters are set.
     *
     * @param array|null $productSearchParameters Associative array of product search parameters.
     *     Expected keys name, url and password.
     */
    private function validateProductSearchParameters($productSearchParameters)
    {
        $errorMessage = '';

        if (empty($productSearchParameters['name'])) {
            $errorMessage .= 'Parameter "name" for product search is not set in Configuration service.';
        }

        if (empty($productSearchParameters['url'])) {
            $errorMessage .= 'Parameter "url" for product search is not set in Configuration service.';
        }

        if (empty($productSearchParameters['password'])) {
            $errorMessage .= 'Parameter "password" for product search is not set in Configuration service.';
        }

        if (empty($productSearchParameters['type'])) {
            $errorMessage .= 'Parameter "type" for product search is not set in Configuration service.';
        }

        if (empty($productSearchParameters['cors'])) {
            $errorMessage .= 'Parameter "cors" for product search is not set in Configuration service.';
        }

        if (empty($productSearchParameters['icon'])) {
            $errorMessage .= 'Parameter "icon" for product search is not set in Configuration service.';
        }

        if (!empty($errorMessage)) {
            Logger::logError($errorMessage);
            throw new \InvalidArgumentException($errorMessage);
        }
    }
}
