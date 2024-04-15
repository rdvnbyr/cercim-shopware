<?php declare(strict_types=1);

namespace Sendcloud\Shipping\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1602017170UpdateQueuesTable extends MigrationStep
{
    public const QUEUES_TABLE = 'sendcloud_queues';

    /**
     * @inheritDoc
     *
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1602017170;
    }

    /**
     * @param Connection $connection
     *
     * @return void
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sql = 'ALTER TABLE `' . self::QUEUES_TABLE . '` 
            CHANGE COLUMN `serializedTask` `serializedTask` MEDIUMBLOB NOT NULL;';

        $connection->executeUpdate($sql);
    }

    /**
     * @param Connection $connection
     * @return void
     */
    public function updateDestructive(Connection $connection): void
    {
        // No need for update destructive
    }
}
