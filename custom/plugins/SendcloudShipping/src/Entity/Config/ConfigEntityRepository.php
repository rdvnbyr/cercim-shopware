<?php

namespace Sendcloud\Shipping\Entity\Config;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Sendcloud\Shipping\Migration\Migration1572012839CreateConfigsTable;

/**
 * Class ConfigEntityRepository
 *
 * @package Sendcloud\Shipping\Entity\Config
 */
class ConfigEntityRepository
{
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var string
     */
    private $tableName;

    /**
     * ConfigEntityRepository constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->tableName = Migration1572012839CreateConfigsTable::CONFIGS_TABLE;
    }

    /**
     * @param string $key
     * @param $value
     * @return bool
     * @throws Exception
     */
    public function updateValue(string $key, $value): bool
    {
        $existingConfiguration = $this->getConfigByKey($key);
        $data = ['`key`' => $key, '`value`' => $value];
        if (!empty($existingConfiguration)) {
            $affectedRaws = $this->connection->update($this->tableName, $data, ['`id`' => $existingConfiguration['id']]);
        } else {
            $affectedRaws = $this->connection->insert($this->tableName, $data);
        }

        return ($affectedRaws > 0);
    }

    /**
     * @param string $key
     * @return mixed|null
     * @throws Exception
     */
    public function getValue(string $key)
    {
        $config = $this->getConfigByKey($key);

        return !empty($config['value']) ? $config['value'] : null;
    }

    /**
     * @param string $key
     * @return string|null
     * @throws Exception
     */
    public function getCustomsField(string $key): ?string
    {
        $config = $this->getConfigByKey($key);

        if (empty($config)) {
            return null;
        }

        return $config['value'] ?? '';
    }

    /**
     * Returns config entity by key
     *
     * @param string $key
     *
     * @return array|bool
     * @throws Exception
     */
    private function getConfigByKey(string $key)
    {
        $sql = "SELECT * FROM `{$this->tableName}` WHERE `key` = ?";

        return $this->connection->fetchAssociative($sql, [$key]);
    }
}
