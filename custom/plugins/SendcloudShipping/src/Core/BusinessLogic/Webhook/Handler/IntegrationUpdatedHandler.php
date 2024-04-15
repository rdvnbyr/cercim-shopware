<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Exception;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator\BaseAuthenticationValidator;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class IntegrationUpdatedHandler
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler
 */
class IntegrationUpdatedHandler extends BaseWebhookHandler
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
        } catch (Exception $e) {
            Logger::logError('Could not handle integration updated webhook' . $e->getMessage());
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
