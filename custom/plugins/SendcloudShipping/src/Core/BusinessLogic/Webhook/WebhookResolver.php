<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Exceptions\ActionNotSupportedException;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Configuration;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler\BaseWebhookHandler;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration as BaseConfiguration;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class WebhookResolver
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook
 */
class WebhookResolver
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Resolves webhook
     * @param WebhookDTO $webhookDTO
     *
     * @return bool
     *
     * @throws ActionNotSupportedException
     */
    public function resolve($webhookDTO)
    {
        $this->getConfiguration()->setContext($webhookDTO->getContext());

        $body = $webhookDTO->getBody();
        $action = isset($body['action']) ? $body['action'] : '';

        /** @var BaseWebhookHandler $handler */
        $handler = WebhookHandlerRegistry::get($action);

        if (!$handler) {
            throw new ActionNotSupportedException('Action ' . $action . ' is not supported.');
        }

        return $handler->handle($webhookDTO);
    }

    /**
     * Returns an instance of configuration service.
     *
     * @return Configuration
     */
    private function getConfiguration()
    {
        if (!$this->configuration) {
            $this->configuration = ServiceRegister::getService(BaseConfiguration::CLASS_NAME);
        }

        return $this->configuration;
    }
}
