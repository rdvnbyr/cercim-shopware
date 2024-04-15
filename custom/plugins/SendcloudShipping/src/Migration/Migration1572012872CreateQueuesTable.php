<?php declare(strict_types=1);

namespace Sendcloud\Shipping\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1572012872CreateQueuesTable
 *
 * @package Sendcloud\Shipping\Migration
 */
class Migration1572012872CreateQueuesTable extends MigrationStep
{
    public const QUEUES_TABLE = 'sendcloud_queues';

    /**
     * @inheritDoc
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1572012872;
    }

    /**
     * @param Connection $connection
     *
     * @return void
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . self::QUEUES_TABLE . '` (
            `id` BINARY(16) NOT NULL,
            `internalId` BIGINT unsigned NOT NULL AUTO_INCREMENT,
            `status` VARCHAR(30) NOT NULL,
            `type` VARCHAR(100) NOT NULL,
            `queueName` VARCHAR(50) NOT NULL,
            `progress` INT(11) NOT NULL DEFAULT 0,
            `lastExecutionProgress` INT(11) DEFAULT 0,
            `retries` INT(11) NOT NULL DEFAULT 0,
            `failureDescription` VARCHAR(255),
            `serializedTask` BLOB NOT NULL,
            `createTimestamp` INT(11),
            `queueTimestamp` INT(11),
            `lastUpdateTimestamp` INT(11),
            `startTimestamp` INT(11),
            `finishTimestamp` INT(11),
            `failTimestamp` INT(11),
            PRIMARY KEY (`id`),
            UNIQUE (`internalId`)
        ) 
        ENGINE = InnoDB
        DEFAULT CHARSET = utf8
        COLLATE = utf8_general_ci;';

        $connection->executeUpdate($sql);
    }

    /**
     * @param Connection $connection
     *
     * @return void
     * @throws Exception
     */
    public function updateDestructive(Connection $connection): void
    {
        $sql = 'DROP TABLE IF EXISTS `' . self::QUEUES_TABLE . '`';
        $connection->executeUpdate($sql);
    }
}
