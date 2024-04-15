<?php

namespace Sendcloud\Shipping\Entity\Order;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Class OrderRepository
 *
 * @package Sendcloud\Shipping\Entity\Order
 */
class OrderRepository
{
    /**
     * @var EntityRepository
     */
    private $baseRepository;
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param EntityRepository $baseRepository
     * @param Connection $connection
     */
    public function __construct(EntityRepository $baseRepository, Connection $connection)
    {
        $this->baseRepository = $baseRepository;
        $this->connection = $connection;
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public function getOrderIds(): array
    {
        $tableName = $this->baseRepository->getDefinition()->getEntityName();
        $fromDate = (new \DateTime())->sub(new \DateInterval('P30D'))->format(DATE_ATOM);
        $sql = "SELECT `id` FROM `{$tableName}` WHERE created_at > '{$fromDate}'";

        $results = $this->connection->executeQuery($sql)->fetchAll();
        $ids = [];
        foreach ($results as $item) {
            $ids[] = Uuid::fromBytesToHex($item['id']);
        }

        return $ids;
    }

    /**
     * Returns collection of order entities for passed orderIds
     *
     * @param array $orderIds
     *
     * @return OrderCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrders(array $orderIds): OrderCollection
    {
        $criteria = new Criteria($orderIds);
        $criteria->addAssociations($this->getAssociationsForOrder());

        /** @var OrderCollection $collection */
        $collection = $this->baseRepository->search($criteria, Context::createDefaultContext())->getEntities();

        return  $collection;
    }

    /**
     * Returns order entity by order id
     *
     * @param string $orderId
     *
     * @return OrderEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrderById(string $orderId): ?OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociations($this->getAssociationsForOrder());

        return $this->baseRepository->search($criteria, Context::createDefaultContext())->first();
    }

    /**
     * Returns order entity by order number
     *
     * @param string $orderNumber
     *
     * @return OrderEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrderByNumber(string $orderNumber): ?OrderEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderNumber', $orderNumber));
        $criteria->addAssociations($this->getAssociationsForOrder());

        return $this->baseRepository->search($criteria, Context::createDefaultContext())->first();
    }

    /**
     * Returns associations for order search
     *
     * @return array
     */
    private function getAssociationsForOrder(): array
    {
        return [
            'addresses',
            'lineItems.product',
            'lineItems.product.options',
            'lineItems.product.options.group',
            'currency',
            'orderCustomer.customer',
            'deliveries.shippingMethod',
            'deliveries.shippingOrderAddress.country',
            'deliveries.shippingOrderAddress.state',
            'deliveries.shippingOrderAddress.countryState',
            'transactions.paymentMethod'
        ];
    }
}
