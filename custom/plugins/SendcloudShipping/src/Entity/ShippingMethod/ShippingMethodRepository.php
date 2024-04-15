<?php

namespace Sendcloud\Shipping\Entity\ShippingMethod;

use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Class ShippingMethod
 *
 * @package Sendcloud\Shipping\Entity\ShippingMethod
 */
class ShippingMethodRepository
{
    /**
     * @var EntityRepository
     */
    private $baseRepository;

    /**
     * ShippingMethod constructor.
     *
     * @param EntityRepository $baseRepository
     */
    public function __construct(EntityRepository $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Returns shipping method by its id
     *
     * @param string $id
     *
     * @return ShippingMethodEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getShippingMethodById(string $id): ?ShippingMethodEntity
    {
        return $this->baseRepository->search(new Criteria([$id]), Context::createDefaultContext())->first();
    }
}
