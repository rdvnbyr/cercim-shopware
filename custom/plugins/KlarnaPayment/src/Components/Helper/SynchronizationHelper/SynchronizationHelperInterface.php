<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\SynchronizationHelper;

use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

interface SynchronizationHelperInterface
{
    public function syncBillingAddress(
        AsyncPaymentTransactionStruct $transaction,
        string $klarnaOrderId,
        SalesChannelContext $salesChannelContext
    ): void;
}
