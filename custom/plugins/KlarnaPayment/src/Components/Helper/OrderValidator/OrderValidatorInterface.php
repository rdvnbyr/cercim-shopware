<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\OrderValidator;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;

interface OrderValidatorInterface
{
    public function isKlarnaOrder(OrderEntity $orderEntity): bool;

    public function validateAndInitLineItemsHash(OrderEntity $orderEntity, Context $context): bool;

    public function validateAndInitOrderAddressHash(OrderEntity $orderEntity, Context $context): bool;
}
