<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\StateHelper\Capture;

use KlarnaPayment\Components\Client\Client;
use KlarnaPayment\Components\Client\Hydrator\Request\CreateCapture\CreateCaptureRequestHydratorInterface;
use KlarnaPayment\Components\Helper\StateHelper\StateData\StateDataHelperInterface;
use KlarnaPayment\Core\Framework\ContextScope;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;

class CaptureStateHelper implements CaptureStateHelperInterface
{
    /** @var CreateCaptureRequestHydratorInterface */
    private $captureRequestHydrator;

    /** @var OrderTransactionStateHandler */
    private $transactionStateHandler;

    /** @var StateDataHelperInterface */
    private $stateDataHelper;

    /** @var LoggerInterface */
    private $logger;

    /** @var Client */
    private $client;

    public function __construct(
        CreateCaptureRequestHydratorInterface $captureRequestHydrator,
        OrderTransactionStateHandler $transactionStateHandler,
        StateDataHelperInterface $stateDataHelper,
        LoggerInterface $logger,
        Client $client
    ) {
        $this->captureRequestHydrator  = $captureRequestHydrator;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->stateDataHelper         = $stateDataHelper;
        $this->logger                  = $logger;
        $this->client                  = $client;
    }

    public function processOrderCapture(OrderEntity $order, Context $context): void
    {
        foreach ($this->stateDataHelper->getValidTransactions($order) as $transaction) {
            $this->captureTransaction($transaction, $order, $context);
        }
    }

    private function captureTransaction(OrderTransactionEntity $transaction, OrderEntity $order, Context $context): void
    {
        if ($order->getCurrency() === null) {
            return;
        }

        $customFields = $transaction->getCustomFields();

        if (empty($customFields['klarna_order_id'])) {
            return;
        }

        $klarnaOrder = $this->stateDataHelper->getKlarnaOrder(
            $customFields['klarna_order_id'],
            $order->getSalesChannelId(),
            $context
        );

        if (empty($klarnaOrder)) {
            return;
        }

        $captureAmount = (int) $klarnaOrder['remaining_authorized_amount'];

        if ($captureAmount <= 0) {
            return;
        }

        $dataBag = $this->stateDataHelper->prepareDataBag(
            $order,
            $klarnaOrder,
            $order->getSalesChannelId()
        );

        $dataBag->set('captureAmount', $captureAmount / 100);

        $request  = $this->captureRequestHydrator->hydrate($dataBag, $context);
        $response = $this->client->request($request, $context);

        if ($response->getHttpStatus() !== 201) {
            $this->logger->notice('transaction was not captured automatically', [
                'orderNumber'   => $order->getOrderNumber(),
                'transactionId' => $transaction->getId(),
            ]);

            return;
        }

        try {
            $context->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($transaction): void {
                if (method_exists($this->transactionStateHandler, 'paid')) {
                    $this->transactionStateHandler->paid($transaction->getId(), $context);
                } else {
                    /** @phpstan-ignore-next-line */
                    $this->transactionStateHandler->pay($transaction->getId(), $context);
                }
            });
        } catch (IllegalTransitionException $exception) {
            $this->logger->notice($exception->getMessage(), $exception->getParameters());
        }
    }
}
