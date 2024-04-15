<?php

namespace Sendcloud\Shipping\Entity\Shipment;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Class ShipmentEntityCollection
 *
 * @package Sendcloud\Shipping\Entity\Shipment
 *
 * @method void              add(ShipmentEntity $entity)
 * @method void              set(string $key, ShipmentEntity $entity)
 * @method ShipmentEntity[]    getIterator()
 * @method ShipmentEntity[]    getElements()
 * @method ShipmentEntity|null get(string $key)
 * @method ShipmentEntity|null first()
 * @method ShipmentEntity|null last()
 */
class ShipmentEntityCollection extends EntityCollection
{
    /**
     * @inheritDoc
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return ShipmentEntity::class;
    }
}
