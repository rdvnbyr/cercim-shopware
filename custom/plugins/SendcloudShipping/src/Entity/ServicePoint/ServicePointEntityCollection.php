<?php

namespace Sendcloud\Shipping\Entity\ServicePoint;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Class ServicePointEntityCollection
 *
 * @package Sendcloud\Shipping\Entity\ServicePoint
 *
 * @method void              add(ServicePointEntity $entity)
 * @method void              set(string $key, ServicePointEntity $entity)
 * @method ServicePointEntity[]    getIterator()
 * @method ServicePointEntity[]    getElements()
 * @method ServicePointEntity|null get(string $key)
 * @method ServicePointEntity|null first()
 * @method ServicePointEntity|null last()
 */
class ServicePointEntityCollection extends EntityCollection
{
    /**
     * @inheritDoc
     *
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return ServicePointEntity::class;
    }
}