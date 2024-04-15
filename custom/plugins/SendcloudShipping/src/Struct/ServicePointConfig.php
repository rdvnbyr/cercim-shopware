<?php

namespace Sendcloud\Shipping\Struct;

use Shopware\Core\Framework\Struct\Struct;

/**
 * Class ServicePointConfig
 *
 * @package Sendcloud\Shipping\Struct
 */
class ServicePointConfig extends Struct
{
    /**
     * @var array
     */
    public $servicePointConfig;

    /**
     * ServicePointConfig constructor.
     *
     * @param array $servicePointConfig
     */
    public function __construct(array $servicePointConfig)
    {
        $this->servicePointConfig = $servicePointConfig;
    }
}
