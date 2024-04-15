<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required;

use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;

/**
 * Interface Configuration
 * @package Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required
 */
interface Configuration
{

    const CLASS_NAME = __CLASS__;

    /**
     * Sets task execution context.
     *
     * When integration supports multiple accounts (middleware integration) proper context must be set based on
     * middleware account that is using core library functionality. This context should then be used by business
     * services to fetch account specific data.Core will set context provided upon task enqueueing before task
     * execution.
     *
     * @param string $context Context to set
     */
    public function setContext($context);

    /**
     * Gets task execution context
     *
     * @return string Context in which task is being executed. If no context is provided empty string is returned
     *     (global context)
     */
    public function getContext();

    /**
     * Gets integration queue name.
     *
     * @return string
     */
    public function getQueueName();

    /**
     * Saves min log level in integration database
     *
     * @param int $minLogLevel
     */
    public function saveMinLogLevel($minLogLevel);

    /**
     * Retrieves min log level from integration database
     *
     * @return int
     */
    public function getMinLogLevel();

    /**
     * Save user information in integration database
     *
     * @param array $userInfo
     */
    public function setUserInfo($userInfo);

    /**
     * Retrieves integration name
     *
     * @return string
     */
    public function getIntegrationName();

    /**
     * Returns default batch size
     *
     * @return int
     */
    public function getDefaultBatchSize();

    /**
     * Sets synchronization batch size.
     *
     * @param int $batchSize
     */
    public function setDefaultBatchSize($batchSize);

    /**
     * Resets authorization credentials to null
     */
    public function resetAuthorizationCredentials();

    /**
     * Set default logger status (enabled/disabled)
     *
     * @param bool $status
     */
    public function setDefaultLoggerEnabled($status);

    /**
     * Return whether default logger is enabled or not
     *
     * @return bool
     */
    public function isDefaultLoggerEnabled();

    /**
     * Gets the number of maximum allowed started task at the point in time. This number will determine how many tasks
     * can be in "in_progress" status at the same time
     *
     * @return int
     */
    public function getMaxStartedTasksLimit();

    /**
     * Automatic task runner wakeup delay in seconds. Task runner will sleep at the end of its lifecycle for this value
     * seconds before it sends wakeup signal for a new lifecycle. Return null to use default system value (10)
     *
     * @return int|null
     */
    public function getTaskRunnerWakeupDelay();

    /**
     * Gets maximal time in seconds allowed for runner instance to stay in alive (running) status. After this period
     * system will automatically start new runner instance and shutdown old one. Return null to use default system
     * value (60)
     *
     * @return int|null
     */
    public function getTaskRunnerMaxAliveTime();

    /**
     * Gets maximum number of failed task execution retries. System will retry task execution in case of error until
     * this number is reached. Return null to use default system value (5)
     *
     * @return int|null
     */
    public function getMaxTaskExecutionRetries();

    /**
     * Gets max inactivity period for a task in seconds. After inactivity period is passed, system will fail such tasks
     * as expired. Return null to use default system value (30)
     *
     * @return int|null
     */
    public function getMaxTaskInactivityPeriod();

    /**
     * @return array
     */
    public function getTaskRunnerStatus();

    /**
     * Sets task runner status information as JSON encoded string.
     *
     * @param string $guid
     * @param int $timestamp
     *
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    public function setTaskRunnerStatus($guid, $timestamp);

    /**
     * Retrieves Sendcloud panel url
     *
     * @return string
     */
    public function getSendCloudPanelUrl();

    /**
     * Retrieves Sendcloud base API url
     *
     * @return mixed
     */
    public function getBaseApiUrl();

    /**
     * Retrieves Sendcloud connect url
     *
     * @return mixed
     */
    public function getConnectUrl();
}
