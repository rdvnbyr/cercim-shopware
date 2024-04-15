<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook;

use Sendcloud\Shipping\Core\BusinessLogic\Webhook\Handler\BaseWebhookHandler;

/**
 * Class WebhookHandlerRegistry
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook
 */
class WebhookHandlerRegistry
{
    /**
     * @var WebhookHandlerRegistry
     */
    private static $instance;
    /**
     * @var array
     */
    private $webhookHandlers;

    /**
     * WebhookHandlerRegistry constructor
     */
    private function __construct()
    {
        $this->webhookHandlers = array();
    }

    /**
     * @param string $eventType
     * @param callable $callable
     *
     * @return void
     */
    public static function registerWebhookHandler($eventType, $callable)
    {
        $handlerRegistry = self::getInstance();

        if (is_callable($callable)) {
            $handlerRegistry->webhookHandlers[$eventType] = $callable;
        }
    }

    /**
     * @param string $eventType
     *
     * @return BaseWebhookHandler|null
     */
    public static function get($eventType)
    {
        $handlerRegistry = self::getInstance();
        $callable = $handlerRegistry->getCallableByType($eventType);

        if ($callable && is_callable($callable)) {
            return call_user_func($callable);
        }

        return null;
    }

    /**
     * @return WebhookHandlerRegistry
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $eventType
     *
     * @return mixed
     */
    private function getCallableByType($eventType)
    {
        return isset($this->webhookHandlers[$eventType]) ? $this->webhookHandlers[$eventType] : null;
    }
}
