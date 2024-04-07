<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper;

use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtension;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtensionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;

class OrderHashDeterminer
{
    public static function getOrderAddressHash(OrderEntity $orderEntity): ?string
    {
        $klarnaExtension = $orderEntity->getExtension(OrderExtension::EXTENSION_NAME);

        if ($klarnaExtension instanceof OrderExtensionEntity && !empty($klarnaExtension->getOrderAddressHash())) {
            return $klarnaExtension->getOrderAddressHash();
        }

        $customFields = $orderEntity->getCustomFields() ?? [];

        return $customFields['klarna_order_address_hash'] ?? null;
    }

    public static function getOrderCartHash(OrderEntity $orderEntity): ?string
    {
        $klarnaExtension = $orderEntity->getExtension(OrderExtension::EXTENSION_NAME);

        if ($klarnaExtension instanceof OrderExtensionEntity && !empty($klarnaExtension->getOrderCartHash())) {
            return $klarnaExtension->getOrderCartHash();
        }

        $customFields = $orderEntity->getCustomFields() ?? [];

        return $customFields['klarna_order_cart_hash'] ?? null;
    }

    public static function getOrderCartHashVersion(OrderEntity $orderEntity): ?int
    {
        $klarnaExtension = $orderEntity->getExtension(OrderExtension::EXTENSION_NAME);

        if ($klarnaExtension instanceof OrderExtensionEntity && !empty($klarnaExtension->getOrderCartHashVersion())) {
            return $klarnaExtension->getOrderCartHashVersion();
        }

        $customFields = $orderEntity->getCustomFields() ?? [];

        if (!empty($customFields['klarna_order_cart_hash_version'])) {
            return (int) $customFields['klarna_order_cart_hash_version'];
        }

        return null;
    }
}
