<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Exceptions\WebHookPayloadValidationException;

/**
 * Class BaseAuthenticationValidator
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator
 */
class BaseAuthenticationValidator extends WebhookBaseValidator
{
    const CLASS_NAME = __CLASS__;

    /**
     * Validates webhook request
     * @param WebhookDTO $webhookDTO
     * @return bool
     *
     * @throws WebHookPayloadValidationException
     */
    public function isValid($webhookDTO)
    {
        return $this->getWebhookHelper()->isValid($webhookDTO->getHash(), $webhookDTO->getRawBody());
    }
}
