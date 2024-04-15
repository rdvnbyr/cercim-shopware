<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\DTO;

/**
 * Class ShipmentResponseDTO
 * @package Sendcloud\Shipping\Core\BusinessLogic\DTO
 */
class ShipmentResponseDTO
{
    /**
     * @var string
     */
    private $externalOrderId;
    /**
     * @var string
     */
    private $externalShipmentId;
    /**
     * @var string
     */
    private $shipmentUuid;
    /**
     * @var string
     */
    private $status;
    /**
     * @var array
     */
    private $errors;

    /**
     * ShipmentResponseDTO constructor.
     *
     * @param string $externalOrderId
     * @param string $externalShipmentId
     * @param string $shipmentUuid
     * @param string $status
     * @param array $errors
     */
    public function __construct($externalOrderId, $externalShipmentId, $shipmentUuid, $status, array $errors)
    {
        $this->externalOrderId = $externalOrderId;
        $this->externalShipmentId = $externalShipmentId;
        $this->shipmentUuid = $shipmentUuid;
        $this->status = $status;
        $this->errors = $errors;
    }

    /**
     * @return string
     */
    public function getExternalOrderId()
    {
        return $this->externalOrderId;
    }

    /**
     * @return string
     */
    public function getExternalShipmentId()
    {
        return $this->externalShipmentId;
    }

    /**
     * @return string
     */
    public function getShipmentUuid()
    {
        return $this->shipmentUuid;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
