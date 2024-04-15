<?php

namespace Sendcloud\Shipping\Utility;

use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Utility\WebhookBootstrap as BaseBootstrap;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Utility\WebhookHelper;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\WebhookHandlerRegistry;
use Sendcloud\Shipping\Handlers\ParcelStatusHandler;

/**
 * Class WebhookBootstrap
 *
 * @package Sendcloud\Shipping\Utility
 */
class WebhookBootstrap extends BaseBootstrap
{
    /**
     * Registers webhook handlers
     *
     * @return void
     */
    public static function init(): void
    {
        parent::init();

        WebhookHandlerRegistry::registerWebhookHandler(
            WebhookHelper::PARCEL_STATUS_CHANGED,
            function() {
                return new ParcelStatusHandler();
            }
        );
    }
}
