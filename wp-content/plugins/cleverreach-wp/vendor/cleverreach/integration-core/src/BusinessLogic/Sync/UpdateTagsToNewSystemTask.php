<?php

namespace CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync;

use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\Tag;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagCollection;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Entity\TagInOldFormat;
use CleverReach\WordPress\IntegrationCore\BusinessLogic\Interfaces\Recipients;
use CleverReach\WordPress\IntegrationCore\Infrastructure\ServiceRegister;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\CompositeTask;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * Class UpdateTagsToNewSystemTask
 *
 * @package CleverReach\WordPress\IntegrationCore\BusinessLogic\Sync
 */
class UpdateTagsToNewSystemTask extends CompositeTask
{
    const INITIAL_SYNC_PROGRESS = 1;
    const NUMBER_OF_SUBTASKS = 3;

    /**
     * Instance of recipient service.
     *
     * @var Recipients $recipientService
     */
    private $recipientService;
    /**
     * List of sub tasks used in this task.
     *
     * @var array $subTasks
     */
    private $subTasks = array();
    /**
     * All prefixed tags.
     *
     * @var array $prefixedShopTags
     */
    private $prefixedShopTags;

    /**
     * UpdateTagsToNewSystemTask constructor.
     *
     * @param array $prefixedShopTags Shop tags for migration.
     */
    public function __construct(array $prefixedShopTags = array())
    {
        $this->prefixedShopTags = $prefixedShopTags;
        $this->setDeletePrefixedFilterTask();
        $this->setFilterSyncTask();
        $this->setRecipientSyncTasks();

        parent::__construct($this->subTasks, self::INITIAL_SYNC_PROGRESS);
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
        $entity = new static($array['prefixedShopTags']);

        $tasks = array();
        foreach ($array['tasks'] as $taskArray) {
            $tasks[] = Serializer::unserialize($taskArray);
        }

        $entity->taskProgressMap = $array['taskProgress'];
        $entity->subTasksProgressShare = $array['subTasksProgressShare'];
        $entity->tasks = $tasks;

        $entity->attachSubTasksEvents();

        return $entity;
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
                'taskProgress' => $this->taskProgressMap,
                'subTasksProgressShare' => $this->subTasksProgressShare,
                'tasks' => $this->tasks,
                'prefixedShopTags' => $this->prefixedShopTags,
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
        $unserializedStateData = Serializer::unserialize($serialized);

        $this->taskProgressMap = $unserializedStateData['taskProgress'];
        $this->subTasksProgressShare = $unserializedStateData['subTasksProgressShare'];
        $this->tasks = $unserializedStateData['tasks'];
        $this->prefixedShopTags = $unserializedStateData['prefixedShopTags'];

        $this->attachSubTasksEvents();
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        $tasksArray = array();
        foreach ($this->tasks as $task) {
            $tasksArray[] = Serializer::serialize($task);
        }

        return array(
            'taskProgress' => $this->taskProgressMap,
            'subTasksProgressShare' => $this->subTasksProgressShare,
            'tasks' => $tasksArray,
            'prefixedShopTags' => $this->prefixedShopTags,
        );
    }

    /**
     * Returns progress by tasks (keys in returned array).
     *
     * @return array
     *   Sync task group as key and progress as value.
     */
    public function getProgressByTask()
    {
        return array(
            'deletePrefixedFilters' => $this->getDeletePrefixedFiltersTaskProgress(),
            'filters' => $this->getFilterSyncTaskProgress(),
            'recipients' => $this->getRecipientSyncTasksProgress(),
        );
    }

    /**
     * Gets count of synchronized recipients.
     *
     * @return int
     *   Number of synchronized recipients.
     */
    public function getSyncedRecipientsCount()
    {
        /** @var RecipientSyncTask $recipientTask */
        $recipientTask = $this->getSubTask($this->getRecipientSyncTaskName());

        return $recipientTask->getNumberOfRecipientsForSync() - count($recipientTask->getRecipientsIdsForSync());
    }

    /**
     * Creates child task of composite (main) task.
     *
     * @param string $taskKey Unique task key.
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task
     *   Instance of created simple task.
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     */
    protected function createSubTask($taskKey)
    {
        switch ($taskKey) {
            case $this->getDeletePrefixedFilterTaskName():
                return $this->makeDeletePrefixedFilterSyncTask();

            case $this->getFilterSyncTaskName():
                return $this->makeFilterSyncTask();

            case $this->getRecipientSyncTaskName():
                return $this->makeRecipientSyncTask();
        }

        throw new \RuntimeException('Unknown task type! ' . $taskKey);
    }

    /**
     * Sets DeletePrefixedFilterSyncTask to the list of sub tasks.
     */
    protected function setDeletePrefixedFilterTask()
    {
        $this->subTasks[$this->getDeletePrefixedFilterTaskName()] = $this->getPercentageOfTheOverallProgress();
    }

    /**
     * Gets overall progress of tasks belonging to delete prefixed filter task.
     *
     * @return float
     *   Overall progress for delete prefixed filter task.
     */
    protected function getDeletePrefixedFiltersTaskProgress()
    {
        return $this->taskProgressMap[$this->getDeletePrefixedFilterTaskName()];
    }

    /**
     * Sets FilterSyncTask to the list of sub tasks.
     */
    protected function setFilterSyncTask()
    {
        $this->subTasks[$this->getFilterSyncTaskName()] = $this->getPercentageOfTheOverallProgress();
    }

    /**
     * Gets overall progress of tasks belonging to filter sync task.
     *
     * @return float
     *   Overall progress for filter task.
     */
    protected function getFilterSyncTaskProgress()
    {
        return $this->taskProgressMap[$this->getFilterSyncTaskName()];
    }

    /**
     * Sets RecipientSyncTask to the list of sub tasks.
     */
    protected function setRecipientSyncTasks()
    {
        $this->subTasks[$this->getRecipientSyncTaskName()] = $this->getPercentageOfTheOverallProgress();
    }

    /**
     * Gets overall progress of tasks belonging to recipients task group.
     *
     * @return float
     *   Overall progress for recipient task.
     */
    protected function getRecipientSyncTasksProgress()
    {
        return $this->taskProgressMap[$this->getRecipientSyncTaskName()];
    }

    /**
     * Gets the key for the group sync task.
     *
     * @return string
     *   Class name.
     */
    protected function getDeletePrefixedFilterTaskName()
    {
        return DeletePrefixedFilterSyncTask::getClassName();
    }

    /**
     * Gets the name of the filter sync task.
     *
     * @return string
     *   Class name.
     */
    protected function getFilterSyncTaskName()
    {
        return FilterSyncTask::getClassName();
    }

    /**
     * Gets the key for the recipient sync task.
     *
     * @return string
     *   Class name.
     */
    protected function getRecipientSyncTaskName()
    {
        return RecipientSyncTask::getClassName();
    }

    /**
     * Creates new instance.
     *
     * @return DeletePrefixedFilterSyncTask
     *   New instance.
     */
    protected function makeDeletePrefixedFilterSyncTask()
    {
        return new DeletePrefixedFilterSyncTask($this->prefixedShopTags);
    }

    /**
     * Creates new instance.
     *
     * @return FilterSyncTask
     *   New instance.
     */
    protected function makeFilterSyncTask()
    {
        return new FilterSyncTask();
    }

    /**
     * Creates new instance.
     *
     * @return RecipientSyncTask
     *   New instance.
     * @throws \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Exceptions\RecipientsGetException
     */
    protected function makeRecipientSyncTask()
    {
        $allRecipientsIds = $this->getRecipientService()->getAllRecipientsIds();
        $tagsForDelete = $this->formatTagsForDelete();
        return new RecipientSyncTask($allRecipientsIds, $tagsForDelete, false);
    }

    /**
     * Formats tag for delete.
     *
     * @return TagCollection
     *   Collection of tags to be deleted.
     */
    private function formatTagsForDelete()
    {
        $tagCollection = new TagCollection();
        if ($this->prefixedShopTags === null) {
            return $tagCollection;
        }

        foreach ($this->prefixedShopTags as $prefixedShopTag) {
            $tagInOldFormat = $this->getTagForDelete($prefixedShopTag);
            $tagCollection->addTag($tagInOldFormat);
        }

        return $tagCollection;
    }

    /**
     * Gets tag collection for delete.
     *
     * Creates instance of Tag object used for migration from old to new
     * tag system and marks it for delete.
     *
     * @param string $prefixedShopTag Tag prefix.
     *
     * @return Tag
     *   Instance of object used ONLY for migration.
     */
    private function getTagForDelete($prefixedShopTag)
    {
        $tag = new TagInOldFormat($prefixedShopTag);
        $tag->markDeleted();

        return $tag;
    }

    /**
     * Gets instance of recipient service.
     *
     * @return Recipients
     *   Instance of recipient service.
     */
    private function getRecipientService()
    {
        if ($this->recipientService === null) {
            $this->recipientService = ServiceRegister::getService(Recipients::CLASS_NAME);
        }

        return $this->recipientService;
    }

    /**
     * Gets overall progress of synchronization execution.
     *
     * @return float|int
     *   Overall progress.
     */
    private function getPercentageOfTheOverallProgress()
    {
        return (100 - self::INITIAL_SYNC_PROGRESS) / self::NUMBER_OF_SUBTASKS;
    }
}
