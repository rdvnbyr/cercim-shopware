<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\SynchronizationHelper;

use KlarnaPayment\Components\Client\ClientInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\GetOrder\GetOrderRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Response\GetOrder\GetOrderResponseHydratorInterface;
use KlarnaPayment\Components\Client\Response\GetOrderResponse;
use KlarnaPayment\Components\Client\Struct\Address;
use KlarnaPayment\Core\Framework\ContextScope;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class SynchronizationHelper implements SynchronizationHelperInterface
{
    /** @var EntityRepository */
    private $orderRepository;

    /** @var EntityRepository */
    private $orderAddressRepository;

    /** @var EntityRepository */
    private $orderDeliveryRepository;

    /** @var GetOrderRequestHydratorInterface */
    private $getOrderRequestHydrator;

    /** @var GetOrderResponseHydratorInterface */
    private $getOrderResponseHydrator;

    /** @var ClientInterface */
    private $client;

    public function __construct(
        EntityRepository $orderRepository,
        EntityRepository $orderAddressRepository,
        EntityRepository $orderDeliveryRepository,
        GetOrderRequestHydratorInterface $getOrderRequestHydrator,
        GetOrderResponseHydratorInterface $getOrderResponseHydrator,
        ClientInterface $client
    ) {
        $this->orderRepository          = $orderRepository;
        $this->orderAddressRepository   = $orderAddressRepository;
        $this->orderDeliveryRepository  = $orderDeliveryRepository;
        $this->getOrderRequestHydrator  = $getOrderRequestHydrator;
        $this->getOrderResponseHydrator = $getOrderResponseHydrator;
        $this->client                   = $client;
    }

    public function syncBillingAddress(
        AsyncPaymentTransactionStruct $transaction,
        string $klarnaOrderId,
        SalesChannelContext $salesChannelContext
    ): void {
        $salesChannelId = method_exists($salesChannelContext, 'getSalesChannelId')
            ? $salesChannelContext->getSalesChannelId()
            : $salesChannelContext->getSalesChannel()->getId();

        $getOrderRequest = new RequestDataBag([
            'klarna_order_id' => $klarnaOrderId,
            'salesChannel'    => $salesChannelId,
        ]);

        $request  = $this->getOrderRequestHydrator->hydrate($getOrderRequest);
        $response = $this->client->request($request, $salesChannelContext->getContext());

        $order = $this->getOrderResponseHydrator->hydrate($response, $salesChannelContext->getContext());

        $this->ensureBillingShippingUniqueAddresses($transaction, $order, $salesChannelContext);

        $data = [
            $this->getOrderAddressData(
                $transaction->getOrder()->getBillingAddressId(),
                $transaction->getOrder()->getId(),
                $order->getBillingAddress()
            ),
        ];

        $salesChannelContext->getContext()->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($data): void {
            $this->orderAddressRepository->upsert($data, $context);
        });
    }

    private function getOrderAddressData(
        string $shopwareOrderAddressId,
        string $orderId,
        Address $address
    ): array {
        return [
            'id'           => $shopwareOrderAddressId,
            'firstName'    => $address->getFirstName(),
            'lastName'     => $address->getLastName(),
            'street'       => $address->getStreetAddress(),
            'zipcode'      => $address->getPostalCode(),
            'city'         => $address->getCity(),
            'company'      => $address->getCompanyName(),
            'orderId'      => $orderId,
            'countryId'    => $address->getCountry(),
            'salutationId' => $address->getSalutation(),
        ];
    }

    /**
     * duplicate billing OrderAddress if it is the same as the shipping OrderAddress
     * and save the duplicated billing OrderAddress to the latest delivery
     */
    private function ensureBillingShippingUniqueAddresses(
        AsyncPaymentTransactionStruct $transaction,
        GetOrderResponse $order,
        SalesChannelContext $salesChannelContext
    ): void {
        $shopwareOrder = $this->reloadOrderWithDeliveries($transaction, $salesChannelContext->getContext());

        if (!$shopwareOrder instanceof OrderEntity) {
            return;
        }

        $deliveries = $shopwareOrder->getDeliveries();

        if (!$deliveries instanceof OrderDeliveryCollection) {
            return;
        }

        $latestDelivery = $deliveries->first();

        if (!$latestDelivery instanceof OrderDeliveryEntity) {
            return;
        }

        if ($transaction->getOrder()->getBillingAddressId() !== $latestDelivery->getShippingOrderAddressId()) {
            return;
        }

        $shippingAddress = $latestDelivery->getShippingOrderAddress();

        if (!$shippingAddress instanceof OrderAddressEntity) {
            return;
        }

        $addresses = $shopwareOrder->getAddresses();

        if (!$addresses instanceof OrderAddressCollection) {
            return;
        }

        $actualBillingAddress = $addresses->get($shopwareOrder->getBillingAddressId());

        if (!$actualBillingAddress instanceof OrderAddressEntity) {
            return;
        }

        if (!$this->syncAddressHasMajorDifference($order->getBillingAddress(), $shippingAddress)) {
            return;
        }

        $duplicatedBillingAddressId = Uuid::randomHex();

        $this->orderAddressRepository->clone($actualBillingAddress->getId(), $salesChannelContext->getContext(), $duplicatedBillingAddressId);

        $deliveryUpdate = [
            'id'                     => $latestDelivery->getId(),
            'shippingOrderAddressId' => $duplicatedBillingAddressId,
        ];

        $salesChannelContext->getContext()->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($deliveryUpdate): void {
            $this->orderDeliveryRepository->upsert([$deliveryUpdate], $context);
        });
    }

    private function syncAddressHasMajorDifference(Address $klarnaAddress, OrderAddressEntity $orderAddress): bool
    {
        return $klarnaAddress->getCompanyName() !== $orderAddress->getCompany()
            || $klarnaAddress->getSalutation() !== $this->getCustomerSalutation($orderAddress)
            || $klarnaAddress->getFirstName() !== $orderAddress->getFirstName()
            || $klarnaAddress->getLastName() !== $orderAddress->getLastName()
            || $klarnaAddress->getPostalCode() !== $orderAddress->getZipcode()
            || $klarnaAddress->getStreetAddress() !== $orderAddress->getStreet()
            || $klarnaAddress->getStreetAddress2() !== $this->getStreetAddress2($orderAddress)
            || $klarnaAddress->getCity() !== $orderAddress->getCity()
            || $klarnaAddress->getCountry() !== $this->getCustomerCountry($orderAddress)
            || $klarnaAddress->getRegion() !== ($orderAddress->getCountryState() instanceof CountryStateEntity ? $orderAddress->getCountryState()->getShortCode() : null)
            || $klarnaAddress->getPhoneNumber() !== $orderAddress->getPhoneNumber();
    }

    private function reloadOrderWithDeliveries(AsyncPaymentTransactionStruct $transaction, Context $context): ?OrderEntity
    {
        $criteria = new Criteria([$transaction->getOrder()->getId()]);
        $criteria->addAssociation('addresses');
        $criteria->getAssociation('deliveries.shippingOrderAddress.salutation')->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        return $this->orderRepository->search($criteria, $context)->first();
    }

    private function getCustomerSalutation(OrderAddressEntity $customerAddress): string
    {
        $salutation = $customerAddress->getSalutation();

        if ($salutation === null || ($salutation->getTranslation('displayName') === null && $salutation->getDisplayName() === null)) {
            throw new \LogicException('missing order customer salutation');
        }

        return $salutation->getTranslation('displayName') ?? $salutation->getDisplayName();
    }

    private function getCustomerCountry(OrderAddressEntity $customerAddress): string
    {
        $country = $customerAddress->getCountry();

        if ($country === null || $country->getIso() === null) {
            throw new \LogicException('missing order customer country');
        }

        return $country->getIso();
    }

    private function getStreetAddress2(OrderAddressEntity $customerAddress): ?string
    {
        $streetAddress2 = $customerAddress->getAdditionalAddressLine1();

        if (!empty($customerAddress->getAdditionalAddressLine2())) {
            $streetAddress2 .= ' - ' . $customerAddress->getAdditionalAddressLine2();
        }

        return $streetAddress2;
    }
}
