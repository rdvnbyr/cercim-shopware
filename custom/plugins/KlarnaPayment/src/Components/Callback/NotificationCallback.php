<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Callback;

use KlarnaPayment\Components\Helper\StateHelper\Authorize\AuthorizeStateHelperInterface;
use KlarnaPayment\Components\Helper\StateHelper\Capture\CaptureStateHelperInterface;
use KlarnaPayment\Components\PaymentHandler\AbstractKlarnaPaymentHandler;
use KlarnaPayment\Components\Validator\OrderTransitionChangeValidator;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotificationCallback
{
    /** @var EntityRepository */
    private $transactionRepository;

    /** @var OrderTransactionStateHandler */
    private $stateHandler;

    /** @var OrderTransitionChangeValidator */
    private $orderStatusValidator;

    /** @var CaptureStateHelperInterface */
    private $captureStateHelper;

    /** @var AuthorizeStateHelperInterface */
    private $authorizeStateHelper;

    public function __construct(EntityRepository $transactionRepository, OrderTransactionStateHandler $stateHandler, OrderTransitionChangeValidator $orderStatusValidator, CaptureStateHelperInterface $captureStateHelper, AuthorizeStateHelperInterface $authorizeStateHelper)
    {
        $this->transactionRepository = $transactionRepository;
        $this->stateHandler          = $stateHandler;
        $this->orderStatusValidator  = $orderStatusValidator;
        $this->captureStateHelper    = $captureStateHelper;
        $this->authorizeStateHelper  = $authorizeStateHelper;
    }

    public function handle(string $transactionId, string $eventType, SalesChannelContext $context): void
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order.transactions');
        $criteria->addAssociation('order.currency');

        /** @var null|OrderTransactionEntity $transaction */
        $transaction = $this->transactionRepository->search($criteria, $context->getContext())->first();

        if ($transaction === null) {
            throw new NotFoundHttpException('Not found');
        }

        $fraudStatus = '';

        if (stripos($eventType, 'FRAUD_RISK_') !== false) {
            $fraudStatus = str_replace('FRAUD_RISK_', '', $eventType);
            $this->saveFraudStatus($transaction, $fraudStatus, $context->getContext());
        }

        $cancelStates = [
            strtolower(AbstractKlarnaPaymentHandler::FRAUD_STATUS_REJECTED),
            strtolower(AbstractKlarnaPaymentHandler::FRAUD_STATUS_STOPPED),
        ];

        if (in_array(strtolower($fraudStatus), $cancelStates, true)) {
            $this->stateHandler->cancel($transaction->getId(), $context->getContext());
        }

        $order = $transaction->getOrder();

        if ($order === null) {
            return;
        }

        $stateMachineState = $order->getStateMachineState();

        if ($stateMachineState === null) {
            return;
        }

        if (\method_exists($context, 'getSalesChannelId')) {
            $salesChannelId = $context->getSalesChannelId();
        } else {
            $salesChannelId = $context->getSalesChannel()->getId();
        }

        if (strtolower($fraudStatus) === strtolower(AbstractKlarnaPaymentHandler::FRAUD_STATUS_ACCEPTED)) {
            if ($this->orderStatusValidator->isAutomaticCapture(
                null,
                $stateMachineState->getTechnicalName(),
                $salesChannelId,
                $context->getContext()
            )) {
                $this->captureStateHelper->processOrderCapture($order, $context->getContext());
            } elseif ($stateMachineState->getTechnicalName() === OrderTransactionStates::STATE_OPEN) {
                $this->authorizeStateHelper->processOrderAuthorize($order, $context->getContext());
            }
        }
    }

    private function saveFraudStatus(OrderTransactionEntity $transaction, string $fraudStatus, Context $context): void
    {
        $customFields = $transaction->getCustomFields() ?? [];

        $customFields = array_merge($customFields, [
            'klarna_fraud_status' => $fraudStatus,
        ]);

        $update = [
            'id'           => $transaction->getId(),
            'customFields' => $customFields,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($update): void {
            $this->transactionRepository->update([$update], $context);
        });
    }
}
