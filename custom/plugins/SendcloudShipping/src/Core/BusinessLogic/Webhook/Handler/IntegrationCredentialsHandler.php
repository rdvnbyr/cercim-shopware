<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Exception;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\ConnectService;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator\TokenValidator;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class IntegrationCredentialsHandler
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler
 */
class IntegrationCredentialsHandler extends BaseWebhookHandler
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
            $credentials = $this->getWebhookHelper()->parseIntegrationCredentials($webhookDTO->getBody());
            $this->getConnectService()->initializeConnection($credentials);

            return true;
        } catch (Exception $exception) {
            Logger::logError('Could not handle integration credentials webhook' . $exception->getMessage());
            return false;
        }
    }

    /**
     * Handles exception
     * @param WebhookDTO $webhookDTO
     *
     * @return void
     */
    protected function handleException($webhookDTO)
    {
        $this->getConfiguration()->resetAuthorizationCredentials();
    }

    /**
     * Returns instance of TokenValidator
     *
     * @return TokenValidator
     */
    protected function getValidator()
    {
        return ServiceRegister::getService(TokenValidator::CLASS_NAME);
    }

    /**
     * Returns instance of ConnectService
     *
     * @return ConnectService
     */
    private function getConnectService()
    {
        return ServiceRegister::getService(ConnectService::CLASS_NAME);
    }
}
