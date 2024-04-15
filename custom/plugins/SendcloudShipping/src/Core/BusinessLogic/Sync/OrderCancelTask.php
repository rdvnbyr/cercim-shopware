<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Sync;

/**
 * Class OrderCancelTask
 * @package Sendcloud\Shipping\Core\BusinessLogic\Sync
 */
class OrderCancelTask extends BaseSyncTask
{

    /**
     * @var string Order id
     */
    private $orderId;

    /**
     * @var string|null Order number
     */
    private $shipmentId;

    /**
     * OrderCancelTask constructor.
     *
     * @param string $orderId
     * @param string $shipmentId
     */
    public function __construct($orderId, $shipmentId = null)
    {
        $this->orderId = $orderId;
        $this->shipmentId = $shipmentId;
    }

    /**
     * String representation of object
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize(array($this->orderId, $this->shipmentId));
    }

    /**
     * Constructs the object
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        list($this->orderId, $this->shipmentId) = unserialize($serialized);
    }
    
    /**
     * Runs task logic
     */
    public function execute()
    {
        $this->getProxy()->cancelOrderById($this->orderId, $this->shipmentId);
        $this->reportProgress(75);

        if ($order = $this->getOrderService()->getOrderById($this->orderId)) {
            $order->setSendCloudStatus('Deleted');
            $this->getOrderService()->updateOrderStatus($order);
        }

        $this->reportProgress(100);
    }
}
