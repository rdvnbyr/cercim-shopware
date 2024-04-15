<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Interfaces;

use Sendcloud\Shipping\Core\Infrastructure\Logger\LogData;

/**
 * Interface LoggerAdapter
 * @package Sendcloud\Shipping\Core\Infrastructure\Interfaces
 */
interface LoggerAdapter
{

    /**
     * Log message in system
     *
     * @param LogData $data
     */
    public function logMessage(LogData $data);

}
