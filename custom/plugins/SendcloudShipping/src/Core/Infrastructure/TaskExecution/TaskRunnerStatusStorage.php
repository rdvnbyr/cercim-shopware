<?php

namespace Sendcloud\Shipping\Core\Infrastructure\TaskExecution;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;

/**
 * Class TaskRunnerStatusStorage
 * @package Sendcloud\Shipping\Core\Infrastructure\TaskExecution
 */
class TaskRunnerStatusStorage implements TaskRunnerStatusStorageInterface
{

    /**
     * @var Configuration
     */
    private $configService;

    /**
     * Returns task runner status
     *
     * @return TaskRunnerStatus
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    public function getStatus()
    {
        $result = $this->getConfigService()->getTaskRunnerStatus();
        if (empty($result)) {
            throw new TaskRunnerStatusStorageUnavailableException('Task runner status storage is not available');
        }

        $taskRunnerStatus = new TaskRunnerStatus($result['guid'], $result['timestamp']);

        return $taskRunnerStatus;
    }

    /**
     * Sets task runner status
     *
     * @param TaskRunnerStatus $status
     *
     * @throws TaskRunnerStatusChangeException
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    public function setStatus(TaskRunnerStatus $status)
    {
        $this->checkTaskRunnerStatusChangeAvailability($status);
        $this->getConfigService()->setTaskRunnerStatus($status->getGuid(), $status->getAliveSinceTimestamp());
    }

    /**
     * Checks if task runner can change availability status
     *
     * @param TaskRunnerStatus $status
     *
     * @throws TaskRunnerStatusChangeException
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    private function checkTaskRunnerStatusChangeAvailability(TaskRunnerStatus $status)
    {
        $currentGuid = $this->getStatus()->getGuid();
        $guidForUpdate = $status->getGuid();

        if (!empty($currentGuid) && !empty($guidForUpdate) && $currentGuid !== $guidForUpdate) {
            throw new TaskRunnerStatusChangeException(
                'Task runner with guid: ' . $guidForUpdate . ' can not change the status.'
            );
        }
    }

    /**
     * @return Configuration
     */
    private function getConfigService()
    {
        if (empty($this->configService)) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
