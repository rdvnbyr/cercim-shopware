<?php

namespace Sendcloud\Shipping\Core\BusinessLogic;

use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Configuration;
use Sendcloud\Shipping\Core\BusinessLogic\Sync\OldQueueItemsCleanupTask;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration as InfrastructureConfiguration;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Queue;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\QueueItem;

/**
 * Class OldQueueItemCleanupTickHandler.
 *
 * @package Sendcloud\Shipping\Core\BusinessLogic
 */
class OldQueueItemCleanupTickHandler
{
    /**
     * Queues OldQueueItemsCleanupTask.
     */
    public function handle()
    {
        /** @var Queue $queueService */
        $queueService = ServiceRegister::getService(Queue::CLASS_NAME);
        $task = $queueService->findLatestByType('OldQueueItemsCleanupTask');

        if ($task && in_array($task->getStatus(), array(QueueItem::QUEUED, QueueItem::IN_PROGRESS), true)) {
            return;
        }

        /** @var Configuration $configService */
        $configService = ServiceRegister::getService(InfrastructureConfiguration::CLASS_NAME);
        $threshold = $configService->getOldTaskCleanupTimeThreshold();

        if ($task && $task->getQueueTimestamp() + $threshold >= time()) {
            return;
        }

        $task = new OldQueueItemsCleanupTask();
        try {
            $queueService->enqueue($configService->getOldTaskCleanupQueueName(), $task);
        } catch (QueueStorageUnavailableException $ex) {
            Logger::logError(
                'Failed to enqueue task ' . $task->getType(),
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                )
            );
        }
    }
}