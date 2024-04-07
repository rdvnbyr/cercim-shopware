<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\OrderValidator;

use KlarnaPayment\Components\Client\ClientInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\GetOrder\GetOrderRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateAddress\UpdateAddressRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateOrder\UpdateOrderRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Response\GetOrder\GetOrderResponseHydratorInterface;
use KlarnaPayment\Components\Exception\GetKlarnaOrderException;
use KlarnaPayment\Components\Exception\KlarnaOrderIdNotFoundException;
use KlarnaPayment\Components\Helper\OrderHashDeterminer;
use KlarnaPayment\Components\Helper\OrderHashUpdater;
use KlarnaPayment\Components\Helper\RequestHasherInterface;
use KlarnaPayment\Components\PaymentHandler\AbstractKlarnaPaymentHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class OrderValidator implements OrderValidatorInterface
{
    /** @var UpdateAddressRequestHydratorInterface */
    private $updateAddressRequestHydrator;

    /** @var UpdateOrderRequestHydratorInterface */
    private $updateOrderRequestHydrator;

    /** @var ClientInterface */
    private $client;

    /** @var RequestHasherInterface */
    private $updateOrderRequestHasher;

    /** @var RequestHasherInterface */
    private $updateAddressRequestHasher;

    /** @var OrderHashUpdater */
    private $orderHashUpdater;

    /** @var GetOrderRequestHydratorInterface */
    private $getOrderRequestHydrator;

    /** @var GetOrderResponseHydratorInterface */
    private $getOrderResponseHydrator;

    /**
     * This is a runtime cache to save requests for the same order.
     *
     * @var array<string,bool>
     */
    private $cachedCaputuredStates = [];

    public function __construct(
        UpdateAddressRequestHydratorInterface $updateAddressRequestHydrator,
        UpdateOrderRequestHydratorInterface $updateOrderRequestHydrator,
        ClientInterface $client,
        RequestHasherInterface $updateOrderRequestHasher,
        RequestHasherInterface $updateAddressRequestHasher,
        OrderHashUpdater $orderHashUpdater,
        GetOrderRequestHydratorInterface $getOrderRequestHydrator,
        GetOrderResponseHydratorInterface $getOrderResponseHydrator
    ) {
        $this->updateAddressRequestHydrator = $updateAddressRequestHydrator;
        $this->updateOrderRequestHydrator   = $updateOrderRequestHydrator;
        $this->client                       = $client;
        $this->updateOrderRequestHasher     = $updateOrderRequestHasher;
        $this->updateAddressRequestHasher   = $updateAddressRequestHasher;
        $this->orderHashUpdater             = $orderHashUpdater;
        $this->getOrderRequestHydrator      = $getOrderRequestHydrator;
        $this->getOrderResponseHydrator     = $getOrderResponseHydrator;
    }

    public function isKlarnaOrder(OrderEntity $orderEntity): bool
    {
        return !empty($this->getKlarnaOrderId($orderEntity));
    }

    public function validateAndInitLineItemsHash(OrderEntity $orderEntity, Context $context): bool
    {
        $request     = $this->updateOrderRequestHydrator->hydrate($orderEntity, $context);
        $currentHash = OrderHashDeterminer::getOrderCartHash($orderEntity);

        if (!empty($currentHash)) {
            $hashVersion = OrderHashDeterminer::getOrderCartHashVersion($orderEntity) ?? AbstractKlarnaPaymentHandler::CART_HASH_DEFAULT_VERSION;
            $hash        = $this->updateOrderRequestHasher->getHash($request, $hashVersion);

            if ($hash === $currentHash) {
                if ($hashVersion !== AbstractKlarnaPaymentHandler::CART_HASH_CURRENT_VERSION) {
                    $this->orderHashUpdater->updateOrderCartHash($request, $orderEntity, $context);
                }

                return true;
            }
        }

        if ($this->hasCapturedAmount($orderEntity, $context)) {
            $this->orderHashUpdater->updateOrderCartHash($request, $orderEntity, $context);

            return true;
        }

        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() === 204) {
            $this->orderHashUpdater->updateOrderCartHash($request, $orderEntity, $context);

            return true;
        }

        return false;
    }

    public function validateAndInitOrderAddressHash(OrderEntity $orderEntity, Context $context): bool
    {
        $request     = $this->updateAddressRequestHydrator->hydrate($orderEntity, $context);
        $hash        = $this->updateAddressRequestHasher->getHash($request);
        $currentHash = OrderHashDeterminer::getOrderAddressHash($orderEntity);

        if (!empty($currentHash) && $hash === $currentHash) {
            return true;
        }

        $request->assign(['billingAddress' => null]);
        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() === 204) {
            $this->orderHashUpdater->saveOrderAddressHash($hash, $orderEntity, $context);

            return true;
        }

        return false;
    }

    private function getKlarnaOrderId(OrderEntity $orderEntity): ?string
    {
        if ($orderEntity->getTransactions() === null) {
            return null;
        }

        foreach ($orderEntity->getTransactions() as $transaction) {
            if ($transaction->getStateMachineState() === null) {
                continue;
            }

            if ($transaction->getStateMachineState()->getTechnicalName() === OrderTransactionStates::STATE_CANCELLED) {
                continue;
            }

            if (empty($transaction->getCustomFields()['klarna_order_id'])) {
                continue;
            }

            return $transaction->getCustomFields()['klarna_order_id'];
        }

        return null;
    }

    private function hasCapturedAmount(OrderEntity $orderEntity, Context $context): bool
    {
        $klarnaOrderId = $this->getKlarnaOrderId($orderEntity);

        if (empty($klarnaOrderId)) {
            throw new KlarnaOrderIdNotFoundException();
        }

        if (!array_key_exists($klarnaOrderId, $this->cachedCaputuredStates)) {
            $this->cachedCaputuredStates[$klarnaOrderId] = $this->hasCapturedAmountRequest($klarnaOrderId, $orderEntity, $context);
        }

        return $this->cachedCaputuredStates[$klarnaOrderId];
    }

    private function hasCapturedAmountRequest(string $klarnaOrderId, OrderEntity $orderEntity, Context $context): bool
    {
        $dataBag = new RequestDataBag();
        $dataBag->add([
            'klarna_order_id' => $klarnaOrderId,
            'salesChannel'    => $orderEntity->getSalesChannelId(),
        ]);

        $request = $this->getOrderRequestHydrator->hydrate($dataBag);

        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() !== 200) {
            throw new GetKlarnaOrderException((string) $response->getHttpStatus(), $response->getResponse());
        }

        $order = $this->getOrderResponseHydrator->hydrate($response, $context);

        return $order->getCapturedAmount() > 0;
    }
}
