<?php

namespace CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution;

use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskEvents\AliveAnnouncedTaskEvent;
use CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;
use CleverReach\WordPress\IntegrationCore\Infrastructure\Utility\Serializer;

/**
 * This type of task should be used when there is a need for synchronous execution of several tasks.
 *
 * @package CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution
 */
abstract class CompositeTask extends Task
{
    /**
     * Associative array where key is task and value is current progress.
     *
     * @var array
     */
    public $taskProgressMap = array();
    /**
     * Progress share points.
     *
     * @var array
     */
    protected $subTasksProgressShare = array();
    /**
     * List of child tasks.
     *
     * @var Task[]
     */
    protected $tasks = array();
    /**
     * Initial progress.
     *
     * @var int
     */
    private $initialProgress;

    /**
     * CompositeTask constructor.
     *
     * @param Task[] $subTasks List of child tasks.
     * @param int $initialProgress Initial progress.
     */
    public function __construct(array $subTasks, $initialProgress = 0)
    {
        $this->initialProgress = $initialProgress;

        $this->taskProgressMap = array(
            'overallTaskProgress' => 0,
        );

        $this->subTasksProgressShare = array();

        foreach ($subTasks as $subTaskKey => $subTaskProgressShare) {
            $this->taskProgressMap[$subTaskKey] = 0;
            $this->subTasksProgressShare[$subTaskKey] = $subTaskProgressShare;
        }
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
        /** @var static $entity */
        $entity = new static(array(), $array['initialProgress']);

        $entity->taskProgressMap = $array['taskProgress'];
        $entity->subTasksProgressShare = $array['subTasksProgressShare'];

        foreach ($array['tasks'] as $task) {
            $entity->tasks[] = Serializer::unserialize($task);
        }

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
                'initialProgress' => $this->initialProgress,
                'taskProgress' => $this->taskProgressMap,
                'subTasksProgressShare' => $this->subTasksProgressShare,
                'tasks' => $this->tasks,
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

        $this->initialProgress = $unserializedStateData['initialProgress'];
        $this->taskProgressMap = $unserializedStateData['taskProgress'];
        $this->subTasksProgressShare = $unserializedStateData['subTasksProgressShare'];
        $this->tasks = $unserializedStateData['tasks'];

        $this->attachSubTasksEvents();
    }

    /**
     * Transforms entity to array.
     *
     * @return array
     */
    public function toArray()
    {
        $tasks = array();

        foreach ($this->tasks as $task) {
            $tasks[] = Serializer::serialize($task);
        }

        return array(
            'initialProgress' => $this->initialProgress,
            'taskProgress' => $this->taskProgressMap,
            'subTasksProgressShare' => $this->subTasksProgressShare,
            'tasks' => $tasks,
        );
    }

    /**
     * Runs task execution.
     *
     * @inheritdoc
     */
    public function execute()
    {
        while ($activeTask = $this->getActiveTask()) {
            $activeTask->execute();
        }
    }

    /**
     * Indicates whether task can be configured or not.
     *
     * @return bool
     *   If task is possible to be configured returns true, otherwise false.
     */
    public function canBeReconfigured()
    {
        $activeTask = $this->getActiveTask();

        return $activeTask !== null ? $activeTask->canBeReconfigured() : false;
    }

    /**
     * Reconfigures the task.
     */
    public function reconfigure()
    {
        $activeTask = $this->getActiveTask();

        if ($activeTask !== null) {
            $activeTask->reconfigure();
        }
    }

    /**
     * Gets progress by each task.
     *
     * @return array
     *   Associative array where key is task and value is current progress.
     */
    public function getProgressByTask()
    {
        return $this->taskProgressMap;
    }

    /**
     * Creates child task of composite (main) task.
     *
     * @param string $taskKey Unique task key.
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task
     *   Instance of created simple task.
     */
    abstract protected function createSubTask($taskKey);

    /**
     * Gets active task (task that is currently running).
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task|null
     *   If null is returned, none of task is running at the moment.
     */
    protected function getActiveTask()
    {
        $task = null;
        foreach ($this->taskProgressMap as $taskKey => $taskProgress) {
            if ($taskKey === 'overallTaskProgress') {
                continue;
            }

            if ($taskProgress < 100) {
                $task = $this->getSubTask($taskKey);

                break;
            }
        }

        return $task;
    }

    /**
     * Gets sub task by the task key. If sub task does not exist, creates it.
     *
     * @param string $taskKey Unique task key.
     *
     * @return \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task
     *   Task that matches provided task key.
     */
    protected function getSubTask($taskKey)
    {
        if (empty($this->tasks[$taskKey])) {
            $this->tasks[$taskKey] = $this->createSubTask($taskKey);
            $this->attachSubTaskEvents($this->tasks[$taskKey]);
        }

        return $this->tasks[$taskKey];
    }

    /**
     * Attaches "report progress" and "report alive" events to all sub tasks.
     */
    protected function attachSubTasksEvents()
    {
        foreach ($this->tasks as $task) {
            $this->attachSubTaskEvents($task);
        }
    }

    /**
     * Attaches "report progress" and "report alive" events to a sub task.
     *
     * @param \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task $task Task object.
     */
    protected function attachSubTaskEvents(Task $task)
    {
        $this->attachReportAliveEvent($task);
        $this->attachReportProgressEvent($task);
    }

    /**
     * Calculates progress of execution.
     *
     * @param float  $subTaskProgress Progress of execution in percentage.
     * @param string $subTaskProgressMapKey Calculated task key.
     */
    public function calculateProgress($subTaskProgress, $subTaskProgressMapKey)
    {
        $this->taskProgressMap[$subTaskProgressMapKey] = $subTaskProgress;
        $overallProgress = 0;

        foreach ($this->subTasksProgressShare as $subTaskKey => $subTaskPercentageShare) {
            $overallProgress += $this->taskProgressMap[$subTaskKey] * $subTaskPercentageShare / 100;
        }

        $this->taskProgressMap['overallTaskProgress'] = $this->initialProgress + $overallProgress;

        if ($this->isProcessCompleted()) {
            $this->taskProgressMap['overallTaskProgress'] = 100;
        }
    }

    /**
     * Checks if all sub tasks are finished.
     *
     * @return bool
     *   True when all sub tasks are finished, otherwise false.
     */
    protected function isProcessCompleted()
    {
        $allTasksSuccessful = true;

        foreach (array_keys($this->subTasksProgressShare) as $subTaskKey) {
            if ($this->taskProgressMap[$subTaskKey] < 100) {
                $allTasksSuccessful = false;
                break;
            }
        }

        return $allTasksSuccessful;
    }

    /**
     * Attaches report alive event to provided task.
     *
     * @param \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task $task Task object.
     */
    private function attachReportAliveEvent(Task $task)
    {
        $self = $this;

        $task->when(
            AliveAnnouncedTaskEvent::CLASS_NAME,
            function () use ($self) {
                $self->reportAlive();
            }
        );
    }

    /**
     * Attaches report progress event to provided task.
     *
     * @param \CleverReach\WordPress\IntegrationCore\Infrastructure\TaskExecution\Task $task Task object.
     */
    private function attachReportProgressEvent(Task $task)
    {
        $self = $this;

        $task->when(
            ProgressedTaskEvent::CLASS_NAME,
            function (ProgressedTaskEvent $event) use ($self, $task) {
                $self->calculateProgress($event->getProgressFormatted(), $task->getType());
                $self->reportProgress($self->taskProgressMap['overallTaskProgress']);
            }
        );
    }
}
