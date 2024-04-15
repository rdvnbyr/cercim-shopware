<?php

namespace Sendcloud\Shipping\Core\Infrastructure\TaskExecution;

use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\TaskEvents\AliveAnnouncedTaskEvent;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\TaskEvents\ProgressedTaskEvent;

/**
 * Class CompositeTask
 *
 * This type of task should be used when there is a need for synchronous execution of several tasks.
 *
 * @package Sendcloud\Shipping\Core\Infrastructure\TaskExecution
 */
abstract class CompositeTask extends Task
{
    /**
     * @var array
     */
    protected $taskProgressMap = array();
    /**
     * @var array
     */
    protected $subTasksProgressShare = array();
    /**
     * @var Task[]
     */
    protected $tasks = array();
    /**
     * @var int
     */
    private $initialProgress;

    /**
     * CompositeTask constructor.
     *
     * @param array $subTasks
     * @param int $initialProgress
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
     * String representation of object
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize(
            array(
                'initialProgress' => $this->initialProgress,
                'taskProgress' => $this->taskProgressMap,
                'subTasksProgressShare' => $this->subTasksProgressShare,
                'tasks' => $this->tasks,
            )
        );
    }

    /**
     * Constructs the object
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $unserializedStateData = unserialize($serialized);

        $this->initialProgress = $unserializedStateData['initialProgress'];
        $this->taskProgressMap = $unserializedStateData['taskProgress'];
        $this->subTasksProgressShare = $unserializedStateData['subTasksProgressShare'];
        $this->tasks = $unserializedStateData['tasks'];

        $this->attachSubTasksEvents();
    }

    /**
     * Runs task logic
     */
    public function execute()
    {
        while ($activeTask = $this->getActiveTask()) {
            $activeTask->execute();
        }
    }

    /**
     * Determines whether task can be reconfigured.
     *
     * @return bool
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
     */
    public function getProgressByTask()
    {
        return $this->taskProgressMap;
    }

    /**
     * Creates a sub-task
     *
     * @param string $taskKey
     *
     * @return Task
     */
    abstract protected function createSubTask($taskKey);

    /**
     * @return Task|null
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
     * @param string $taskKey
     *
     * @return \Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Task
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
     * @param \Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Task $task
     */
    protected function attachSubTaskEvents(Task $task)
    {
        $this->attachReportAliveEvent($task);
        $this->attachReportProgressEvent($task);
    }

    /**
     * @param float $subTaskProgress
     * @param string $subTaskProgressMapKey
     */
    protected function calculateProgress($subTaskProgress, $subTaskProgressMapKey)
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
     * Registers event listener when task reports its alive status
     *
     * @param Task $task
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
     * Registers event listener when task reports its progress
     *
     * @param Task $task
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
