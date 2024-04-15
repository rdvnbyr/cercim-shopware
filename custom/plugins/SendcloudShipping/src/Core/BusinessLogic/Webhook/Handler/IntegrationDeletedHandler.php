<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator\BaseAuthenticationValidator;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class IntegrationDeletedHandler
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler
 */
class IntegrationDeletedHandler extends BaseWebhookHandler
{
    /**
     * Handles webhook
     * @param WebhookDTO $webhookDTO
     *
     * @return bool
     */
    protected function handleInternal($webhookDTO)
    {
        $this->getConfiguration()->resetAuthorizationCredentials();
        $this->getConfiguration()->setServicePointEnabled(false);
        $this->getConfiguration()->setCarriers();

        /** @var TaskQueueStorage $queueStorage */
        $queueStorage = ServiceRegister::getService(TaskQueueStorage::CLASS_NAME);

        return $queueStorage->deleteByType('InitialSyncTask', $webhookDTO->getContext());
    }

    /**
     * Returns instance of BaseAuthenticationValidator
     *
     * @return BaseAuthenticationValidator
     */
    protected function getValidator()
    {
        return ServiceRegister::getService(BaseAuthenticationValidator::CLASS_NAME);
    }
}
