<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\AbstractTag;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Tag;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Recipients;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\Filter;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Utility\Rule;

/**
 * Class FilterSyncTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class FilterSyncTask extends BaseSyncTask
{
    const INITIAL_PROGRESS_PERCENT = 10;
    /**
     * Instance of recipient service.
     *
     * @var Recipients
     */
    private $recipientsService;
    /**
     * CleverReach integration ID.
     *
     * @var int
     */
    private $integrationId;
    /**
     * Current progress in percentage.
     *
     * @var int
     */
    private $progressPercent = self::INITIAL_PROGRESS_PERCENT;
    /**
     * Current progress step.
     *
     * @var int
     */
    private $progressStep;

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
        $this->integrationId = $this->getConfigService()->getIntegrationId();

        if ($this->integrationId === null) {
            throw new InvalidConfigurationException('Integration ID not set');
        }

        // take both regular and special tags
        /** @var \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection $shopTags */
        $shopTags = $this->getRecipientsService()->getAllTags()
            ->add($this->getRecipientsService()->getAllSpecialTags());

        $this->reportAlive();

        /** @var Filter[] $allCRFilters */
        $allCRFilters = $this->getProxy()->getAllFilters($this->integrationId);

        $totalNumberOfFilters = count($shopTags) + count($allCRFilters);

        if ($totalNumberOfFilters === 0) {
            $this->reportProgress(100);

            return;
        }

        $this->reportProgress($this->progressPercent);

        $this->progressStep = (100 - $this->progressPercent) / $totalNumberOfFilters;

        $this->createNewFilters($allCRFilters, $shopTags);
        $this->reportAlive();
        $this->deleteFilters($allCRFilters, $shopTags);

        $this->reportProgress(100);
    }

    /**
     * Creates new filter on CleverReach if necessary.
     *
     * @param Filter[]|null $allCRFilters List of all filters from CleverReach.
     * @param TagCollection|null $shopTags Collection of all tags in integration.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function createNewFilters($allCRFilters, $shopTags)
    {
        // convert array of filter to array of strings
        $allFilterNames = array();
        foreach ($allCRFilters as $filter) {
            $allFilterNames[] = $filter->getFirstCondition();
        }

        foreach ($shopTags as $tag) {
            if (!in_array((string)$tag, $allFilterNames, false)) {
                $this->createFilter($tag);
            }

            $this->incrementProgress();
        }
    }

    /**
     * Deletes filter on CleverReach if necessary.
     *
     * @param Filter[]|null $allCRFilters List of all filters from CleverReach.
     * @param TagCollection|null $shopTags Collection of all tags in integration.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function deleteFilters($allCRFilters, $shopTags)
    {
        if (empty($allCRFilters)) {
            return;
        }

        foreach ($allCRFilters as $filter) {
            $tagName = $filter->getFirstCondition();
            if (!$shopTags->hasTag($tagName) && $this->isTagOriginSame($tagName)) {
                $this->getProxy()->deleteFilter($filter->getId(), $this->integrationId);
            }

            /* @noinspection DisconnectedForeachInstructionInspection */
            $this->incrementProgress();
        }
    }

    /**
     * Checks if tag origin is the same.
     *
     * @param string $tagName Full tag name.
     *
     * @return bool
     *   Returns true when tag is the same, otherwise false.
     */
    private function isTagOriginSame($tagName)
    {
        return strpos($tagName, $this->getTagPrefix()) === 0 ||
            strpos($tagName, $this->getConfigService()->getIntegrationName()) === 0;
    }

    /**
     * Gets integration tag prefix.
     *
     * @return string
     *   Tag prefix.
     */
    private function getTagPrefix()
    {
        return preg_replace(AbstractTag::TAG_NAME_REGEX, '_', $this->getConfigService()->getIntegrationName());
    }

    /**
     * Creates new filter on CleverReach.
     *
     * @param Tag|null $tag Tag that needs to be created on CleverReach.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    private function createFilter($tag)
    {
        $rule = new Rule('tags', 'contains', (string)$tag);

        $filter = new Filter($tag->getTitle(), $rule);
        $this->getProxy()->createFilter($filter, $this->integrationId);
    }

    /**
     * Increments progress.
     */
    private function incrementProgress()
    {
        $this->progressPercent += $this->progressStep;
        $this->reportProgress($this->progressPercent);
    }

    /**
     * Gets instance of recipients service.
     *
     * @return Recipients
     *   Instance of recipients service.
     */
    private function getRecipientsService()
    {
        if ($this->recipientsService === null) {
            $this->recipientsService = ServiceRegister::getService(Recipients::CLASS_NAME);
        }

        return $this->recipientsService;
    }
}
