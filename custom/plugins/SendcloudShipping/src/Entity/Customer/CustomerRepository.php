<?php

namespace Sendcloud\Shipping\Entity\Customer;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Class CustomerRepository
 *
 * @package Sendcloud\Shipping\Entity\Customer
 */
class CustomerRepository
{
    /**
     * @var EntityRepository
     */
    private $baseRepository;
    /**
     * CustomerRepository constructor.
     *
     * @param EntityRepository $baseRepository
     */
    public function __construct(EntityRepository $baseRepository)
    {
        $this->baseRepository = $baseRepository;
    }

    /**
     * Return customer by its id
     *
     * @param string $customerId
     *
     * @return CustomerEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getCustomerById(string $customerId): ?CustomerEntity
    {
        return $this->baseRepository->search(new Criteria([$customerId]), Context::createDefaultContext())->first();
    }
}
