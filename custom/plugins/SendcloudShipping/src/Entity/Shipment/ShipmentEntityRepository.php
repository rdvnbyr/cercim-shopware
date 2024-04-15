<?php

namespace Sendcloud\Shipping\Entity\Shipment;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class ShipmentEntityRepository
 *
 * @package Sendcloud\Shipping\Entity\Shipment
 */
class ShipmentEntityRepository
{
    /**
     * @var EntityRepository
     */
    private $baseRepository;

    /**
     * ShipmentEntityRepository constructor.
     *
     * @param EntityRepository $baseRepository
     */
    public function __construct(EntityRepository $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Updates service point information
     *
     * @param string $orderNumber
     * @param array $servicePointInfo
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function updateServicePoint(string $orderNumber, array $servicePointInfo): void
    {
        $payload = [
            'orderNumber' => $orderNumber,
            'servicePointId' => (string)$servicePointInfo['id'],
            'servicePointInfo' => json_encode($servicePointInfo),
        ];

        $this->update($orderNumber, $payload);
    }

    /**
     * Update shipment
     *
     * @param string $orderNumber
     * @param string|null $status
     * @param string|null $servicePointId
     * @param string|null $trackingNumber
     * @param string|null $trackingUrl
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function updateShipment(
        string $orderNumber,
        ?string $status,
        ?string $servicePointId,
        ?string $trackingNumber,
        ?string $trackingUrl
    ): void {

        $payload = [
            'orderNumber' => $orderNumber,
            'sendcloudStatus' => $status,
            'servicePointId' => $servicePointId,
            'trackingNumber' => $trackingNumber,
            'trackingUrl' => $trackingUrl,
        ];

        $this->update($orderNumber, $payload);
    }

    /**
     * Updates shipment with given order number
     *
     * @param string $orderNumber
     * @param array $payload
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function update(string $orderNumber, array $payload): void
    {
        $existingShipment = $this->getShipmentByOrderNumber($orderNumber);
        $context = Context::createDefaultContext();
        if ($existingShipment) {
            $payload['id'] = $existingShipment->getId();
            $this->baseRepository->update([$payload], $context);
        } else {
            $this->baseRepository->create([$payload], $context);
        }
    }

    /**
     * Return shipment entity by order number
     *
     * @param string $orderNumber
     *
     * @return ShipmentEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getShipmentByOrderNumber(string $orderNumber): ?ShipmentEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));

        return $this->baseRepository->search($criteria, Context::createDefaultContext())->first();
    }
}
