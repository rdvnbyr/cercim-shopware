<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Entity;

/**
 * Class Order
 * @package Sendcloud\Shipping\Core\BusinessLogic\Entity
 */
class Order
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $number;
    /**
     * @var string
     */
    private $address;
    /**
     * @var string
     */
    private $address2 = '';
    /**
     * @var string
     */
    private $customsInvoiceNr = '';
    /**
     * @var string
     */
    private $city;
    /**
     * @var string
     */
    private $companyName;
    /**
     * Two letter ISO country code
     *
     * @var string
     */
    private $countryCode;
    /**
     * Three letter currency code
     * @var string
     */
    private $currency;
    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $houseNumber;
    /**
     * @var string
     */
    private $customerName;
    /**
     * @var string
     */
    private $statusId;
    /**
     * @var string
     */
    private $statusName;
    /**
     * @var OrderItem[]
     */
    private $items = array();
    /**
     * @var string
     */
    private $paymentStatusId;
    /**
     * @var string
     */
    private $paymentStatusName;
    /**
     * @var string
     */
    private $postalCode;
    /**
     * @var string
     */
    private $telephone = '';
    /**
     * @var string
     */
    private $toPostNumber = '';
    /**
     * @var int
     */
    private $toServicePoint;
    /**
     * @var string
     */
    private $toState;
    /**
     * @var int
     */
    private $totalInsuredValue = 0;
    /**
     * @var float
     */
    private $weight = 0.0;
    /**
     * @var float
     */
    private $totalValue = 0.0;
    /**
     * @var \DateTime
     */
    private $createdAt;
    /**
     * @var \DateTime
     */
    private $updatedAt;
    /**
     * @var string|null
     */
    private $shipmentId;
    /**
     * @var string
     */
    private $checkoutShippingName;
    /**
     * @var string
     */
    private $sendCloudStatus = '';
    /**
     * @var int
     */
    private $sendCloudStatusId;
    /**
     * @var string|null
     */
    private $sendCloudTrackingNumber;
    /**
     * @var string|null
     */
    private $sendCloudTrackingUrl;
    /**
     * @var string|null
     */
    private $sendCloudCarrierCode;
    /**
     * @var string|null
     */
    private $sendCloudParcelId;

    /**
     * @var int|null
     */
    private $customsShipmentType;
    /**
     * @var int|null
     */
    private $senderAddress;
    /**
     * @var int
     */
    private $shippingMethodId;
    /**
     * @var string|null
     */
    private $shipmentUuid;
    /**
     * @var bool|null
     */
    private $isReturn;
    /**
     * @var string|null
     */
    private $length;
    /**
     * @var string|null
     */
    private $height;
    /**
     * @var string|null
     */
    private $width;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return null|string
     */
    public function getSendCloudTrackingNumber()
    {
        return $this->sendCloudTrackingNumber;
    }

    /**
     * @param null|string $sendCloudTrackingNumber
     */
    public function setSendCloudTrackingNumber($sendCloudTrackingNumber)
    {
        $this->sendCloudTrackingNumber = $sendCloudTrackingNumber;
    }

    /**
     * @return string|null
     */
    public function getSendCloudTrackingUrl()
    {
        return $this->sendCloudTrackingUrl;
    }

    /**
     * @param string|null $sendCloudTrackingUrl
     */
    public function setSendCloudTrackingUrl($sendCloudTrackingUrl)
    {
        $this->sendCloudTrackingUrl = $sendCloudTrackingUrl;
    }

    /**
     * @return int|null
     */
    public function getCustomsShipmentType()
    {
        return $this->customsShipmentType;
    }

    /**
     * @param int|null $customsShipmentType
     */
    public function setCustomsShipmentType($customsShipmentType)
    {
        $this->customsShipmentType = $customsShipmentType;
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return string
     */
    public function getCheckoutShippingName()
    {
        return $this->checkoutShippingName;
    }

    /**
     * @param string $checkoutShippingName
     */
    public function setCheckoutShippingName($checkoutShippingName)
    {
        $this->checkoutShippingName = $checkoutShippingName;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getAddress2()
    {
        return $this->address2;
    }

    /**
     * @param string $address2
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;
    }

    /**
     * @return string
     */
    public function getCustomsInvoiceNr()
    {
        return $this->customsInvoiceNr;
    }

    /**
     * @param string $customsInvoiceNr
     */
    public function setCustomsInvoiceNr($customsInvoiceNr)
    {
        $this->customsInvoiceNr = $customsInvoiceNr;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * @param string $companyName
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        return $this->countryCode;
    }

    /**
     * @param string $countryCode
     */
    public function setCountryCode($countryCode)
    {
        $this->countryCode = $countryCode;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getHouseNumber()
    {
        return $this->houseNumber;
    }

    /**
     * @param string $houseNumber
     */
    public function setHouseNumber($houseNumber)
    {
        $this->houseNumber = $houseNumber;
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * @param string $customerName
     */
    public function setCustomerName($customerName)
    {
        $this->customerName = $customerName;
    }

    /**
     * @return string
     */
    public function getStatusId()
    {
        return $this->statusId;
    }

    /**
     * @param string $statusId
     */
    public function setStatusId($statusId)
    {
        $this->statusId = $statusId;
    }

    /**
     * @return string
     */
    public function getStatusName()
    {
        return $this->statusName;
    }

    /**
     * @param string $statusName
     */
    public function setStatusName($statusName)
    {
        $this->statusName = $statusName;
    }

    /**
     * @return OrderItem[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param OrderItem[] $items
     */
    public function setItems($items)
    {
        $this->items = $items;
    }

    /**
     * @return string
     */
    public function getPaymentStatusId()
    {
        return $this->paymentStatusId;
    }

    /**
     * @param string $paymentStatusId
     */
    public function setPaymentStatusId($paymentStatusId)
    {
        $this->paymentStatusId = $paymentStatusId;
    }

    /**
     * @return string
     */
    public function getPaymentStatusName()
    {
        return $this->paymentStatusName;
    }

    /**
     * @param string $paymentStatusName
     */
    public function setPaymentStatusName($paymentStatusName)
    {
        $this->paymentStatusName = $paymentStatusName;
    }

    /**
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
    }

    /**
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * @param string $telephone
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }

    /**
     * @return string
     */
    public function getToPostNumber()
    {
        return $this->toPostNumber;
    }

    /**
     * @param string $toPostNumber
     */
    public function setToPostNumber($toPostNumber)
    {
        $this->toPostNumber = $toPostNumber;
    }

    /**
     * @return int
     */
    public function getToServicePoint()
    {
        return $this->toServicePoint;
    }

    /**
     * @param int $toServicePoint
     */
    public function setToServicePoint($toServicePoint)
    {
        $this->toServicePoint = $toServicePoint;
    }

    /**
     * @return string
     */
    public function getToState()
    {
        return $this->toState;
    }

    /**
     * @param string $toState
     */
    public function setToState($toState)
    {
        $this->toState = $toState;
    }

    /**
     * @return int
     */
    public function getTotalInsuredValue()
    {
        return $this->totalInsuredValue;
    }

    /**
     * @param int $totalInsuredValue
     */
    public function setTotalInsuredValue($totalInsuredValue)
    {
        $this->totalInsuredValue = $totalInsuredValue;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return float
     */
    public function getTotalValue()
    {
        return $this->totalValue;
    }

    /**
     * @param float $totalValue
     */
    public function setTotalValue($totalValue)
    {
        $this->totalValue = $totalValue;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string
     */
    public function getSendCloudStatus()
    {
        return $this->sendCloudStatus;
    }

    /**
     * @param string $sendCloudStatus
     */
    public function setSendCloudStatus($sendCloudStatus)
    {
        $this->sendCloudStatus = $sendCloudStatus;
    }

    /**
     * @return int|null
     */
    public function getSendCloudStatusId()
    {
        return $this->sendCloudStatusId;
    }

    /**
     * @param int $sendCloudStatusId
     */
    public function setSendCloudStatusId($sendCloudStatusId)
    {
        $this->sendCloudStatusId = $sendCloudStatusId;
    }

    /**
     * @return null|string
     */
    public function getShipmentId()
    {
        return $this->shipmentId;
    }

    /**
     * @param null|string $shipmentId
     */
    public function setShipmentId($shipmentId)
    {
        $this->shipmentId = $shipmentId;
    }

    /**
     * @return string|null
     */
    public function getSendCloudCarrierCode()
    {
        return $this->sendCloudCarrierCode;
    }

    /**
     * @param string|null $sendCloudCarrierCode
     */
    public function setSendCloudCarrierCode($sendCloudCarrierCode)
    {
        $this->sendCloudCarrierCode = $sendCloudCarrierCode;
    }

    /**
     * @return string|null
     */
    public function getSendCloudParcelId()
    {
        return $this->sendCloudParcelId;
    }

    /**
     * @param string|null $sendCloudParcelId
     */
    public function setSendCloudParcelId($sendCloudParcelId)
    {
        $this->sendCloudParcelId = $sendCloudParcelId;
    }

    /**
     * @return int|null
     */
    public function getSenderAddress()
    {
        return $this->senderAddress;
    }

    /**
     * @param int|null $senderAddress
     */
    public function setSenderAddress($senderAddress)
    {
        $this->senderAddress = $senderAddress;
    }

    /**
     * @return int
     */
    public function getShippingMethodId()
    {
        return $this->shippingMethodId;
    }

    /**
     * @param int $shippingMethodId
     */
    public function setShippingMethodId($shippingMethodId)
    {
        $this->shippingMethodId = $shippingMethodId;
    }

    /**
     * @return string|null
     */
    public function getShipmentUuid()
    {
        return $this->shipmentUuid;
    }

    /**
     * @param string|null $shipmentUuid
     */
    public function setShipmentUuid( $shipmentUuid)
    {
        $this->shipmentUuid = $shipmentUuid;
    }

    /**
     * @return bool|null
     */
    public function isReturn()
    {
        return $this->isReturn;
    }

    /**
     * @param bool|null $isReturn
     */
    public function setIsReturn($isReturn)
    {
        $this->isReturn = $isReturn;
    }

    /**
     * @return string|null
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param string|null $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return string|null
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string|null $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }

    /**
     * @return string|null
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param string|null $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }
}
