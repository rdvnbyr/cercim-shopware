<?php

namespace Sendcloud\Shipping\Interfaces;

interface EntityQueueNameAware
{
    /**
     * Generates entity specific queue name.
     *
     * @param string $type
     * @param string $id
     *
     * @return string
     */
    public function getEntityQueueName($type, $id);
}