<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\PaymentHandler;

use KlarnaPayment\Components\Client\Response\GenericResponse;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\HttpFoundation\RequestStack;

abstract class AbstractKlarnaPaymentHandler
{
    public const CART_HASH_DEFAULT_VERSION = 1;
    public const CART_HASH_CURRENT_VERSION = 2;

    public const FRAUD_STATUS_REJECTED      = 'REJECTED';
    public const FRAUD_STATUS_ACCEPTED      = 'ACCEPTED';
    public const FRAUD_STATUS_STOPPED       = 'STOPPED';
    public const IS_KLARNA_EXPRESS_CHECKOUT = 'isKlarnaExpressCheckout';

    /** @var EntityRepository */
    protected $transactionRepository;

    /** @var RequestStack */
    protected $requestStack;

    protected function saveTransactionData(OrderTransactionEntity $transaction, GenericResponse $response, Context $context, string $authorizationToken): void
    {
        $customFields = $transaction->getCustomFields() ?? [];

        $customFields = array_merge($customFields, [
            'klarna_order_id'            => $response->getResponse()['order_id'],
            'klarna_fraud_status'        => $response->getResponse()['fraud_status'],
            'klarna_authorization_token' => $authorizationToken,
        ]);

        $update = [
            'id'           => $transaction->getId(),
            'customFields' => $customFields,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($update): void {
            $this->transactionRepository->update([$update], $context);
        });
    }

    protected function fetchRequestData(): RequestDataBag
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            throw new \LogicException('missing current request');
        }

        return new RequestDataBag($request->request->all());
    }
}
