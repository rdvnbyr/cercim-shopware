<?php

namespace Sendcloud\Shipping\Core\BusinessLogic;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\IntegrationDTO;
use Sendcloud\Shipping\Core\BusinessLogic\DTO\SendcloudExceptionLogs\ExceptionLog;
use Sendcloud\Shipping\Core\BusinessLogic\DTO\ShipmentDTO;
use Sendcloud\Shipping\Core\BusinessLogic\DTO\ShipmentResponseDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Entity\Order;
use Sendcloud\Shipping\Core\BusinessLogic\Entity\OrderItem;
use Sendcloud\Shipping\Core\Infrastructure\Logger\LogData;
use Sendcloud\Shipping\Core\Infrastructure\Utility\HttpResponse;

/**
 * Class ProxyTransformer
 * @package Sendcloud\Shipping\Core\BusinessLogic
 */
class ProxyTransformer
{
    const CLASS_NAME = __CLASS__;

    const DATE_FORMAT = "Y-m-d\TH:i:s.uP";

    /**
     * Transforms raw json response to array of integration objects
     *
     * @param HttpResponse $response
     *
     * @return IntegrationDTO
     */
    public function transformIntegrationResponse(HttpResponse $response)
    {
        $integration = json_decode($response->getBody(), true);

        return $this->buildIntegrationObject($integration);
    }

    /**
     * Transform Integration object to an array which is suitable for sending to remote API
     *
     * @param IntegrationDTO $integrationDTO
     *
     * @return array
     */
    public function transformIntegration(IntegrationDTO $integrationDTO)
    {
        $result = array();
        if ($shopName = $integrationDTO->getShopName()) {
            $result['shop_name'] = $shopName;
        }

        if ($carriers = $integrationDTO->getServicePointCarriers()) {
            $result['service_point_carriers'] = $carriers;
        }

        $servicePointEnabled = $integrationDTO->isServicePointsEnabled();
        if (isset($servicePointEnabled)) {
            $result['service_point_enabled'] = $servicePointEnabled;
        }

        $webhookActive = $integrationDTO->isWebHookActive();
        if (isset($webhookActive)) {
            $result['webhook_active'] = $webhookActive;
        }

        if ($webhookUrl = $integrationDTO->getWebHookUrl()) {
            $result['webhook_url'] = $webhookUrl;
        }

        return $result;
    }

    /**
     * Transform Sendcloud Exception log object to an array which is suitable for sending to remote API
     *
     * @param ExceptionLog $exceptionLog
     *
     * @return array
     */
    public function transformExceptionLog(ExceptionLog $exceptionLog)
    {
        $result = array();

        $result['exception'] = $exceptionLog->getException() ?: '';
        $result['exception_type'] = $exceptionLog->getExceptionType();
        $result['method'] = $exceptionLog->getMethod();
        $result['response_code'] = $exceptionLog->getResponseCode();
        $result['request'] = json_encode($exceptionLog->getRequest()->toArray());
        $result['response'] = json_encode($exceptionLog->getResponse()->toArray());
        $result['base_url'] = $exceptionLog->getBaseUrl();
        $result['full_url'] = $exceptionLog->getFullUrl();
        $result['created_at'] = $exceptionLog->getCreatedAt();

        return $result;
    }

    /**
     * Transforms raw json response to order object
     *
     * @param HttpResponse $response
     *
     * @return ShipmentDTO[]
     */
    public function transformShipmentsResponse(HttpResponse $response)
    {
        $result = array();
        $shipments = json_decode($response->getBody(), true);
        foreach ($shipments['results'] as $shipment) {
            $result[] = $this->buildShipmentObject($shipment);
        }

        return $result;
    }

    /**
     * Transforms raw json response
     *
     * @param HttpResponse $response
     *
     * @return array
     */
    public function transformParcel(HttpResponse $response)
    {
        $response = json_decode($response->getBody(), true);

        return isset($response['parcel']) ? $response['parcel'] : array();
    }

    /**
     * Parses and returns an array of shipment results for shipment upsert call
     *
     * @param HttpResponse $response
     *
     * @return ShipmentResponseDTO[]
     */
    public function transformShipmentUpsertResponse(HttpResponse $response)
    {
        $result = array();
        $shipments = json_decode($response->getBody(), true);
        foreach ($shipments as $item) {
            $errors = isset($item['error']) ? $item['error'] : array();
            $shipmentUuid = isset($item['shipment_uuid']) ? $item['shipment_uuid'] : null;

            $result[] = new ShipmentResponseDTO(
                $item['external_order_id'],
                $item['external_shipment_id'],
                $shipmentUuid,
                $item['status'],
                $errors
            );
        }

        return $result;
    }

    /**
     * Transforms order object to an array which is suitable for sending to remote API
     *
     * @param ShipmentDTO[] $shipmentDTOs
     *
     * @return array
     */
    public function transformShipments(array $shipmentDTOs)
    {
        $result = array();
        foreach ($shipmentDTOs as $shipmentDTO) {
            $order = $shipmentDTO->getOrderEntity();
            $createdAt = $order->getCreatedAt();
            $updatedAt = $order->getUpdatedAt();
            $shipment = array(
                'address' => $order->getAddress(),
                'address_2' => $order->getAddress2(),
                'city' => $order->getCity(),
                'company_name' => $order->getCompanyName(),
                'country' => $order->getCountryCode(),
                'currency' => $order->getCurrency(),
                'created_at' => $createdAt ? $createdAt->format(self::DATE_FORMAT) : '0000-00-00T00:00:00.000000+00:00',
                'customs_invoice_nr' => $order->getCustomsInvoiceNr(),
                'customs_shipment_type' => $order->getCustomsShipmentType(),
                'email' => $order->getEmail(),
                'external_order_id' => $order->getId(),
                'external_shipment_id' => $order->getShipmentId(),
                'house_number' => $order->getHouseNumber(),
                'name' => $order->getCustomerName(),
                'order_number' => $order->getNumber(),
                'order_status' => $this->getStatus($order->getStatusId(), $order->getStatusName()),
                'parcel_items' => $this->formatParcelItems($order->getItems()),
                'payment_status' => $this->getStatus($order->getPaymentStatusId(), $order->getPaymentStatusName()),
                'postal_code' => $order->getPostalCode(),
                'shipping_method_checkout_name' => $order->getCheckoutShippingName(),
                'telephone' => $order->getTelephone(),
                'to_post_number' => $order->getToPostNumber(),
                'to_service_point' => $order->getToServicePoint(),
                'to_state' => $order->getToState(),
                'total_insured_value' => $order->getTotalInsuredValue(),
                'total_order_value' => $order->getTotalValue(),
                'updated_at' => $updatedAt ? $updatedAt->format(self::DATE_FORMAT) : '0000-00-00T00:00:00.000000+00:00',
                'weight' => (string)round($order->getWeight(), 3),
                'sender_address' => $order->getSenderAddress(),
                'length' => $order->getLength(),
                'width' => $order->getWidth(),
                'height' => $order->getHeight()
            );

            if ($shipment['weight'] == 0) {
                unset($shipment['weight']);
            }

            $result[] = $shipment;
        }

        return $result;
    }

    /**
     * Transforms Log data object to an array which is suitable for sending to remote API
     *
     * @param LogData $logData
     *
     * @return array
     */
    public function transformLogData(LogData $logData)
    {
        // todo transform when full API specification is available
        return array();
    }

    /**
     * Transforms batch of log data objects to an array which is suitable for sending to remote API
     *
     * @param LogData[] $logBatch
     *
     * @return array
     */
    public function transformLogBatch(array $logBatch)
    {
        $result = array();
        foreach ($logBatch as $logData) {
            $result[] = $this->transformLogData($logData);
        }

        return $result;
    }

    /**
     * @param string|null $statusId
     * @param string|null $statusMessage
     * @return array|null
     */
    private function getStatus($statusId, $statusMessage)
    {
        if ($this->isEmpty($statusId) || $this->isEmpty($statusMessage)) {
            return null;
        }

        return array(
            'id' => $statusId,
            'message' => $statusMessage,
        );
    }

    /**
     * @param string $value
     * @return bool
     */
    private function isEmpty($value)
    {
        return $value === null || $value === '';
    }

    /**
     * Builds Integration object out of response array
     *
     * @param array $integration
     *
     * @return IntegrationDTO
     */
    private function buildIntegrationObject(array $integration)
    {
        $failingSince = null;
        if (!empty($integration['failing_since'])) {
            $failingSince = \DateTime::createFromFormat(self::DATE_FORMAT, $integration['failing_since']);
        }

        $lastUpdateAt = null;
        if (!empty($integration['last_updated_at'])) {
            $lastUpdateAt = \DateTime::createFromFormat(self::DATE_FORMAT, $integration['last_updated_at']);
        }

        return new IntegrationDTO(
            $integration['id'],
            $integration['shop_name'],
            $integration['system'],
            $integration['service_point_enabled'],
            $integration['service_point_carriers'],
            $failingSince,
            $lastUpdateAt,
            $integration['webhook_active'],
            $integration['webhook_url']
        );
    }

    /**
     * Builds Shipment object out of response array
     *
     * @param array $shipment
     *
     * @return ShipmentDTO
     */
    private function buildShipmentObject(array $shipment)
    {
        $order = new Order();
        $order->setWeight((float)$shipment['weight']);
        $order->setTotalValue(isset($shipment['total_order_value']) ? (float)$shipment['total_order_value'] : null);
        $order->setToState($shipment['to_state']);
        $order->setStatusName(
            isset($shipment['order_status']['message']) ? $shipment['order_status']['message'] : null
        );
        $order->setStatusId(isset($shipment['order_status']['id']) ? $shipment['order_status']['id'] : null);
        $order->setToPostNumber($shipment['to_post_number']);
        $order->setToServicePoint($shipment['to_service_point']);
        $order->setPaymentStatusName(
            isset($shipment['payment_status']['message']) ? $shipment['payment_status']['message'] : null
        );
        $order->setPaymentStatusId(isset($shipment['payment_status']['id']) ? $shipment['payment_status']['id'] : null);
        $order->setTelephone($shipment['telephone']);
        $order->setPostalCode($shipment['postal_code']);
        $order->setCustomsInvoiceNr($shipment['customs_invoice_nr']);
        $order->setCustomsShipmentType($shipment['customs_shipment_type']);
        $order->setEmail($shipment['email']);
        $order->setCustomerName($shipment['name']);
        $order->setCheckoutShippingName($shipment['shipping_method_checkout_name']);
        $order->setCreatedAt(\DateTime::createFromFormat(self::DATE_FORMAT, $shipment['created_at']));
        $order->setUpdatedAt(\DateTime::createFromFormat(self::DATE_FORMAT, $shipment['updated_at']));
        $order->setCountryCode($shipment['country']);
        $order->setSendCloudStatus(isset($shipment['status']['message']) ? $shipment['status']['message'] : null);
        $order->setCompanyName($shipment['company_name']);
        $order->setHouseNumber($shipment['house_number']);
        $order->setCity($shipment['city']);
        $order->setAddress($shipment['address']);
        $order->setAddress2($shipment['address_2']);
        $order->setCurrency($shipment['currency']);
        $order->setId($shipment['external_order_id']);
        $order->setShipmentId($shipment['external_shipment_id']);
        $order->setShipmentUuid($shipment['shipment_uuid']);
        $order->setNumber($shipment['order_number']);
        $order->setSenderAddress($shipment['sender_address']);
        $order->setItems($this->setOrderItems($shipment['parcel_items']));
        $order->setShippingMethodId(array_key_exists('shipping_method', $shipment) ? $shipment['shipping_method'] : null);
        $order->setLength(isset($shipment['length']) ? $shipment['length'] : null);
        $order->setWidth(isset($shipment['width']) ? $shipment['width'] : null);
        $order->setHeight(isset($shipment['height']) ? $shipment['height'] : null);

        return new ShipmentDTO($order);
    }

    /**
     * Sets shipping order items
     *
     * @param array $parcelItems
     *
     * @return array
     */
    private function setOrderItems(array $parcelItems)
    {
        $orderItems = array();
        foreach ($parcelItems as $parcelItem) {
            $orderItem = new OrderItem();
            $orderItem->setWeight($parcelItem['weight']);
            $orderItem->setQuantity($parcelItem['quantity']);
            $orderItem->setSku($parcelItem['sku']);
            $orderItem->setDescription($parcelItem['description']);
            $orderItem->setProductId($parcelItem['product_id']);
            $orderItem->setValue($parcelItem['value']);
            $orderItem->setOriginCountry($parcelItem['origin_country']);
            $orderItem->setHsCode($parcelItem['hs_code']);
            $orderItem->setProperties($parcelItem['properties']);

            $orderItems[] = $orderItem;
        }

        return $orderItems;
    }

    /**
     * Formats order items to parcel expected format
     *
     * @param OrderItem[] $orderItems
     *
     * @return array
     */
    private function formatParcelItems(array $orderItems)
    {
        $result = array();
        foreach ($orderItems as $orderItem) {
            $item = array(
                'description' => $orderItem->getDescription(),
                'hs_code' => $orderItem->getHsCode(),
                'origin_country' => $orderItem->getOriginCountry(),
                'product_id' => $orderItem->getProductId(),
                'properties' => (object)$orderItem->getProperties(),
                'quantity' => $orderItem->getQuantity(),
                'sku' => $orderItem->getSku(),
                'value' => $orderItem->getValue(),
                'weight' => (string)round($orderItem->getWeight(), 3),
            );

            if ($item['weight'] == 0) {
                unset($item['weight']);
            }

            $result[] = $item;
        }

        return $result;
    }
}
