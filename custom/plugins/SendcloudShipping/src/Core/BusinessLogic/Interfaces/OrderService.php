<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Interfaces;

use Sendcloud\Shipping\Core\BusinessLogic\Entity\Order;
use Sendcloud\Shipping\Core\BusinessLogic\Exceptions\OrdersGetException;

/**
 * Interface OrderService
 * @package Sendcloud\Shipping\Core\BusinessLogic\Interfaces
 */
interface OrderService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Gets all order IDs from source system.
     *
     * @return string[]
     */
    public function getAllOrderIds();

    /**
     * Gets all orders for passed batch ids formatted in the proper way.
     *
     * @param array $batchOrderIds
     *
     * @return Order[] based on passed ids
     *
     * @throws OrdersGetException
     */
    public function getOrders(array $batchOrderIds);

    /**
     * Returns order for passed id or null if order is not found.
     *
     * @param int|string $orderId
     *
     * @return Order|null
     */
    public function getOrderById($orderId);

    /**
     * Returns order for passed order number or null if order is not found. In most systems order ID and
     * order number are the same. SendCloud doesn't send external order ID in some webhook payloads.
     *
     * @param int|string $orderNumber
     *
     * @return Order|null
     */
    public function getOrderByNumber($orderNumber);

    /**
     * Updates order information on the host system
     *
     * @param Order $order
     */
    public function updateOrderStatus(Order $order);

    /**
     * Informs service about completed synchronization of provided orders (IDs).
     *
     * @param array $orderIds
     */
    public function orderSyncCompleted(array $orderIds);
}
