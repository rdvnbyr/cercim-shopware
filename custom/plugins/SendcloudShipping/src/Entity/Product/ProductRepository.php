<?php

namespace Sendcloud\Shipping\Entity\Product;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Class ProductRepository
 *
 * @package Sendcloud\Shipping\Entity\Product
 */
class ProductRepository
{
    /**
     * @var EntityRepository
     */
    private $baseRepository;

    /**
     * ProductRepository constructor.
     *
     * @param EntityRepository $baseRepository
     */
    public function __construct(EntityRepository $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Returns products by its ids
     *
     * @param array $productIds
     *
     * @return ProductCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getProducts(array $productIds): ProductCollection
    {
        $criteria = new Criteria($productIds);
        $criteria->addAssociations(['translations', 'media']);

        /** @var ProductCollection $collection */
        $collection = $this->baseRepository->search($criteria, Context::createDefaultContext())->getEntities();

        return $collection;
    }
}
