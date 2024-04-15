<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required;

use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\QueueItem;

/**
 * Interface TaskQueueStorage
 * @package Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required
 */
interface TaskQueueStorage
{
    const CLASS_NAME = __CLASS__;
    const SORT_ASC = 'ASC';
    const SORT_DESC = 'DESC';

    /**
     * Creates or updates given queue item. If queue item id is not set, new queue item will be created otherwise
     * update will be performed.
     *
     * @param QueueItem $queueItem Item to save
     * @param array $additionalWhere List of key/value pairs that must be satisfied upon saving queue item. Key is
     *                               queue item property and value is condition value for that property. Example for MySql storage:
     *                               $storage->save($queueItem, array('status' => 'queued')) should produce query
     *                               UPDATE queue_storage_table SET .... WHERE .... AND status => 'queued'
     *
     * @return int Id of saved queue item
     * @throws QueueItemSaveException if queue item could not be saved
     */
    public function save(QueueItem $queueItem, array $additionalWhere = array());

    /**
     * Finds queue item by id
     *
     * @param int $id Id of a queue item to find
     *
     * @return QueueItem|null Found queue item or null when queue item does not exist
     */
    public function find($id);

    /**
     * Finds latest queue item by type across all queues
     *
     * @param string $type Type of a queue item to find
     * @param string $context Task context restriction if provided search will be limited to given task context. Leave
     *     empty for search across all task contexts
     *
     * @return QueueItem|null Found queue item or null when queue item does not exist
     */
    public function findLatestByType($type, $context = '');

    /**
     * Finds list of earliest queued queue items per queue. Following list of criteria for searching must be satisfied:
     *      - Queue must be without already running queue items
     *      - For one queue only one (oldest queued) item should be returned
     *
     * @param int $limit Result set limit. By default max 10 earliest queue items will be returned
     *
     * @return \Sendcloud\Shipping\Core\Infrastructure\TaskExecution\QueueItem[] Found queue item list
     */
    public function findOldestQueuedItems($limit = 10);

    /**
     * Finds all queue items from all queues
     *
     * @param array $filterBy List of simple search filters, where key is queue item property and value is condition
     *      value for that property. Leave empty for unfiltered result.
     * @param array $sortBy List of sorting options where key is queue item property and value sort direction ("ASC" or
     *     "DESC"). Leave empty for default sorting.
     * @param int $start From which record index result set should start
     * @param int $limit Max number of records that should be returned (default is 10)
     *
     * @return \Sendcloud\Shipping\Core\Infrastructure\TaskExecution\QueueItem[] Found queue item list
     */
    public function findAll(array $filterBy = array(), array $sortBy = array(), $start = 0, $limit = 10);

    /**
     * Deletes queue items by provided type.
     *
     * @param string $type Type of a queue item to find.
     * @param string $context Task context restriction if provided search will be limited to given task context. Leave
     *                        empty for search across all task contexts.
     *
     * @return bool True on success, otherwise false.
     */
    public function deleteByType($type, $context = '');

    /**
     * Deletes queue items older than provided time limit with optional additional simple filters  and limit
     *
     * @param \DateTime $timeBefore Time boundary for delete operation. All queue items older than this time
     *      should be removed
     * @param array $filterBy List of simple search filters, where key is queue item property and value is condition
     *      value for that property. Leave empty for unfiltered removal.
     * @param array $excludeTypes List of queue item types to be excluded from the delete operation
     * @param int $limit How many queue items should be removed at most
     *
     * @return int Count of actually removed queue items
     */
    public function deleteOldItemsBy(\DateTime $timeBefore, array $filterBy = array(), array $excludeTypes = array(), $limit = 1000);

}
