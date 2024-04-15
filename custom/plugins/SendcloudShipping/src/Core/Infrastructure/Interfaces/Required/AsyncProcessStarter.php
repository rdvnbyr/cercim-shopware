<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\Runnable;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException;

/**
 * Interface AsyncProcessStarter
 * @package Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required
 */
interface AsyncProcessStarter
{
    const CLASS_NAME = __CLASS__;

    /**
     * Starts given runner asynchronously (in new process/web request or similar)
     *
     * @param Runnable $runner Runner that should be started async
     *
     * @throws ProcessStarterSaveException
     */
    public function start(Runnable $runner);
}
