<?php

namespace Sendcloud\Shipping\Struct;

use Shopware\Core\Framework\Struct\Struct;

/**
 * Class ServicePointInfo
 *
 * @package Sendcloud\Shipping\Struct
 */
class ServicePointInfo extends Struct
{
    /**
     * @var array
     */
    public $servicePointInfo;

    /**
     * ArrayStruct constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->servicePointInfo = $data;
    }
}
