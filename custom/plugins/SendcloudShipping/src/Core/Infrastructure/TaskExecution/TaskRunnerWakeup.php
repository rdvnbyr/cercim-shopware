<?php

namespace Sendcloud\Shipping\Core\Infrastructure\TaskExecution;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusChangeException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\Utility\GuidProvider;
use Sendcloud\Shipping\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class TaskRunnerWakeup
 * @package Sendcloud\Shipping\Core\Infrastructure\TaskExecution
 */
class TaskRunnerWakeup implements TaskRunnerWakeupInterface
{
    /** @var AsyncProcessStarter */
    private $asyncProcessStarter;

    /** @var TaskRunnerStatusStorage */
    private $runnerStatusStorage;

    /** @var TimeProvider */
    private $timeProvider;

    /** @var GuidProvider */
    private $guidProvider;

    /**
     * Wakes up TaskRunner instance asynchronously if active instance is not already running
     */
    public function wakeup()
    {
        try {
            $this->doWakeup();
        } catch (TaskRunnerStatusChangeException $ex) {
            Logger::logError(
                'Fail to wakeup task runner. Runner status storage failed to set new active state.',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                )
            );
        } catch (TaskRunnerStatusStorageUnavailableException $ex) {
            Logger::logError(
                'Fail to wakeup task runner. Runner status storage unavailable.',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                )
            );
        } catch (\Exception $ex) {
            Logger::logError(
                'Fail to wakeup task runner. Unexpected error occurred.',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString(),
                )
            );
        }
    }

    /**
     * Executes wakeup of queued task
     *
     * @return void
     * @throws Exceptions\ProcessStarterSaveException
     * @throws TaskRunnerStatusChangeException
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    private function doWakeup()
    {
        $runnerStatus = $this->getRunnerStorage()->getStatus();
        $currentGuid = $runnerStatus->getGuid();
        if (!empty($currentGuid) && !$runnerStatus->isExpired()) {
            return;
        }

        if ($runnerStatus->isExpired()) {
            $this->runnerStatusStorage->setStatus(TaskRunnerStatus::createNullStatus());
            Logger::logDebug('Expired task runner detected, wakeup component will start new instance.');
        }

        $guid = $this->getGuidProvider()->generateGuid();

        $this->runnerStatusStorage->setStatus(
            new TaskRunnerStatus(
                $guid,
                $this->getTimeProvider()->getCurrentLocalTime()->getTimestamp()
            )
        );

        $this->getAsyncProcessStarter()->start(new TaskRunnerStarter($guid));
    }

    /**
     * @return TaskRunnerStatusStorageInterface
     */
    private function getRunnerStorage()
    {
        if (empty($this->runnerStatusStorage)) {
            $this->runnerStatusStorage = ServiceRegister::getService(TaskRunnerStatusStorageInterface::CLASS_NAME);
        }

        return $this->runnerStatusStorage;
    }

    /**
     * @return GuidProvider
     */
    private function getGuidProvider()
    {
        if (empty($this->guidProvider)) {
            $this->guidProvider = ServiceRegister::getService(GuidProvider::CLASS_NAME);
        }

        return $this->guidProvider;
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
     * @return AsyncProcessStarter
     */
    private function getAsyncProcessStarter()
    {
        if (empty($this->asyncProcessStarter)) {
            $this->asyncProcessStarter = ServiceRegister::getService(AsyncProcessStarter::CLASS_NAME);
        }

        return $this->asyncProcessStarter;
    }
}
