<?php declare(strict_types=1);

namespace Sendcloud\Shipping\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1574260096CreateServicePointsTable
 *
 * @package Sendcloud\Shipping\Migration
 */
class Migration1574260096CreateServicePointsTable extends MigrationStep
{
    public const SERVICE_POINTS_TABLE = 'sendcloud_servicepoints';

    public function getCreationTimestamp(): int
    {
        return 1574260096;
    }

    /**
     * @param Connection $connection
     *
     * @return void
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . self::SERVICE_POINTS_TABLE . '` (
            `id` BINARY(16) NOT NULL,
            `customerNumber` VARCHAR(50) NOT NULL,
            `servicePointInfo` MEDIUMTEXT,
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
        $sql = 'DROP TABLE IF EXISTS `' . self::SERVICE_POINTS_TABLE . '`';
        $connection->executeUpdate($sql);
    }
}
