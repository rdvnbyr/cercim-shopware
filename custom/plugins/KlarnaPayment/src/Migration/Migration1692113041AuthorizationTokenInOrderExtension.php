<?php

declare(strict_types=1);

namespace KlarnaPayment\Migration;

use Doctrine\DBAL\Connection;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DbalConnectionHelper;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1692113041AuthorizationTokenInOrderExtension extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1692113041;
    }

    public function update(Connection $connection): void
    {
        DbalConnectionHelper::exec($connection, ' ALTER TABLE `klarna_order_extension`
        ADD `authorization_token` VARCHAR(128) NULL AFTER `order_cart_hash_version`;');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
