<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Utility;

/**
 * Class GuidProvider
 * @package Sendcloud\Shipping\Core\Infrastructure\Utility
 */
class GuidProvider
{
    const CLASS_NAME = __CLASS__;

    /**
     * @return string
     */
    public function generateGuid()
    {
        return uniqid(getmypid() . '_');
    }
}
