<?php

namespace Sendcloud\Shipping\Handlers;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookParcelDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Sync\ParcelUpdateTask;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler\ParcelStatusHandler as BaseHandler;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Queue;
use Sendcloud\Shipping\Interfaces\EntityQueueNameAware;
use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Exception;

/**
 * Class ParcelStatusHandler
 *
 * @package Sendcloud\Shipping\Handlers
 */
class ParcelStatusHandler extends BaseHandler
{
    /**
     * Handles webhook request
     *
     * @param WebhookDTO $webhookDTO
     *
     * @return bool
     */
    protected function handleInternal($webhookDTO): bool
    {
        try {
            $parcel = $webhookDTO->getBody()['parcel'] ?? null;
            $isReturn = $parcel && $parcel['is_return'];

            if ($isReturn) {
                Logger::logInfo('Skipping queueing ParcelUpdate task because return is created');
                return true;
            }

            $webhookParcel = $this->getWebhookHelper()->parseParcelPayload($webhookDTO->getBody());
            $this->enqueueParcelUpdateTask($webhookParcel, $webhookDTO->getContext());

            return true;
        } catch (Exception $exception) {
            Logger::logError('Could not handle parcel status change webhook' . $exception->getMessage());
            return false;
        }
    }

    /**
     * Enqueues task for parcel update.
     *
     * @param WebhookParcelDTO $webhookParcel
     * @param string $context
     *
     * @throws QueueStorageUnavailableException
     */
    protected function enqueueParcelUpdateTask(WebhookParcelDTO $webhookParcel, $context)
    {
        /** @var EntityQueueNameAware $config */
        $config = $this->getConfiguration();
        /** @var Queue $queue */
        $queue = ServiceRegister::getService(Queue::CLASS_NAME);
        $queue->enqueue(
            $config->getEntityQueueName('order', $webhookParcel->getOrderId()),
            new ParcelUpdateTask(
                $webhookParcel->getShipmentUuid(),
                $webhookParcel->getOrderId(),
                $webhookParcel->getOrderNumber(),
                $webhookParcel->getParcelId(),
                $webhookParcel->getTimestamp()
            ),
            $context
        );
    }
}
