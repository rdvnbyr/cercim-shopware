<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\DTO;

/**
 * Class WebHookParcelDTO
 * @package Sendcloud\Shipping\Core\BusinessLogic\DTO
 */
class WebhookParcelDTO
{
    /**
     * @var string
     */
    private $action;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var int
     */
    private $parcelId;

    /**
     * @var string
     */
    private $trackingNumber;

    /**
     * @var int
     */
    private $statusId;

    /**
     * @var string
     */
    private $statusMessage;

    /**
     * @var string
     */
    private $orderNumber;

    /**
     * @var string
     */
    private $shipmentUuid;

    /**
     * @var string
     */
    private $orderId;

    /**
     * WebHookParcelDTO constructor.
     *
     * @param string $action
     * @param int $timestamp
     * @param int $parcelId
     */
    public function __construct($action, $timestamp, $parcelId)
    {
        $this->action = $action;
        $this->timestamp = $timestamp;
        $this->parcelId = $parcelId;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @return int
     */
    public function getParcelId()
    {
        return $this->parcelId;
    }

    /**
     * @param int $parcelId
     */
    public function setParcelId($parcelId)
    {
        $this->parcelId = $parcelId;
    }

    /**
     * @return string
     */
    public function getTrackingNumber()
    {
        return $this->trackingNumber;
    }

    /**
     * @param string $trackingNumber
     */
    public function setTrackingNumber($trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;
    }

    /**
     * @return int
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * @param int $statusId
     */
    public function setStatusId($statusId)
    {
        $this->statusId = $statusId;
    }

    /**
     * @return string
     */
    public function getStatusMessage()
    {
        return $this->statusMessage;
    }

    /**
     * @param string $statusMessage
     */
    public function setStatusMessage($statusMessage)
    {
        $this->statusMessage = $statusMessage;
    }

    /**
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * @param string $orderNumber
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;
    }

    /**
     * @return string
     */
    public function getShipmentUuid()
    {
        return $this->shipmentUuid;
    }

    /**
     * @param string $shipmentUuid
     */
    public function setShipmentUuid($shipmentUuid)
    {
        $this->shipmentUuid = $shipmentUuid;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

}
