<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook\Utility;

use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler\IntegrationConnectedHandler;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler\IntegrationCredentialsHandler;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler\IntegrationDeletedHandler;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler\IntegrationUpdatedHandler;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler\ParcelStatusHandler;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator\BaseAuthenticationValidator;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Validator\TokenValidator;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\WebhookHandlerRegistry;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class WebhookBootstrap
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook\Utility
 */
class WebhookBootstrap
{
    /**
     * Registers webhook handlers
     *
     * @return void
     */
    public static function init()
    {
        WebhookHandlerRegistry::registerWebhookHandler(
            WebhookHelper::PARCEL_STATUS_CHANGED, function () {
            return new ParcelStatusHandler();
        });

        WebhookHandlerRegistry::registerWebhookHandler(
            WebhookHelper::INTEGRATION_CONNECTED, function () {
            return new IntegrationConnectedHandler();
        });

        WebhookHandlerRegistry::registerWebhookHandler(
            WebhookHelper::INTEGRATION_CREDENTIALS, function () {
            return new IntegrationCredentialsHandler();
        });

        WebhookHandlerRegistry::registerWebhookHandler(
            WebhookHelper::INTEGRATION_UPDATED, function () {
            return new IntegrationUpdatedHandler();
        });

        WebhookHandlerRegistry::registerWebhookHandler(
            WebhookHelper::INTEGRATION_DELETED, function () {
            return new IntegrationDeletedHandler();
        });

        self::registerServices();
    }

    /**
     * Registers services
     *
     * @return void
     */
    private static function registerServices()
    {
        ServiceRegister::registerService(
            BaseAuthenticationValidator::CLASS_NAME, function () {
            return new BaseAuthenticationValidator();
        });

        ServiceRegister::registerService(
            TokenValidator::CLASS_NAME, function () {
            return new TokenValidator();
        });
    }
}
