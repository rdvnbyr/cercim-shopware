<?php declare(strict_types=1);

namespace Sendcloud\Shipping\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1572012839CreateConfigsTable
 *
 * @package Sendcloud\Shipping\Migration
 */
class Migration1572012839CreateConfigsTable extends MigrationStep
{
    public const CONFIGS_TABLE = 'sendcloud_configs';

    /**
     * @inheritDoc
     * @return int
     */
    public function getCreationTimestamp(): int
    {
        return 1572012839;
    }

    /**
     * @param Connection $connection
     *
     * @return void
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . self::CONFIGS_TABLE . '` (
            `id` BIGINT unsigned NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(255),
            `value` MEDIUMTEXT,
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
        $sql = 'DROP TABLE IF EXISTS `' . self::CONFIGS_TABLE . '`';
        $connection->executeUpdate($sql);
    }
}
