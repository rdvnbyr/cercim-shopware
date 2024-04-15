<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Utility;

/**
 * Class TimeProvider
 * @package Sendcloud\Shipping\Core\Infrastructure\Utility
 */
class TimeProvider
{
    const CLASS_NAME = __CLASS__;

    /**
     * Gets current time in default server timezone
     *
     * @return \DateTime Current time
     */
    public function getCurrentLocalTime()
    {
        return new \DateTime();
    }

    /**
     * Returns current timestamp in milliseconds
     *
     * @return int
     */
    public function getMillisecondsTimestamp()
    {
        return (int) round(microtime(true) * 1000);
    }

    /**
     * Delays execution for sleep time seconds
     *
     * @param int $sleepTime Sleep time in seconds
     */
    public function sleep($sleepTime)
    {
        sleep($sleepTime);
    }
}
