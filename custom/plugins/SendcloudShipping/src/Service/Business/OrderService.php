<?php

namespace Sendcloud\Shipping\Service\Business;

use Psr\Container\ContainerInterface;
use Sendcloud\Shipping\Core\BusinessLogic\Entity\Order;
use Sendcloud\Shipping\Core\BusinessLogic\Entity\OrderItem;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\OrderService as OrderServiceInterface;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Entity\Currency\CurrencyRepository;
use Sendcloud\Shipping\Entity\Order\OrderDeliveryRepository;
use Sendcloud\Shipping\Entity\Order\OrderRepository;
use Sendcloud\Shipping\Entity\Product\ProductRepository;
use Sendcloud\Shipping\Entity\Shipment\ShipmentEntityRepository;
use Sendcloud\Shipping\Entity\StateMachine\StateMachineRepository;
use Sendcloud\Shipping\Service\Utility\DeliveryStateMapper;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\Currency\CurrencyEntity;

/**
 * Class OrderService
 *
 * @package Sendcloud\Shipping\Service\Business
 */
class OrderService implements OrderServiceInterface
{
    private const AUSTRIAN_POST_CARRIER_CODE = 'postat';

    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var OrderDeliveryRepository
     */
    private $orderDeliveryRepository;
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;
    /**
     * @var ShipmentEntityRepository
     */
    private $shipmentRepository;
    /**
     * @var DeliveryStateMapper
     */
    private $deliveryStateMapper;
    /**
     * @var Configuration
     */
    private $configService;
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var StateMachineRepository
     */
    private $stateMachineRepository;

    /**
     * OrderService constructor.
     *
     * @param OrderRepository $orderRepository
     * @param ProductRepository $productRepository
     * @param CurrencyRepository $currencyRepository
     * @param ShipmentEntityRepository $shipmentRepository
     * @param OrderDeliveryRepository $orderDeliveryRepository
     * @param DeliveryStateMapper $deliveryStateMapper
     * @param Configuration $configService
     * @param ContainerInterface $container
     * @param StateMachineRepository $stateMachineRepository
     */
    public function __construct(
        OrderRepository $orderRepository,
        ProductRepository $productRepository,
        CurrencyRepository $currencyRepository,
        ShipmentEntityRepository $shipmentRepository,
        OrderDeliveryRepository $orderDeliveryRepository,
        DeliveryStateMapper $deliveryStateMapper,
        Configuration $configService,
        ContainerInterface $container,
        StateMachineRepository $stateMachineRepository
    )
    {
        $this->orderRepository = $orderRepository;
        $this->productRepository = $productRepository;
        $this->currencyRepository = $currencyRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->orderDeliveryRepository = $orderDeliveryRepository;
        $this->deliveryStateMapper = $deliveryStateMapper;
        $this->configService = $configService;
        $this->container = $container;
        $this->stateMachineRepository = $stateMachineRepository;
    }

    /**
     * Gets all order IDs from source system.
     *
     * @return string[]
     */
    public function getAllOrderIds(): array
    {
        $orderIds = [];
        try {
            $orderIds = $this->orderRepository->getOrderIds();
        } catch (\Exception $exception) {
            Logger::logError("An error occurred when fetching order ids from database: {$exception->getMessage()}. Stacktrace: {$exception->getTraceAsString()}", 'Integration');
        }

        return $orderIds;
    }

    /**
     * Gets all orders for passed batch ids formatted in the proper way.
     *
     * @param array $batchOrderIds
     *
     * @return Order[] based on passed ids
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrders(array $batchOrderIds): array
    {
        $orders = [];
        $sourceOrders = $this->orderRepository->getOrders($batchOrderIds);
        /** @var OrderEntity $sourceOrder */
        foreach ($sourceOrders as $sourceOrder) {
            $order = $this->buildOrderEntity($sourceOrder);

            $orders[] = $order;
        }

        return $orders;
    }

    /**
     * Returns order for passed id or null if order is not found.
     *
     * @param int|string $orderId
     *
     * @return Order|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrderById($orderId): ?Order
    {
        return $this->getOrderByNumber($orderId);
    }

    /**
     * Returns order for passed order number or null if order is not found. In most systems order ID and
     * order number are the same. SendCloud doesn't send external order ID in some webhook payloads.
     *
     * @param int|string $orderNumber
     *
     * @return Order|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getOrderByNumber($orderNumber): ?Order
    {
        $sourceOrder = $this->orderRepository->getOrderByNumber($orderNumber);

        return $sourceOrder ? $this->buildOrderEntity($sourceOrder) : null;
    }

    /**
     * Updates order information on the host system
     *
     * @param Order $order
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function updateOrderStatus(Order $order): void
    {
        try {
            $this->shipmentRepository->updateShipment(
                $order->getNumber(),
                $order->getSendCloudStatus(),
                $order->getToServicePoint(),
                $order->getSendCloudTrackingNumber(),
                $order->getSendCloudTrackingUrl()
            );

            $sourceOrder = $this->orderRepository->getOrderByNumber((string)$order->getNumber());
            if (!$sourceOrder) {
                return;
            }

            $deliveryCollection = $sourceOrder->getDeliveries();
            $delivery = $deliveryCollection ? $deliveryCollection->first() : null;
            if (!$delivery) {
                return;
            }

            $id = $delivery->getId();
            $this->orderDeliveryRepository->updateTrackingNumber(
                $id,
                (string)$order->getSendCloudTrackingNumber(),
                Context::createDefaultContext()
            );

            if ($this->shouldSkipDeliveryStatusUpdate($order)) {
                return;
            }

            $this->deliveryStateMapper->updateStatus($id, $order->getSendCloudStatusId());
        } catch (\Exception $exception) {
            Logger::logError("Failed to update order status: {$exception->getMessage()}. Stacktrace: {$exception->getTraceAsString()}", 'Integration');
        }
    }

    /**
     * Informs service about completed synchronization of provided orders (IDs).
     *
     * @param array $orderIds
     */
    public function orderSyncCompleted(array $orderIds): void
    {
        // Intentionally left empty. We do not need this functionality
    }

    /**
     * Calculates order weight
     *
     * @param LineItemCollection $lineItemCollection
     *
     * @return float
     * @throws InconsistentCriteriaIdsException
     */
    public function calculateTotalWeight(LineItemCollection $lineItemCollection): float
    {
        $totalWeight = 0;
        $productsMap = $this->createProductsMap($lineItemCollection);

        /** @var LineItem $sourceItem */
        foreach ($lineItemCollection as $sourceItem) {
            $quantity = $sourceItem->getQuantity();
            $productId = $sourceItem->getId();
            if (array_key_exists($productId, $productsMap)) {
                /** @var ProductEntity $productEntity */
                $productEntity = $productsMap[$productId];
                $totalWeight += $quantity * $productEntity->getWeight();
            }
        }

        return round(((float)$totalWeight), 2);
    }

    /**
     * Creates order entity
     *
     * @param OrderEntity $sourceOrder
     *
     * @return Order
     * @throws InconsistentCriteriaIdsException
     * @throws \Exception
     */
    private function buildOrderEntity(OrderEntity $sourceOrder): Order
    {
        $order = new Order();
        $order->setId($sourceOrder->getId());
        $order->setNumber($sourceOrder->getOrderNumber());
        $this->setOrderState($sourceOrder, $order);
        $this->setPayment($sourceOrder, $order);
        $customer = $sourceOrder->getOrderCustomer();
        if ($customer) {
            $name = $customer->getFirstName();
            if (!empty($customer->getLastName())) {
                $name .= ' ' . $customer->getLastName();
            }

            $order->setCustomerName($name);
            $order->setEmail($customer->getEmail());
        }

        $order->setCurrency('EUR');
        $this->setDates($sourceOrder, $order);

        $delivery = $sourceOrder->getDeliveries() ? $sourceOrder->getDeliveries()->first() : null;
        if ($delivery) {
            $this->setDeliveryInformation($delivery, $order);
        }

        $shipment = $this->shipmentRepository->getShipmentByOrderNumber($sourceOrder->getOrderNumber());
        if ($shipment && ($servicePointId = $shipment->get('servicePointId'))) {
            $order->setToServicePoint((int)$servicePointId);
        }

        $this->setItemsAndValues($sourceOrder->getLineItems(), $order, $sourceOrder->getCurrency());

        if (!$order->getTelephone()) {
            $this->setFallbackPhone($sourceOrder, $order);
        }

        if ($invoiceNumber = $this->getInvoiceNumber($sourceOrder->getId())) {
            $order->setCustomsInvoiceNr($invoiceNumber);
        }
        $defaultShipmentType = $this->configService->getDefaultShipmentType();
        if ($defaultShipmentType !== null && $defaultShipmentType !== '') {
            $order->setCustomsShipmentType($defaultShipmentType);
        }

        return $order;
    }

    /**
     * Get invoice number for order id
     *
     * @param string $orderId
     *
     * @return string|null
     */
    private function getInvoiceNumber(string $orderId): ?string
    {
        $documentRepository = $this->container->get('document.repository');

        $criteria = new Criteria();
        $criteria->addSorting(new FieldSorting('document.createdAt', FieldSorting::DESCENDING));
        $criteria->addFilter(new EqualsFilter('document.orderId', $orderId));
        $criteria->addFilter(new EqualsFilter('document.documentTypeId', $this->getInvoiceTypeId()));
        $document = $documentRepository->search($criteria, Context::createDefaultContext())->first();

        return $document ? $document->getConfig()['documentNumber'] : null;
    }

    /**
     * Retrieves invoice type ID
     *
     * @return string
     */
    private function getInvoiceTypeId(): string
    {
        $documentTypeRepository = $this->container->get('document_type.repository');
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', 'invoice'));

        return $documentTypeRepository->search($criteria, Context::createDefaultContext())->first()->getId();
    }

    /**
     * Set order items, total value and weight
     *
     * @param OrderLineItemCollection $sourceItems
     * @param Order $order
     * @param CurrencyEntity|null $currencyEntity
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function setItemsAndValues(OrderLineItemCollection $sourceItems, Order $order, ?CurrencyEntity $currencyEntity): void
    {
        $orderItems = [];
        $totalWeight = 0;
        $totalValue = 0;
        if (!$currencyEntity || $currencyEntity->getIsoCode() === CurrencyRepository::EURO) {
            $factor = 1;
        } else {
            $factor = $this->getEurosFactor($currencyEntity->getFactor());
        }
        /** @var OrderLineItemEntity $sourceItem */
        foreach ($sourceItems as $sourceItem) {
            if ($sourceItem->getType() !== 'product') {
                continue;
            }

            $productId = $sourceItem->getIdentifier();
            $orderItem = new OrderItem();
            $orderItem->setProductId($productId);
            $orderItem->setDescription($sourceItem->getLabel());
            $quantity = $sourceItem->getQuantity();
            $orderItem->setQuantity($quantity);
            $value = $sourceItem->getUnitPrice();
            $totalValue += $quantity * $value;
            $orderItem->setValue(round($factor * $value, 2));
            $productEntity = $sourceItem->getProduct();

            if ($this->configService->getDefaultHsCode()) {
                $orderItem->setHsCode($this->configService->getDefaultHsCode());
            }

            if ($this->configService->getDefaultOriginCountry()) {
                $orderItem->setOriginCountry($this->configService->getDefaultOriginCountry());
            }

            if ($productEntity) {
                $weight = $this->getProductWeight($productEntity);
                $orderItem->setSku($productEntity->getProductNumber());
                $totalWeight += $quantity * $weight;
                $orderItem->setWeight(round($weight, 2));
                $this->setProductLevelInternationalShippingData($orderItem, $productEntity);

                $orderItemProperties = [];
                foreach ($productEntity->getOptions() as $option) {
                    $group = $option->getGroup();

                    if ($group) {
                        $orderItemProperties[$group->getTranslation('name')] = $option->getTranslation('name');
                    }
                }

                $orderItem->setProperties($orderItemProperties);

                Logger::logInfo(
                    "HS code and Country of Origin for product with id: " . $orderItem->getProductId() . " are: " .
                    ($orderItem->getHsCode() !== '' ? $orderItem->getHsCode() : 'not set' ) .
                    ' and ' .
                    ($orderItem->getOriginCountry() !== '' ? $orderItem->getOriginCountry() : 'not set')
                );
            }

            $orderItems[] = $orderItem;
        }

        $order->setItems($orderItems);
        $order->setTotalValue(number_format($factor * $totalValue, 2, '.', ''));
        $order->setWeight($totalWeight);
    }

    /**
     * @param ProductEntity $productEntity
     *
     * @return float|null
     */
    private function getProductWeight(ProductEntity $productEntity): ?float
    {
        if ($weight = $productEntity->getWeight()) {
            return $weight;
        }

        $parentId = $productEntity->getParentId();

        if ($parentId && $parent = $this->productRepository->getProducts([$parentId])->first()) {
            return $parent->getWeight();
        }

        return null;
    }

    /**
     * Set HS Code and Origin Country from product configuration
     *
     * @param OrderItem $orderItem
     * @param ProductEntity $productEntity
     */
    private function setProductLevelInternationalShippingData(OrderItem $orderItem, ProductEntity $productEntity): void
    {
        if ($customFields = $this->getCustomFields($productEntity)) {
            if ($this->configService->getMappedHsCode() && !empty($customFields[$this->configService->getMappedHsCode()])) {
                $orderItem->setHsCode($customFields[$this->configService->getMappedHsCode()]);
            }

            if ($this->configService->getMappedOriginCountry() && !empty($customFields[$this->configService->getMappedOriginCountry()])) {
                $orderItem->setOriginCountry($customFields[$this->configService->getMappedOriginCountry()]);
            }
        }
    }

    /**
     * Returns custom fields array
     *
     * @param ProductEntity $productEntity
     *
     * @return array|null
     */
    private function getCustomFields(ProductEntity $productEntity): ?array
    {
        $customFields = $productEntity->getCustomFields();
        if ($customFields) {
            return $customFields;
        }

        $parentId = $productEntity->getParentId();

        if ($parentId && $parent = $this->productRepository->getProducts([$parentId])->first()) {
            return $parent->getCustomFields();
        }

        return null;
    }

    /**
     * Set shipping method and shipping address information
     *
     * @param OrderDeliveryEntity $delivery
     * @param Order $order
     */
    private function setDeliveryInformation(OrderDeliveryEntity $delivery, Order $order): void
    {
        $shippingMethod = $delivery->getShippingMethod();
        if ($shippingMethod) {
            $order->setCheckoutShippingName($shippingMethod->getTranslation('name'));
        }

        $shippingAddress = $delivery->getShippingOrderAddress();
        if ($shippingAddress) {
            $this->setAddressData($order, $shippingAddress);
        }
    }

    /**
     * Set shipping address information
     *
     * @param Order $order
     * @param OrderAddressEntity $shippingAddress
     */
    private function setAddressData(Order $order, OrderAddressEntity $shippingAddress): void
    {
        $country = $shippingAddress->getCountry();
        $order->setCountryCode($country ? $country->getIso() : '');

        $state = $shippingAddress->getCountryState();
        if ($state) {
            $toStateParts = explode('-', (string)$state->getShortCode());
            // Remove country code from state code if it exist
            if (count($toStateParts) > 1) {
                array_shift($toStateParts);
            }
            $toState = implode('-', $toStateParts);

            $order->setToState($toState);
        }

        $address1 = $shippingAddress->getStreet();
        $address2 = '';
        if (!empty($shippingAddress->getAdditionalAddressLine1())) {
            $address2 .= $shippingAddress->getAdditionalAddressLine1() . ' ';
        }

        if (!empty($shippingAddress->getAdditionalAddressLine2())) {
            $address2 .= $shippingAddress->getAdditionalAddressLine2();
        }

        $name = $shippingAddress->getFirstName();
        if (!empty($shippingAddress->getLastName())) {
            $name .= ' ' . $shippingAddress->getLastName();
        }

        $order->setCustomerName($name);
        $order->setAddress($address1);
        $order->setAddress2($address2);
        $order->setPostalCode($shippingAddress->getZipcode());
        $order->setCity($shippingAddress->getCity());
        $order->setCompanyName((string)$shippingAddress->getCompany());
        $order->setTelephone($shippingAddress->getPhoneNumber());
        $order->setHouseNumber('');
    }

    /**
     * Set payment information
     *
     * @param  OrderEntity  $sourceOrder
     * @param  Order  $order
     */
    private function setPayment(OrderEntity $sourceOrder, Order $order): void
    {
        $transaction = $this->getTransaction($sourceOrder);
        if ($transaction && $transaction->getStateMachineState()) {
            $order->setPaymentStatusId($transaction->getStateMachineState()->getId());
            $order->setPaymentStatusName($transaction->getStateMachineState()->getTranslation('name'));
        }

        if ($transaction && $stateMachine = $this->stateMachineRepository->getStateMachineById($transaction->getStateId(),
                Context::createDefaultContext())) {
            $order->setPaymentStatusId($stateMachine->getId());
            $order->setPaymentStatusName($stateMachine->getTranslation('name'));
        }
    }

    /**
     * Get transaction
     *
     * @param  \Shopware\Core\Checkout\Order\OrderEntity  $sourceOrder
     *
     * @return \Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity
     */
    private function getTransaction(OrderEntity $sourceOrder): ?OrderTransactionEntity
    {
        $lastTransaction = $sourceOrder->getTransactions() ? $sourceOrder->getTransactions()->last() : null;
        foreach ($sourceOrder->getTransactions() as $transaction) {
            if ($transaction->getUpdatedAt() && $lastTransaction->getUpdatedAt() &&
                $transaction->getUpdatedAt()->getTimestamp() > $lastTransaction->getUpdatedAt()->getTimestamp()) {
                $lastTransaction = $transaction;
            }
        }

        return $lastTransaction;
    }

    /**
     * Set order state information
     *
     * @param OrderEntity $sourceOrder
     * @param Order $order
     */
    private function setOrderState(OrderEntity $sourceOrder, Order $order): void
    {
        $state = $sourceOrder->getStateMachineState();
        if ($state) {
            $order->setStatusId($state->getId());
            $order->setStatusName($state->getTranslation('name'));

            return;
        }

        $stateMachine = $this->stateMachineRepository->getStateMachineById($sourceOrder->getStateId(), Context::createDefaultContext());
        if ($stateMachine) {
            $order->setStatusId($stateMachine->getId());
            $order->setStatusName($stateMachine->getName());
        }
    }

    /**
     * Returns product map with id as key ['productId' => productEntity]
     *
     * @param OrderLineItemCollection|LineItemCollection|null $sourceItems
     *
     * @return ProductEntity[]
     * @throws InconsistentCriteriaIdsException
     */
    private function createProductsMap($sourceItems): array
    {
        if (!$sourceItems) {
            return [];
        }

        $productIds = $sourceItems->map(function ($sourceItem) {
            /** @var OrderLineItemEntity|LineItem $sourceItem */
            return ($sourceItem instanceof OrderLineItemEntity) ? $sourceItem->getIdentifier() : $sourceItem->getId();
        });

        return $this->productRepository->getProducts($productIds)->getElements();
    }

    /**
     * @param float $productCurrencyFactor
     *
     * @return float
     * @throws InconsistentCriteriaIdsException
     */
    private function getEurosFactor(float $productCurrencyFactor): float
    {
        $euro = $this->currencyRepository->getEuroCurrency();
        $factor = $euro ? ($productCurrencyFactor / $euro->getFactor()) : 1;

        return round($factor, 2);
    }

    /**
     * Set create and update dates
     *
     * @param OrderEntity $sourceOrder
     * @param Order $order
     *
     * @throws \Exception
     */
    private function setDates(OrderEntity $sourceOrder, Order $order): void
    {
        $createdAt = $sourceOrder->getOrderDateTime() ?: new \DateTime();
        $order->setCreatedAt(new \DateTime("@{$createdAt->getTimestamp()}"));
        $updatedAt = $sourceOrder->getUpdatedAt() ?: $createdAt;
        $order->setUpdatedAt(new \DateTime("@{$updatedAt->getTimestamp()}"));
    }

    /**
     * Set phone number from billing address
     *
     * @param OrderEntity $sourceOrder
     * @param Order $order
     */
    private function setFallbackPhone(OrderEntity $sourceOrder, Order $order): void
    {
        $billingAddressId = $sourceOrder->getBillingAddressId();
        $addresses = $sourceOrder->getAddresses();
        if ($addresses) {
            $billingAddress = $addresses->filter(function ($address) use ($billingAddressId) {
                /** @var OrderAddressEntity $address */
                return $address->getId() === $billingAddressId;
            })->first();

            $phone = $billingAddress ? (string)$billingAddress->getPhoneNumber() : '';
            $order->setTelephone($phone);
        }
    }

    /**
     * Check if order update should be skipped
     *
     * @param Order $order
     * @return bool
     */
    private function shouldSkipDeliveryStatusUpdate(Order $order): bool
    {
        /**
         * Skip status update for Post AT carrier in case the parcel status is ANNOUNCED
         */
        if ($order->getSendCloudCarrierCode() === self::AUSTRIAN_POST_CARRIER_CODE &&
            $order->getSendCloudStatusId() === DeliveryStateMapper::SENDCLOUD_STATUS_ANNOUNCED) {
            Logger::logWarning('Skip status update for Post AT carrier in case the parcel status is ANNOUNCED. Order: ' . $order->getNumber());

            return true;
        }

        return false;
    }
}
