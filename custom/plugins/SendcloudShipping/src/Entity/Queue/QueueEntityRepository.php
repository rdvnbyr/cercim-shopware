<?php

namespace Sendcloud\Shipping\Entity\Queue;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\QueueItem;
use Sendcloud\Shipping\Migration\Migration1572012872CreateQueuesTable;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Class QueueEntityRepository
 *
 * @package Sendcloud\Shipping\Entity\Queue
 */
class QueueEntityRepository
{
    /**
     * @var EntityRepository
     */
    private $baseRepository;
    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var string
     */
    private $tableName;

    /**
     * QueueEntityRepository constructor.
     *
     * @param EntityRepository $baseRepository
     * @param Connection $connection
     */
    public function __construct(EntityRepository $baseRepository, Connection $connection)
    {
        $this->baseRepository = $baseRepository;
        $this->connection = $connection;
        $this->tableName = Migration1572012872CreateQueuesTable::QUEUES_TABLE;
    }

    /**
     * Removes queue item by type
     *
     * @param string $type
     *
     * @return int
     * @throws DBALException
     * @throws InvalidArgumentException
     */
    public function deleteByType(string $type): int
    {
        return $this->connection->delete($this->tableName, ['`type`' => $type]);
    }

    public function deleteOldItemsBy(
        \DateTime $timeBefore,
        array $filterBy = array(),
        array $excludeTypes = array(),
                  $limit = 1000
    ): int {
        $criteria = $this->buildCriteria(null, $filterBy, ['internalId' => FieldSorting::ASCENDING], $limit);
        $criteria->addFilter(
            new RangeFilter('createTimestamp', [
                RangeFilter::LTE => $timeBefore->getTimestamp()
            ])
        );

        if (!empty($excludeTypes)) {
            $criteria->addFilter(
                new NotFilter(NotFilter::CONNECTION_AND, [
                    new EqualsAnyFilter('type', $excludeTypes),
                ])
            );
        }

        $context = Context::createDefaultContext();
        $oldItems = $this->baseRepository->searchIds($criteria, $context);

        if ($oldItems->getTotal() === 0) {
            return 0;
        }

        $keys = array_map(function ($id) {
            return ['id' => $id];
        }, $oldItems->getIds());

        $this->baseRepository->delete($keys, $context);

        return $oldItems->getTotal();
    }

    /**
     * Creates or update queue item
     *
     * @param string|null $id
     * @param array $data
     * @param array $additionalConditions
     *
     * @return string|null
     * @throws InconsistentCriteriaIdsException
     */
    public function save(?string $id, array $data, array $additionalConditions): ?string
    {
        $context = Context::createDefaultContext();
        /** @var QueueEntity $queueEntity */
        if ($id) {
            $queueEntity = $this->baseRepository->search($this->buildCriteria($id, $additionalConditions), $context)->first();
            if ($queueEntity) {
                $updateData = array_merge(['id' => $queueEntity->getId()], $data);
                $this->baseRepository->update([$updateData], $context);

                return $queueEntity->getId();
            }
        }

        $event = $this->baseRepository->create([$data], $context)->getEventByEntityName(QueueEntity::class);

        return $event ? $event->getIds()[0] : null;
    }

    /**
     * Returns QueueEntity by its id
     *
     * @param string $id
     *
     * @return QueueEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function getById(string $id): ?QueueEntity
    {
        return $this->baseRepository->search(new Criteria([$id]), Context::createDefaultContext())->first();
    }

    /**
     * Returns queue item with latest queueTimestamp
     *
     * @param string $type
     *
     * @return QueueEntity|null
     * @throws InconsistentCriteriaIdsException
     */
    public function findLatestByType(string $type): ?QueueEntity
    {
        $filter = ['type' => $type];
        $sortBy = ['queueTimestamp' => FieldSorting::DESCENDING];

        return $this->baseRepository->search($this->buildCriteria(null, $filter, $sortBy), Context::createDefaultContext())->first();
    }

    /**
     * Finds list of earliest queued queue items per queue.
     *
     * @param int $limit
     *
     * @return mixed[]
     * @throws DBALException
     */
    public function findOldestQueuedEntities(int $limit = 10): array
    {
        $runningQueuesQuery = "SELECT DISTINCT queueName 
                               FROM {$this->tableName} qi2 
                               WHERE qi2.status='" . QueueItem::IN_PROGRESS . "'";

        $query = "SELECT * 
                  FROM (
                    SELECT queueName, min(internalId) AS internalId
                    FROM {$this->tableName} AS t
                    WHERE t.status='" . QueueItem::QUEUED . "' AND t.queueName NOT IN ({$runningQueuesQuery})
                    GROUP BY queueName LIMIT {$limit}
                  ) AS queueView
                  INNER JOIN {$this->tableName} AS qi ON queueView.queueName=qi.queueName and queueView.internalId=qi.internalId";

        return $this->connection->executeQuery($query)->fetchAll();
    }

    /**
     * Returns all queue items which satisfy given condition
     *
     * @param array $filterBy
     * @param array $sortBy
     * @param int $start
     * @param int $limit
     *
     * @return EntityCollection
     * @throws InconsistentCriteriaIdsException
     */
    public function findAll(array $filterBy = [], array $sortBy = [], $start = 0, $limit = 10): EntityCollection
    {
        return $this->baseRepository
            ->search($this->buildCriteria(null, $filterBy, $sortBy, $limit, $start), Context::createDefaultContext())
            ->getEntities();
    }

    /**
     * Count all queue items which satisfy given condition
     *
     * @param array $filterBy
     *
     * @return int
     */
    public function countAll(array $filterBy = []): int
    {
        $criteria = new Criteria([]);
        foreach ($filterBy as $key => $value) {
            $criteria->addFilter(new EqualsFilter($key, $value));
        }

        return $this->baseRepository
            ->search($criteria, Context::createDefaultContext())
            ->count();
    }

    /**
     * Creates search criteria
     *
     * @param string|null $id
     * @param array $additionalConditions
     *
     * @param array $sorting
     * @param int $limit
     * @param int $offset
     *
     * @return Criteria
     * @throws InconsistentCriteriaIdsException
     */
    private function buildCriteria(
        ?string $id,
        array $additionalConditions,
        array $sorting = [],
        int $limit = 50,
        int $offset = 0
    ): Criteria
    {
        $ids = $id ? [$id] : null;
        $criteria = new Criteria($ids);
        foreach ($additionalConditions as $key => $value) {
            $criteria->addFilter(new EqualsFilter($key, $value));
        }

        foreach ($sorting as $field => $direction) {
            $criteria->addSorting(new FieldSorting($field, $direction));
        }

        $criteria->setLimit($limit);
        $criteria->setOffset($offset);

        return $criteria;
    }
}
