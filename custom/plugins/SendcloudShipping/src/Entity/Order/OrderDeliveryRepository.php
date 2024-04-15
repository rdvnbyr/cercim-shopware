<?php

namespace Sendcloud\Shipping\Entity\Order;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * Class OrderDeliveryRepository
 *
 * @package Sendcloud\Shipping\Entity\Order
 */
class OrderDeliveryRepository
{
    /**
     * @var EntityRepository
     */
    private $baseRepository;

    /**
     * OrderDeliveryRepository constructor.
     *
     * @param EntityRepository $baseRepository
     */
    public function __construct(EntityRepository $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Set tracking number
     *
     * @param string $id
     * @param string $trackingNumber
     * @param Context $context
     */
    public function updateTrackingNumber(string $id, string $trackingNumber, Context $context): void
    {
        $data = [
            'id' => $id,
            'trackingCodes' => [$trackingNumber]
        ];

        $this->baseRepository->update([$data], $context);
    }

    /**
     * Updates delivery state
     * @param string $id
     * @param string $stateId
     * @param Context $context
     */
    public function updateDeliveryStatus(string $id, string $stateId, Context $context): void
    {
        $data = [
            'id' => $id,
            'stateId' => $stateId
        ];

        $this->baseRepository->update([$data], $context);
    }
}
