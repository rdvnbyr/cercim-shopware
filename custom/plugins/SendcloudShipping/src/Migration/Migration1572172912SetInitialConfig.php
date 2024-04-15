<?php declare(strict_types=1);

namespace Sendcloud\Shipping\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1572172912SetInitialConfig
 *
 * @package Sendcloud\Shipping\Migration
 */
class Migration1572172912SetInitialConfig extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1572172912;
    }

    /**
     * @param Connection $connection
     *
     * @return void
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $this->setTaskRunnerStatus($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    /**
     * @param Connection $connection
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    private function setTaskRunnerStatus(Connection $connection): void
    {
        $values = json_encode(['guid' => '', 'timestamp' => null]);
        $connection->insert('sendcloud_configs', ['`key`' => 'SENDCLOUD_TASK_RUNNER_STATUS', '`value`' => $values]);
    }
}
