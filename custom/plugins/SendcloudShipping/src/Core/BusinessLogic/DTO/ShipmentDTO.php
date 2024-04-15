<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\DTO;

use Sendcloud\Shipping\Core\BusinessLogic\Entity\Order;

/**
 * Class ShipmentDTO
 * @package Sendcloud\Shipping\Core\BusinessLogic\DTO
 */
class ShipmentDTO
{

    /**
     * @var Order
     */
    private $orderEntity;

    /**
     * @var string
     */
    private $status;

    /**
     * ShipmentDTO constructor.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->orderEntity = $order;
    }

    /**
     * @return Order
     */
    public function getOrderEntity()
    {
        return $this->orderEntity;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }
}
