<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Utility\WebhookHelper;

/**
 * Class WebhookBaseValidator
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator
 */
abstract class WebhookBaseValidator
{
    /**
     * @var WebhookHelper
     */
    private $webhookHelper;

    /**
     * Validates webhook request
     * @param WebhookDTO $webhookDTO
     *
     * @return bool
     */
    public abstract function isValid($webhookDTO);

    /**
     * Returns an instance of webhook helper class.
     *
     * @return WebhookHelper
     */
    protected function getWebhookHelper()
    {
        if (!$this->webhookHelper) {
            $this->webhookHelper = new WebhookHelper();
        }

        return $this->webhookHelper;
    }
}
