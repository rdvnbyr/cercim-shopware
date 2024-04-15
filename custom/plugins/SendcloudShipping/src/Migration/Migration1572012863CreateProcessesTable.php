<?php declare(strict_types=1);

namespace Sendcloud\Shipping\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1572012863CreateProcessesTable
 *
 * @package Sendcloud\Shipping\Migration
 */
class Migration1572012863CreateProcessesTable extends MigrationStep
{
    public const PROCESSES_TABLE = 'sendcloud_processes';

    /**
     * @inheritDoc
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1572012863;
    }

    /**
     * @param Connection $connection
     *
     * @return void
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . self::PROCESSES_TABLE . '` (
            `id` BINARY(16) NOT NULL,
            `guid` VARCHAR(50) NOT NULL,
            `runner` VARCHAR(500) NOT NULL,
            PRIMARY KEY (`id`)
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
        $sql = 'DROP TABLE IF EXISTS `' . self::PROCESSES_TABLE . '`';
        $connection->executeUpdate($sql);
    }
}
