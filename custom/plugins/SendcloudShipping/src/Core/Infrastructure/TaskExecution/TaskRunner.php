<?php

namespace Sendcloud\Shipping\Core\Infrastructure\TaskExecution;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\QueueItemDeserializationException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class TaskRunner
 * @package Sendcloud\Shipping\Core\Infrastructure\TaskExecution
 */
class TaskRunner
{
    const CLASS_NAME = __CLASS__;

    /** Automatic task runner wakeup delay in seconds */
    const WAKEUP_DELAY = 5;

    /** @var string Runner guid */
    protected $guid;

    /** @var AsyncProcessStarter */
    private $asyncProcessStarter;

    /** @var Queue */
    private $queue;

    /** @var TaskRunnerStatusStorageInterface */
    private $runnerStorage;

    /** @var Configuration */
    private $configurationService;

    /** @var TimeProvider */
    private $timeProvider;

    /** @var TaskRunnerWakeupInterface */
    private $taskWakeup;

    /**
     * Sets task runner guid
     *
     * @param string $guid Runner guid to set
     */
    public function setGuid($guid)
    {
        $this->guid = $guid;
    }

    /**
     * Starts task runner lifecycle
     */
    public function run()
    {
        try {
            $this->logDebug(array('Message' => 'Task runner: lifecycle started.'));

            if ($this->isCurrentRunnerAlive()) {
                $this->failOrRequeueExpiredTasks();
                $this->startOldestQueuedItems();
            }

            $this->wakeup();

            $this->logDebug(array('Message' => 'Task runner: lifecycle ended.'));
        } catch (\Exception $ex) {
            $this->logWarning(array(
                'Message' => 'Fail to run task runner. Unexpected error occurred.',
                'ExceptionMessage' => $ex->getMessage(),
                'ExceptionTrace' => $ex->getTraceAsString()
            ));
        }
    }

    /**
     * Fails or re-queues expired tasks
     *
     * @return void
     * @throws QueueItemDeserializationException
     * @throws QueueStorageUnavailableException
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    private function failOrRequeueExpiredTasks()
    {
        $this->logDebug(array('Message' => 'Task runner: expired tasks cleanup started.'));

        $runningItems = $this->getQueue()->findRunningItems();
        if (!$this->isCurrentRunnerAlive()) {
            return;
        }

        foreach ($runningItems as $runningItem) {
            if ($this->isItemExpired($runningItem) && $this->isCurrentRunnerAlive()) {
                $this->logMessageFor($runningItem, 'Task runner: Expired task detected.');
                $this->getConfigurationService()->setContext($runningItem->getContext());
                if ($runningItem->getProgressBasePoints() > $runningItem->getLastExecutionProgressBasePoints()) {
                    $this->logMessageFor($runningItem, 'Task runner: Task requeue for execution continuation.');
                    $this->getQueue()->requeue($runningItem);
                } else {
                    $runningItem->reconfigureTask();
                    $this->getQueue()->fail(
                        $runningItem,
                        sprintf('Task %s failed due to extended inactivity period.', $this->getItemDescription($runningItem))
                    );
                }
            }
        }
    }

    /**
     * Starts oldest queue item from all queues respecting following list of criteria:
     *      - Queue must be without already running queue items
     *      - For one queue only one (oldest queued) item should be started
     *      - Number of running tasks must NOT be greater than maximal allowed by integration configuration
     *
     * @return void
     * @throws ProcessStarterSaveException
     * @throws TaskRunnerStatusStorageUnavailableException
     * @throws QueueItemDeserializationException
     */
    private function startOldestQueuedItems()
    {
        $this->logDebug(array('Message' => 'Task runner: available task detection started.'));

        // Calculate how many queue items can be started
        $maxRunningTasks = $this->getConfigurationService()->getMaxStartedTasksLimit();
        $alreadyRunningItems = $this->getQueue()->findRunningItems();
        $numberOfAvailableSlotsForTaskRunning = $maxRunningTasks - count($alreadyRunningItems);
        if ($numberOfAvailableSlotsForTaskRunning <= 0) {
            $this->logDebug(array('Message' => 'Task runner: max number of active tasks reached.'));
            return;
        }

        $items = $this->getQueue()->findOldestQueuedItems($numberOfAvailableSlotsForTaskRunning);

        if (!$this->isCurrentRunnerAlive()) {
            return;
        }

        foreach ($items as $item) {
            if (!$this->isCurrentRunnerAlive()) {
                return;
            }

            $this->logMessageFor($item, 'Task runner: Starting async task execution.');
            $this->getAsyncProcessStarter()->start(new QueueItemStarter($item->getId()));
        }
    }

    /**
     * @throws TaskRunnerStatusChangeException
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    private function wakeup()
    {
        $this->logDebug(array('Message' => 'Task runner: starting self deactivation.'));
        $this->getTimeProvider()->sleep($this->getWakeupDelay());

        $this->getRunnerStorage()->setStatus(TaskRunnerStatus::createNullStatus());

        $this->logDebug(array('Message' => 'Task runner: sending task runner wakeup signal.'));
        $this->getTaskWakeup()->wakeup();
    }

    /**
     * @return bool
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    private function isCurrentRunnerAlive()
    {
        $runnerStatus = $this->getRunnerStorage()->getStatus();
        $runnerExpired = $runnerStatus->isExpired();
        $runnerGuidIsCorrect = $this->guid === $runnerStatus->getGuid();

        if ($runnerExpired) {
            $this->logWarning(array('Message' => 'Task runner: Task runner started but it is expired.'));
        }

        if (!$runnerGuidIsCorrect) {
            $this->logWarning(array('Message' => 'Task runner: Task runner started but it is not active anymore.'));
        }

        return !$runnerExpired && $runnerGuidIsCorrect;
    }

    /**
     * @param QueueItem $item
     *
     * @return bool
     * @throws QueueItemDeserializationException
     */
    private function isItemExpired(QueueItem $item)
    {
        $currentTimestamp = $this->getTimeProvider()->getCurrentLocalTime()->getTimestamp();
        $maxTaskInactivityPeriod = $item->getTask()->getMaxInactivityPeriod();

        return ($item->getLastUpdateTimestamp() + $maxTaskInactivityPeriod) < $currentTimestamp;
    }

    /**
     * @param QueueItem $item
     *
     * @return string
     * @throws QueueItemDeserializationException
     */
    private function getItemDescription(QueueItem $item)
    {
        return "{$item->getId()}({$item->getTaskType()})";
    }

    /**
     * @return AsyncProcessStarter
     */
    private function getAsyncProcessStarter()
    {
        if (empty($this->asyncProcessStarter)) {
            $this->asyncProcessStarter = ServiceRegister::getService(AsyncProcessStarter::CLASS_NAME);
        }

        return $this->asyncProcessStarter;
    }

    /**
     * @return Queue
     */
    private function getQueue()
    {
        if (empty($this->queue)) {
            $this->queue = ServiceRegister::getService(Queue::CLASS_NAME);
        }

        return $this->queue;
    }

    /**
     * @return TaskRunnerStatusStorageInterface
     */
    private function getRunnerStorage()
    {
        if (empty($this->runnerStorage)) {
            $this->runnerStorage = ServiceRegister::getService(TaskRunnerStatusStorageInterface::CLASS_NAME);
        }

        return $this->runnerStorage;
    }

    /**
     * @return Configuration
     */
    private function getConfigurationService()
    {
        if (empty($this->configurationService)) {
            $this->configurationService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configurationService;
    }

    /**
     * @return TimeProvider
     */
    private function getTimeProvider()
    {
        if (empty($this->timeProvider)) {
            $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        }

        return $this->timeProvider;
    }

    /**
     * @return TaskRunnerWakeupInterface
     */
    private function getTaskWakeup()
    {
        if (empty($this->taskWakeup)) {
            $this->taskWakeup = ServiceRegister::getService(TaskRunnerWakeupInterface::CLASS_NAME);
        }

        return $this->taskWakeup;
    }

    /**
     * Returns wakeup delay in seconds
     *
     * @return int
     */
    private function getWakeupDelay()
    {
        $configurationValue = $this->getConfigurationService()->getTaskRunnerWakeupDelay();
        return !is_null($configurationValue) ? $configurationValue : self::WAKEUP_DELAY;
    }

    /**
     * Logs message and queue item details
     *
     * @param QueueItem $queueItem
     * @param string $message
     *
     * @throws QueueItemDeserializationException
     */
    private function logMessageFor(QueueItem $queueItem, $message)
    {
        $this->logDebug(array(
            'RunnerGuid' => $this->guid,
            'Message' => $message,
            'TaskId' => $queueItem->getId(),
            'TaskType' => $queueItem->getTaskType(),
            'TaskRetries' => $queueItem->getRetries(),
            'TaskProgressBasePoints' => $queueItem->getProgressBasePoints(),
            'TaskLastExecutionProgressBasePoints' => $queueItem->getLastExecutionProgressBasePoints(),
        ));
    }

    /**
     * Helper methods to encapsulate debug level logging
     *
     * @param array $debugContent
     */
    private function logDebug(array $debugContent)
    {
        $debugContent['RunnerGuid'] = $this->guid;
        Logger::logDebug($debugContent['Message'], 'Core', $debugContent);
    }

    /**
     * Helper methods to encapsulate warning level logging
     *
     * @param array $debugContent
     */
    private function logWarning(array $debugContent)
    {
        $debugContent['RunnerGuid'] = $this->guid;
        Logger::logWarning($debugContent['Message'], 'Core', $debugContent);
    }
}
