<?php


namespace Sendcloud\Shipping\Core\BusinessLogic\Sync;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\QueueItem;
use Sendcloud\Shipping\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class OldQueueItemsCleanupTask
 * @package Sendcloud\Shipping\Core\BusinessLogic\Sync
 */
class OldQueueItemsCleanupTask extends BaseSyncTask
{
    /**
     * @var TaskQueueStorage
     */
    private $queueStorageService;
    /**
     * @var TimeProvider
     */
    private $timeProvider;

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $this->deleteBy(QueueItem::COMPLETED, $this->getConfigService()->getCompletedTasksRetentionPeriod());
        $this->reportProgress(50);

        $this->deleteBy(QueueItem::FAILED, $this->getConfigService()->getFailedTasksRetentionPeriod());

        $this->reportProgress(100);
    }

    /**
     * @param string $status
     * @param int $retentionPeriod
     */
    protected function deleteBy($status, $retentionPeriod)
    {
        $timeBefore = $this->getTimeProvider()->getCurrentLocalTime();
        $timeBefore->modify("-{$retentionPeriod} second");
        $deleteBatchSize = 1000;
        $deletedCount = $deleteBatchSize;

        while ($deletedCount >= $deleteBatchSize) {
            $deletedCount = $this->getQueueStorageService()->deleteOldItemsBy(
                $timeBefore,
                array( 'status' => $status),
                array(InitialSyncTask::getClassName()),
                $deleteBatchSize
            );
            $this->reportAlive();
            $this->getTimeProvider()->sleep(1);
        }
    }

    /**
     * @return TaskQueueStorage
     */
    protected function getQueueStorageService()
    {
        if (!$this->queueStorageService) {
            /** @var TaskQueueStorage $queueStorage */
            $this->queueStorageService = ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);
        }

        return $this->queueStorageService;
    }

    /**
     * @return TimeProvider
     */
    protected function getTimeProvider()
    {
        if (!$this->timeProvider) {
            /** @var TimeProvider $queueStorage */
            $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        }

        return $this->timeProvider;
    }
}