<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\DTO\RecipientDTO;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Recipient;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\SpecialTag;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Recipients;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\RecipientsGetException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpBatchSizeTooBigException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpUnhandledException;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class RecipientSyncTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class RecipientSyncTask extends BaseSyncTask
{
    const INITIAL_PROGRESS_PERCENT = 5;
    /**
     * Instance of recipients service.
     *
     * @var Recipients
     */
    private $recipientsSyncService;
    /**
     * Number of records processed in one batch.
     *
     * @var int
     */
    private $batchSize;
    /**
     * All recipient IDs that should be synchronized.
     *
     * @var array
     */
    private $allRecipientsIdsForSync;
    /**
     * Collection of tags that should be deleted from CleverReach.
     *
     * @var \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection
     */
    private $tagsToDelete;
    /**
     * Flag that indicates whether orders should be synchronized or not.
     *
     * @var bool
     */
    private $includeOrders;
    /**
     * Total number of recipients that needs to be synchronized.
     *
     * @var int
     */
    private $numberOfRecipientsForSync;
    /**
     * Current progress of synchronization.
     *
     * @var float
     */
    private $currentSyncProgress;

    /**
     * RecipientSyncTask constructor.
     *
     * @param array $recipientIds Array of recipient IDs.
     * @param TagCollection|null $tagsToDelete Collection of tags that should be deleted.
     * @param bool $includeOrders Flag that indicates whether orders should be synchronized.
     */
    public function __construct(array $recipientIds, $tagsToDelete = null, $includeOrders = false)
    {
        $this->batchSize = $this->getConfigService()->getRecipientsSynchronizationBatchSize();
        $this->allRecipientsIdsForSync = $recipientIds;
        $this->numberOfRecipientsForSync = count($recipientIds);
        $this->tagsToDelete = $tagsToDelete ?: new TagCollection();
        $this->includeOrders = $includeOrders;
        $this->currentSyncProgress = self::INITIAL_PROGRESS_PERCENT;
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
        $tagsToDelete = new TagCollection();

        foreach ($array['tagsToDelete'] as $tag) {
            if (!empty($tag)) {
                $tagsToDelete->addTag(Serializer::unserialize($tag));
            }
        }

        $entity = new self(
            $array['allRecipientsIdsForSync'],
            $tagsToDelete,
            $array['includeOrders']
        );

        $entity->batchSize = $array['batchSize'];
        $entity->numberOfRecipientsForSync = $array['numberOfRecipientsForSync'];
        $entity->currentSyncProgress = $array['currentSyncProgress'];

        return $entity;
    }

    /**
     * Gets count of recipients for synchronization.
     *
     * @return int
     *   Count of recipients for sync
     */
    public function getNumberOfRecipientsForSync()
    {
        return $this->numberOfRecipientsForSync;
    }

    /**
     * Gets recipient IDs for synchronization.
     *
     * @return array
     *   List of recipient IDs.
     */
    public function getRecipientsIdsForSync()
    {
        return $this->allRecipientsIdsForSync;
    }

    /**
     * Get current progress of synchronization.
     *
     * @return float|int
     *   Current synchronization progress.
     */
    public function getCurrentSyncProgress()
    {
        return $this->currentSyncProgress;
    }

    /**
     * Get number of records processed in one batch.
     *
     * @return int
     *   Number of records processed in one batch.
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * Should include order in synchronization.
     *
     * @return bool
     *   Flag that indicates whether orders should be synchronized or not.
     */
    public function getIncludeOrders()
    {
        return $this->includeOrders;
    }

    /**
     * Get collection of tags that needs to be deleted on CleverReach.
     *
     * @return TagCollection
     *   Tag collection.
     */
    public function getTagsToDelete()
    {
        return clone $this->tagsToDelete;
    }

    /**
     * String representation of object
     *
     * @inheritdoc
     */
    public function serialize()
    {
        return Serializer::serialize(
            array(
                $this->batchSize,
                $this->allRecipientsIdsForSync,
                $this->numberOfRecipientsForSync,
                $this->tagsToDelete,
                $this->includeOrders,
                $this->currentSyncProgress,
            )
        );
    }

    /**
     * Constructs the object.
     *
     * @inheritdoc
     */
    public function unserialize($serialized)
    {
        list(
            $this->batchSize,
            $this->allRecipientsIdsForSync,
            $this->numberOfRecipientsForSync,
            $this->tagsToDelete,
            $this->includeOrders,
            $this->currentSyncProgress,
        ) = Serializer::unserialize($serialized);
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        $tagsToDelete = array();

        foreach ($this->tagsToDelete as $tag) {
            $tagsToDelete[] = Serializer::serialize($tag);
        }

        return array(
            'batchSize' => $this->batchSize,
            'allRecipientsIdsForSync' => $this->allRecipientsIdsForSync,
            'numberOfRecipientsForSync' => $this->numberOfRecipientsForSync,
            'tagsToDelete' => $tagsToDelete,
            'includeOrders' => $this->includeOrders,
            'currentSyncProgress' => $this->currentSyncProgress,
        );
    }

    /**
     * Runs task execution.
     *
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Exceptions\InvalidConfigurationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpRequestException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\HttpUnhandledException
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Exceptions\RefreshTokenExpiredException
     */
    public function execute()
    {
        $recipientSyncService = $this->getRecipientSyncService();
        /** @var \CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection $allTags */
        $allTags = $recipientSyncService->getAllTags();
        // add all supported special tags to handle the case
        // when special tag is removed from recipient
        $allTags->add(SpecialTag::all());

        $this->reportProgress($this->currentSyncProgress);
        $this->reportProgressWhenNoRecipientIds();

        while (count($this->allRecipientsIdsForSync) > 0) {
            /** @var array $recipientsWithTagsPerBatch */
            $batchIds = $this->getBatchRecipientsIds();
            $recipientsWithTagsPerBatch = $this->getBatchRecipientsWithTagsFromSourceSystem($batchIds);
            $this->reportAlive();
            $recipientDTOs = $this->makeRecipients($recipientsWithTagsPerBatch, $allTags);
            try {
                // it can happen that task is enqueued with recipient ids but at the execution time
                // this is not valid anymore and recipient service returns empty array. In this case, skip batch.
                if (!empty($recipientDTOs)) {
                    $this->getProxy()->recipientsMassUpdate($recipientDTOs);
                }

                // inform service that batch sync is finished
                $this->getRecipientSyncService()->recipientSyncCompleted($batchIds);

                // if mass update is successful recipients in batch should be removed
                // from the recipients for sending. State of task is updated.
                $this->removeFinishedBatchRecipientsFromRecipientsForSync();

                // if upload is successful progress should be reported for that batch.
                $this->reportProgressForBatch();
            } catch (HttpBatchSizeTooBigException $e) {
                // if HttpBatchSizeTooBigException happens process
                // should be continued with smaller calculated batch.
                $this->reconfigure();
            }
        }

        $this->reportProgress(100);
    }

    /**
     * Reports 100% progress if no recipients Ids (task is finished).
     */
    private function reportProgressWhenNoRecipientIds()
    {
        if (count($this->allRecipientsIdsForSync) === 0) {
            $this->currentSyncProgress = 100;
            $this->reportProgress($this->currentSyncProgress);
        }
    }

    /**
     * Converts recipients retrieved from integration to DTO.
     *
     * @param Recipient[] $recipientsWithTags List of recipients retrieved from integration.
     * @param TagCollection|null $allTags All tags, both integration and special tags.
     *
     * @return RecipientDTO[]
     *   List of @see \CleverReach\WordPress\IntegrationCore\BusinessLogic\DTO\RecipientDTO
     */
    private function makeRecipients(array $recipientsWithTags, $allTags)
    {
        $recipientDTOs = array();

        /** @var Recipient $recipient */
        foreach ($recipientsWithTags as &$recipient) {
            $recipient->getTags()->remove($this->tagsToDelete);
            $recipientDTOs[] = new RecipientDTO(
                $recipient,
                $allTags->diff($recipient->getTags())->merge($this->tagsToDelete),
                $this->includeOrders,
                // Send activated time always
                true,
                // Never send deactivated timestamp, integrations should deactivate
                // recipients only by setting activated to 0. Deactivated timestamp
                // should be left for recipients deactivation on CleverReach
                // system. Once recipient is deactivated with deactivated timestamp
                // integrations can't reactivate them!
                false
            );
        }

        return $recipientDTOs;
    }

    /**
     * Gets recipients with tags for provided batch.
     *
     * @param array|null $batchIDs Recipient IDs for current batch
     *
     * @return Recipient[]|null
     *   List of recipient from source system.
     *
     * @throws RecipientsGetException
     */
    private function getBatchRecipientsWithTagsFromSourceSystem($batchIDs)
    {
        return $this->getRecipientSyncService()->getRecipientsWithTags($batchIDs, $this->includeOrders);
    }

    /**
     * Gets next recipient (IDs) batch from all not processed recipient IDs.
     *
     * @return array
     *   List of recipient IDs.
     */
    private function getBatchRecipientsIds()
    {
        return array_slice($this->allRecipientsIdsForSync, 0, $this->batchSize);
    }

    /**
     * Removes synchronized ids form array
     */
    private function removeFinishedBatchRecipientsFromRecipientsForSync()
    {
        $this->allRecipientsIdsForSync = array_slice($this->allRecipientsIdsForSync, $this->batchSize);
    }

    /**
     * Indicates whether RecipientSyncTask can be reconfigured.
     *
     * @return bool
     *   If batch size greater than zero returns true, otherwise false.
     */
    public function canBeReconfigured()
    {
        return $this->batchSize > 1;
    }

    /**
     * Reduces batch size.
     *
     * @throws HttpUnhandledException
     */
    public function reconfigure()
    {
        if ($this->batchSize >= 100) {
            $this->batchSize -= 50;
        } else if ($this->batchSize > 10 && $this->batchSize < 100) {
            $this->batchSize -= 10;
        } else if ($this->batchSize > 1 && $this->batchSize <= 10) {
            --$this->batchSize;
        } else {
            throw new HttpUnhandledException('Batch size can not be smaller than 1');
        }

        $this->getConfigService()->setRecipientsSynchronizationBatchSize($this->batchSize);
    }

    /**
     * Reports progress for one batch.
     */
    private function reportProgressForBatch()
    {
        $numberSynchronizedRecipients = $this->numberOfRecipientsForSync - count($this->allRecipientsIdsForSync);

        $progressStep = $numberSynchronizedRecipients *
            (100 - self::INITIAL_PROGRESS_PERCENT) / $this->numberOfRecipientsForSync;

        $this->currentSyncProgress = self::INITIAL_PROGRESS_PERCENT + $progressStep;

        $this->reportProgress($this->currentSyncProgress);
    }

    /**
     * Get instance of recipient service.
     *
     * @return Recipients
     *   Instance of recipient service.
     */
    private function getRecipientSyncService()
    {
        if ($this->recipientsSyncService === null) {
            $this->recipientsSyncService = ServiceRegister::getService(Recipients::CLASS_NAME);
        }

        return $this->recipientsSyncService;
    }
}
