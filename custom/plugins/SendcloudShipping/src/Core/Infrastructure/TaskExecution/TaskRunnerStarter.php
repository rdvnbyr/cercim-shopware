<?php

namespace Sendcloud\Shipping\Core\Infrastructure\TaskExecution;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\Runnable;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerRunException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use Sendcloud\Shipping\Core\Infrastructure\Utility\Events\EventBus;

/**
 * Class TaskRunnerStarter
 * @package Sendcloud\Shipping\Core\Infrastructure\TaskExecution
 */
class TaskRunnerStarter implements Runnable
{
    /**
     * @var string
     */
    private $guid;

    /**
     * @var TaskRunnerStatusStorageInterface
     */
    private $runnerStatusStorage;

    /**
     * @var TaskRunner
     */
    private $taskRunner;

    /**
     * @var TaskRunnerWakeupInterface
     */
    private $taskWakeup;

    /**
     * TaskRunnerStarter constructor.
     *
     * @param string $guid
     */
    public function __construct($guid)
    {
        $this->guid = $guid;
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize(array($this->guid));
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     *
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list($this->guid) = unserialize($serialized);
    }

    /**
     * Retrieves task guid
     *
     * @return string
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * Starts synchronously currently active task runner instance
     *
     * @throws TaskRunnerRunException
     */
    public function run()
    {
        try {
            $this->doRun();
        } catch (TaskRunnerStatusStorageUnavailableException $ex) {
            Logger::logError(
                'Failed to run task runner. Runner status storage unavailable.',
                'Core',
                array('ExceptionMessage' => $ex->getMessage())
            );
            Logger::logDebug(
                'Failed to run task runner. Runner status storage unavailable.',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                )
            );
        } catch (TaskRunnerRunException $ex) {
            Logger::logInfo($ex->getMessage());
            Logger::logDebug($ex->getMessage(), 'Core',  array('ExceptionTrace' => $ex->getTraceAsString()));
        } catch (\Exception $ex) {
            Logger::logError(
                'Failed to run task runner. Unexpected error occurred.',
                'Core',
                array('ExceptionMessage' => $ex->getMessage())
            );
            Logger::logDebug(
                'Failed to run task runner. Unexpected error occurred.',
                'Core',
                array(
                    'ExceptionMessage' => $ex->getMessage(),
                    'ExceptionTrace' => $ex->getTraceAsString()
                )
            );
        }
    }

    /**
     * Runs task execution
     *
     * @throws \Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerRunException
     * @throws \Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException
     */
    private function doRun()
    {
        $runnerStatus = $this->getRunnerStorage()->getStatus();
        if ($this->guid !== $runnerStatus->getGuid()) {
            throw new TaskRunnerRunException('Failed to run task runner. Runner guid is not set as active.');
        }

        if ($runnerStatus->isExpired()) {
            $this->getTaskWakeup()->wakeup();
            throw new TaskRunnerRunException('Failed to run task runner. Runner is expired.');
        }

        $this->getTaskRunner()->setGuid($this->guid);
        $this->getTaskRunner()->run();

        /** @var EventBus $eventBus */
        $eventBus = ServiceRegister::getService(EventBus::CLASS_NAME);
        $eventBus->fire(new TickEvent());
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
     * @return TaskRunner
     */
    private function getTaskRunner()
    {
        if (empty($this->taskRunner)) {
            $this->taskRunner = ServiceRegister::getService(TaskRunner::CLASS_NAME);
        }

        return $this->taskRunner;
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
}
