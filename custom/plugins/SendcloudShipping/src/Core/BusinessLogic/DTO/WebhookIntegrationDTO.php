<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\DTO;

/**
 * Class WebHookIntegrationDTO
 * @package Sendcloud\Shipping\Core\BusinessLogic\DTO
 */
class WebhookIntegrationDTO
{
    /**
     * @var string
     */
    private $action;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var int
     */
    private $integrationId;

    /**
     * @var bool
     */
    private $servicePointsEnabled;

    /**
     * @var array
     */
    private $carriers = array();

    /**
     * WebHookIntegrationDTO constructor.
     *
     * @param string $action
     * @param int $timestamp
     * @param int $integrationId
     * @param bool $servicePointsEnabled
     * @param array $carriers
     */
    public function __construct($action, $timestamp, $integrationId, $servicePointsEnabled, array $carriers = array())
    {
        $this->action = $action;
        $this->timestamp = $timestamp;
        $this->integrationId = $integrationId;
        $this->servicePointsEnabled = $servicePointsEnabled;
        $this->carriers = $carriers;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return int
     */
    public function getIntegrationId()
    {
        return $this->integrationId;
    }

    /**
     * @return bool
     */
    public function isServicePointsEnabled()
    {
        return $this->servicePointsEnabled;
    }

    /**
     * @return array
     */
    public function getCarriers()
    {
        return $this->carriers;
    }
}
