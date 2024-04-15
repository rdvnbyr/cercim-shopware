<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed;

/**
 * Interface Runnable
 * @package Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed
 */
interface Runnable extends \Serializable
{
    /**
     * Starts runnable run logic
     */
    public function run();
}
