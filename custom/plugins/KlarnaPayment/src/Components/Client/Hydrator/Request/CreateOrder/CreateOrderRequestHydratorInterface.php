<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CreateOrder;

use KlarnaPayment\Components\Client\Request\CreateOrderRequest;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface CreateOrderRequestHydratorInterface
{
    public function hydrate(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $context): CreateOrderRequest;
}
