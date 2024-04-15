<?php

namespace Sendcloud\Shipping\Core\Infrastructure\TaskExecution;

use Exception;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\Runnable;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class QueueItemStarter
 * @package Sendcloud\Shipping\Core\Infrastructure\TaskExecution
 */
class QueueItemStarter implements Runnable
{

    /** @var int Id of queue item to start */
    private $queueItemId;

    /** @var Queue */
    private $queue;

    /** @var Configuration */
    private $configService;

    /**
     * QueueItemStarter constructor.
     *
     * @param int $queueItemId
     */
    public function __construct($queueItemId)
    {
        $this->queueItemId = $queueItemId;
    }

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
        return serialize(array($this->queueItemId));
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     *
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     *
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        list($this->queueItemId) = unserialize($serialized);
    }

    /**
     * Starts runnable run logic
     *
     * @return void
     */
    public function run()
    {
        $queueItem = $this->fetchItem();

        if (empty($queueItem) || ($queueItem->getStatus() !== QueueItem::QUEUED)) {
            Logger::logWarning(
                'Fail to start task execution because task no longer exists or it is not in queued state anymore.',
                'Core',
                array(
                    'TaskId' => $this->getQueueItemId(),
                    'Status' => !empty($queueItem) ? $queueItem->getStatus() : 'unknown'
                )
            );
            return;
        }

        try {
            $this->getConfigService()->setContext($queueItem->getContext());
            $this->getQueueService()->start($queueItem);
            $this->getQueueService()->finish($queueItem);
        } catch (Exception $ex) {
            if (QueueItem::IN_PROGRESS === $queueItem->getStatus()) {
                $this->getQueueService()->fail($queueItem, $ex->getMessage());
            }

            $context = array(
                'TaskId' => $this->getQueueItemId(),
                'ExceptionMessage' => $ex->getMessage(),
                'ExceptionTrace' => $ex->getTraceAsString(),
            );

            Logger::logWarning("Fail to start task execution: {$ex->getMessage()}", 'Core', $context);
        }
    }

    /**
     * Gets id of a queue item that will be run
     *
     * @return int
     */
    public function getQueueItemId()
    {
        return $this->queueItemId;
    }

    /**
     * @return QueueItem|null
     */
    private function fetchItem()
    {
        $queueItem = null;

        try {
            $queueItem = $this->getQueueService()->find($this->queueItemId);
        } catch (Exception $ex) {
            $context = array(
                'TaskId' => $this->getQueueItemId(),
                'ExceptionMessage' => $ex->getMessage(),
                'ExceptionTrace' => $ex->getTraceAsString(),
            );

            Logger::logWarning("Fail to start task execution: {$ex->getMessage()}", 'Core', $context);
        }

        return $queueItem;
    }

    /**
     * @return Queue
     */
    private function getQueueService()
    {
        if (empty($this->queue)) {
            $this->queue = ServiceRegister::getService(Queue::CLASS_NAME);
        }

        return $this->queue;
    }

    /**
     * @return Configuration
     */
    private function getConfigService()
    {
        if (empty($this->configService)) {
            $this->configService = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configService;
    }
}
