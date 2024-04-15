<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Exceptions\WebHookPayloadValidationException;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\ConnectService;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class TokenValidator
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator
 */
class TokenValidator extends WebhookBaseValidator
{
    const CLASS_NAME = __CLASS__;

    /**
     * Validates webhook request
     *
     * @param WebhookDTO $webhookDTO
     * @return bool
     *
     * @throws WebHookPayloadValidationException
     */
    public function isValid($webhookDTO)
    {
        /** @var ConnectService $connectService */
        $connectService = ServiceRegister::getService(ConnectService::CLASS_NAME);

        if (!$webhookDTO->getBody()) {
            return false;
        }

        $credentials = $this->getWebhookHelper()->parseIntegrationCredentials($webhookDTO->getBody());

        return $connectService->isCallbackValid($credentials, $webhookDTO->getToken());
    }
}
