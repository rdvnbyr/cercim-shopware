<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator\BaseAuthenticationValidator;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Exception;

/**
 * Class IntegrationConnectedHandler
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler
 */
class IntegrationConnectedHandler extends BaseWebhookHandler
{
    /**
     * Handles webhook
     * @param WebhookDTO $webhookDTO
     *
     * @return bool
     */
    protected function handleInternal($webhookDTO)
    {
        try {
            $webhookIntegration = $this->getWebhookHelper()->parseIntegrationPayload($webhookDTO->getBody());
            $this->getConfiguration()->setServicePointEnabled($webhookIntegration->isServicePointsEnabled());
            $this->getConfiguration()->setCarriers($webhookIntegration->getCarriers());

            return true;
        } catch (Exception $exception) {
            Logger::logError('Could not handle integration connected webhook' . $exception->getMessage());
            return false;
        }
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
