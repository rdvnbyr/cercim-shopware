<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler;

use Exception;
use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Configuration;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Utility\WebhookHelper;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator\WebhookBaseValidator;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration as BaseConfiguration;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class BaseWebhookHandler
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler
 */
abstract class BaseWebhookHandler
{
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var WebhookHelper
     */
    protected $webhookHelper;

    /**
     * @param WebhookDTO $webhookDTO
     *
     * @return bool
     */
    protected abstract function handleInternal($webhookDTO);

    /**
     * @return WebhookBaseValidator
     */
    protected abstract function getValidator();

    /**
     * @return void
     */
    protected function executeAdditionalOperation() {}

    /**
     * @param WebhookDTO $webhookDTO
     * @return void
     */
    protected function handleException($webhookDTO) {}

    /**
     * Handles webhook
     * @param WebhookDTO $webhookDTO
     *
     * @return bool
     */
    public function handle($webhookDTO)
    {
        try {
            $validator = $this->getValidator();

            if ($validator->isValid($webhookDTO)) {
                $result = $this->handleInternal($webhookDTO);

                if ($result) {
                    $this->executeAdditionalOperation();
                }

                return $result;
            }
        } catch (Exception $exception) {
            Logger::logError("Could not validate webhook" . $exception->getMessage());
            $this->handleException($webhookDTO);
        }

        return false;
    }

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

    /**
     * Returns an instance of configuration service.
     *
     * @return Configuration
     */
    protected function getConfiguration()
    {
        if (!$this->configuration) {
            $this->configuration = ServiceRegister::getService(BaseConfiguration::CLASS_NAME);
        }

        return $this->configuration;
    }
}
