<?php

namespace Sendcloud\Shipping\Components\Sendcloud;

interface ShipmentTypes
{
    public const SHIPMENT_TYPES = [
        [
            'value' => 0,
            'label' => 'gift'
        ],
        [
            'value' => 1,
            'label' => 'documents'
        ],
        [
            'value' => 2,
            'label' => 'commercialGoods'
        ],
        [
            'value' => 3,
            'label' => 'commercialSample'
        ],
        [
            'value' => 4,
            'label' => 'returnedGoods'
        ],
    ];
}