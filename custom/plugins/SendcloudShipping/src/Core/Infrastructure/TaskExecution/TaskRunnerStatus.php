<?php

namespace Sendcloud\Shipping\Core\Infrastructure\TaskExecution;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class TaskRunnerStatus
 * @package Sendcloud\Shipping\Core\Infrastructure\TaskExecution
 */
class TaskRunnerStatus
{
    /** Maximal time allowed for runner instance to stay in alive (running) status in seconds */
    const MAX_ALIVE_TIME = 60;

    /** @var string */
    private $guid;

    /** @var int|null */
    private $aliveSinceTimestamp;

    /** @var TimeProvider */
    private $timeProvider;

    /** @var Configuration */
    private $configService;

    /**
     * TaskRunnerStatus constructor.
     *
     * @param string $guid Runner instance identifier
     * @param int $aliveSinceTimestamp Timestamp of last alive moment
     */
    public function __construct($guid, $aliveSinceTimestamp)
    {
        $this->guid = $guid;
        $this->aliveSinceTimestamp = $aliveSinceTimestamp;
        $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);
        $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
    }

    /**
     * Creates empty status object
     *
     * @return TaskRunnerStatus
     */
    public static function createNullStatus()
    {
        return new self('', null);
    }

    /**
     * Gets runner instance identifier
     *
     * @return string
     */
    public function getGuid()
    {
        return $this->guid;
    }

    /**
     * Gets timestamp since runner is in alive status or null if runner was never alive
     *
     * @return int|null
     */
    public function getAliveSinceTimestamp()
    {
        return $this->aliveSinceTimestamp;
    }

    /**
     * Checks if task is expired
     *
     * @return bool
     */
    public function isExpired()
    {
        $currentTimestamp = $this->timeProvider->getCurrentLocalTime()->getTimestamp();

        return !empty($this->aliveSinceTimestamp) &&
            ($this->aliveSinceTimestamp + $this->getMaxAliveTimestamp() < $currentTimestamp);
    }

    /**
     * Retrieves max alive timestamp
     *
     * @return int
     */
    private function getMaxAliveTimestamp()
    {
        $configurationValue = $this->configService->getTaskRunnerMaxAliveTime();

        return !is_null($configurationValue) ? $configurationValue : self::MAX_ALIVE_TIME;
    }
}
