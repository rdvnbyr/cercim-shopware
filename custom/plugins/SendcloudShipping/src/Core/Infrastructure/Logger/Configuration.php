<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Logger;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration as ConfigInterface;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class Configuration
 * @package Sendcloud\Shipping\Core\Infrastructure\Logger
 */
class Configuration
{

    const DEFAULT_MIN_LOG_LEVEL = Logger::DEBUG;
    const DEFAULT_IS_DEFAULT_LOGGER_ENABLED = false;

    const BASE_LOGGER_URL = '';

    /**
     * @var Configuration
     */
    private static $instance;

    /**
     * @var boolean
     */
    private $isDefaultLoggerEnabled;

    /**
     * @var ConfigInterface
     */
    private $shopConfiguration;

    /**
     * @var int
     */
    private $minLogLevel;

    /**
     * @var string
     */
    private $integrationName;

    /**
     * Getting logger configuration instance
     *
     * @return Configuration
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Configuration();
        }

        return self::$instance;
    }

    /**
     * Resetting singleton instance. Required for proper tests.
     */
    public static function resetInstance()
    {
        self::$instance = null;
    }

    /**
     * Set default logger status (turning on/off)
     *
     * @param boolean $status
     */
    public static function setDefaultLoggerEnabled($status)
    {
        self::getInstance()->setIsDefaultLoggerEnabled($status);
    }

    /**
     * Returns logger status
     *
     * @return boolean
     */
    public function isDefaultLoggerEnabled()
    {
        if (empty($this->isDefaultLoggerEnabled)) {
            try {
                $this->isDefaultLoggerEnabled = $this->getShopConfiguration()->isDefaultLoggerEnabled();
            } catch (\Exception $ex) {
                // Catch if configuration is not set properly and for some reason throws exception
                // e.g. Client is still not authorized (meaning that configuration is not set) and we want to log something
            }
        }

        return !empty($this->isDefaultLoggerEnabled) ? $this->isDefaultLoggerEnabled : self::DEFAULT_IS_DEFAULT_LOGGER_ENABLED;
    }

    /**
     * Sets logger status
     *
     * @param boolean $loggerStatus
     */
    public function setIsDefaultLoggerEnabled($loggerStatus)
    {
        $this->getShopConfiguration()->setDefaultLoggerEnabled($loggerStatus);
        $this->isDefaultLoggerEnabled = $loggerStatus;
    }

    /**
     * Returns minimal log level
     *
     * @return int
     */
    public function getMinLogLevel()
    {
        if (!isset($this->minLogLevel)) {
            try {
                $this->minLogLevel = $this->getShopConfiguration()->getMinLogLevel();
            } catch (\Exception $ex) {
                // Catch if configuration is not set properly and for some reason throws exception
                // e.g. Client is still not authorized (meaning that configuration is not set) and we want to log something
            }
        }

        return isset($this->minLogLevel) ? $this->minLogLevel : self::DEFAULT_MIN_LOG_LEVEL;
    }

    /**
     * @param int $minLogLevel
     */
    public function setMinLogLevel($minLogLevel)
    {
        $this->getShopConfiguration()->saveMinLogLevel($minLogLevel);
        $this->minLogLevel = $minLogLevel;
    }

    /**
     * Returns integration name
     *
     * @return string
     */
    public function getIntegrationName()
    {
        if (empty($this->integrationName)) {
            try {
                $this->integrationName = $this->getShopConfiguration()->getIntegrationName();
            } catch (\Exception $ex) {
                // Catch if configuration is not set properly and for some reason throws exception
                // e.g. Client is still not authorized (meaning that configuration is not set) and we want to log something
            }
        }

        return !empty($this->integrationName) ? $this->integrationName : 'unknown';
    }

    /**
     * Returns shop configuration instance
     *
     * @return ConfigInterface
     */
    private function getShopConfiguration()
    {
        if (empty($this->shopConfiguration)) {
            $this->shopConfiguration = ServiceRegister::getService(ConfigInterface::CLASS_NAME);
        }

        return $this->shopConfiguration;
    }
}
