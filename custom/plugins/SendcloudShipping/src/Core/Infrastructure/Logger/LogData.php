<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Logger;

/**
 * Class LogData
 * @package Sendcloud\Shipping\Core\Infrastructure\Logger
 */
class LogData
{

    /**
     * @var string
     */
    private $integration;

    /**
     * @var array
     */
    private $context;

    /**
     * @var int
     */
    private $logLevel;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var string
     */
    private $component;

    /**
     * @var string
     */
    private $message;

    /**
     * LogData constructor.
     *
     * @param string $integration
     * @param int $logLevel
     * @param int $timestamp
     * @param string $component
     * @param string $message
     * @param array $context
     */
    public function __construct($integration, $logLevel, $timestamp, $component, $message, array $context = array())
    {
        $this->integration = $integration;
        $this->logLevel = $logLevel;
        $this->component = $component;
        $this->timestamp = $timestamp;
        $this->message = $message;
        $this->context = array();

        foreach ($context as $key => $item) {
            if (!($item instanceof LogContextData)) {
                $item = new LogContextData($key, $item);
            }

            $this->context[] = $item;
        }
    }

    /**
     * @return string
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * @return LogContextData[]
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return int
     */
    public function getLogLevel()
    {
        return $this->logLevel;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getComponent()
    {
        return $this->component;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}
