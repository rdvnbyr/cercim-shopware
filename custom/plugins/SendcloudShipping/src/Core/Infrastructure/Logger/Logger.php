<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Logger;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class Logger
 * @package Sendcloud\Shipping\Core\Infrastructure\Logger
 */
class Logger
{
    const CRITICAL = -1;
    const ERROR = 0;
    const WARNING = 1;
    const INFO = 2;
    const DEBUG = 3;

    private static $instance;

    /**
     * Shop logger
     *
     * @var ShopLoggerAdapter
     */
    private $shopLogger;

    /**
     * Default logger
     *
     * @var DefaultLoggerAdapter
     */
    private $defaultLogger;

    /**
     * @var TimeProvider
     */
    private $timeProvider;

    /**
     * Getting logger component instance
     *
     * @return Logger
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Logger();
        }

        return self::$instance;
    }

    /**
     * Logger constructor.
     */
    public function __construct()
    {
        $this->defaultLogger = ServiceRegister::getService(DefaultLoggerAdapter::CLASS_NAME);
        $this->shopLogger = ServiceRegister::getService(ShopLoggerAdapter::CLASS_NAME);
        $this->timeProvider = ServiceRegister::getService(TimeProvider::CLASS_NAME);

        self::$instance = $this;
    }

    /**
     * Logging critical message
     *
     * @param string $message
     * @param string $component
     * @param array $context
     */
    public static function logCritical($message, $component = 'Core', array $context = array())
    {
        self::getInstance()->logMessage(self::CRITICAL, $message, $component, $context);
    }

    /**
     * Logging error message
     *
     * @param string $message
     * @param string $component
     * @param array $context
     */
    public static function logError($message, $component = 'Core', array $context = array())
    {
        self::getInstance()->logMessage(self::ERROR, $message, $component, $context);
    }

    /**
     * Logging warning message
     *
     * @param string $message
     * @param string $component
     * @param array $context
     */
    public static function logWarning($message, $component = 'Core', array $context = array())
    {
        self::getInstance()->logMessage(self::WARNING, $message, $component, $context);
    }

    /**
     * Logging info message
     *
     * @param string $message
     * @param string $component
     * @param array $context
     */
    public static function logInfo($message, $component = 'Core', array $context = array())
    {
        self::getInstance()->logMessage(self::INFO, $message, $component, $context);
    }

    /**
     * Logging debug message
     *
     * @param string $message
     * @param string $component
     * @param array $context
     */
    public static function logDebug($message, $component = 'Core', array $context = array())
    {
        self::getInstance()->logMessage(self::DEBUG, $message, $component, $context);
    }

    /**
     * Logging message
     *
     * @param int $level
     * @param string $message
     * @param string $component
     * @param LogContextData[] $context
     */
    private function logMessage($level, $message, $component, array $context = array())
    {
        $config = Configuration::getInstance();
        $logData = new LogData(
            $config->getIntegrationName(),
            $level,
            $this->timeProvider->getMillisecondsTimestamp(),
            $component,
            $message,
            $context
        );

        // If default logger is turned on and message level is lower or equal than set in configuration
        if ($config->isDefaultLoggerEnabled() && $level <= $config->getMinLogLevel()) {
            $this->defaultLogger->logMessage($logData);
        }

        $this->shopLogger->logMessage($logData);
    }
}
