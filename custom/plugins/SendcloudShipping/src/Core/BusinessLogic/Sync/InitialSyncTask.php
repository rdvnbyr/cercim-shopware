<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Sync;

use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Configuration;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\OrderService;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Queue;

/**
 * Class InitialSyncTask
 * @package Sendcloud\Shipping\Core\BusinessLogic\Sync
 */
class InitialSyncTask extends BaseSyncTask
{
    const INITIAL_PROGRESS_PERCENT = 5;

    /**
     * Runs task logic
     */
    public function execute()
    {
        try {
            /** @var Queue $queue */
            $queue = ServiceRegister::getService(Queue::CLASS_NAME);

            /** @var Configuration $configuration */
            $configuration = ServiceRegister::getService(Configuration::CLASS_NAME);

            $orderIds = $this->getInitialOrderIds();
            $queue->enqueue($configuration->getQueueName(), new OrderSyncTask($orderIds), $configuration->getContext());
        } catch (QueueStorageUnavailableException $e) {
            // If task enqueue fails do nothing but report that initial sync is in progress
        }

        $this->reportProgress(100);
    }

    /**
     * Returns an array of order ids that need to be synced in initial synchronization
     *
     * @return array
     */
    private function getInitialOrderIds()
    {
        /** @var OrderService $orderService */
        $orderService = ServiceRegister::getService(OrderService::CLASS_NAME);

        return $orderService->getAllOrderIds();
    }
}
