<?php

namespace Sendcloud\Shipping\Service\Utility;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Sendcloud\Shipping\Migration\Migration1572012839CreateConfigsTable;
use Sendcloud\Shipping\Migration\Migration1572012863CreateProcessesTable;
use Sendcloud\Shipping\Migration\Migration1572012872CreateQueuesTable;
use Sendcloud\Shipping\Migration\Migration1573059308CreateShipmentsTable;
use Sendcloud\Shipping\Migration\Migration1574260096CreateServicePointsTable;

/**
 * Class DatabaseHandler
 *
 * @package Sendcloud\Shipping\Service\Utility
 */
class DatabaseHandler
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * DatabaseHandler constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function removeSendCloudTables(): void
    {
        $this->removeTable(Migration1572012839CreateConfigsTable::CONFIGS_TABLE);
        $this->removeTable(Migration1572012872CreateQueuesTable::QUEUES_TABLE);
        $this->removeTable(Migration1572012863CreateProcessesTable::PROCESSES_TABLE);
        $this->removeTable(Migration1573059308CreateShipmentsTable::SHIPMENTS_TABLE);
        $this->removeTable(Migration1574260096CreateServicePointsTable::SERVICE_POINTS_TABLE);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function removeIntegrationConnectConnectTask(): void
    {
        $taskType = 'IntegrationConnectTask';
        $this->connection->delete(Migration1572012872CreateQueuesTable::QUEUES_TABLE, ['type' => $taskType]);
    }

    /**
     * @param string $tableName
     *
     * @return void
     * @throws Exception
     */
    private function removeTable(string $tableName): void
    {
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";
        $this->connection->executeUpdate($sql);
    }
}
