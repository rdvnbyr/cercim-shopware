<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\DTO;

/**
 * Class IntegrationDTO
 * @package Sendcloud\Shipping\Core\BusinessLogic\DTO
 */
class IntegrationDTO
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $shopName;
    /**
     * @var string
     */
    private $system;
    /**
     * @var \DateTime
     */
    private $failingSince;
    /**
     * @var \DateTime
     */
    private $lastUpdatedAt;
    /**
     * @var bool
     */
    private $servicePointsEnabled;
    /**
     * @var string[]
     */
    private $servicePointCarriers;
    /**
     * @var bool
     */
    private $webHookActive = false;
    /**
     * @var string
     */
    private $webHookUrl = '';

    /**
     * IntegrationDTO constructor.
     *
     * @param int $id
     * @param string $shopName
     * @param string $system
     * @param bool $servicePointsEnabled
     * @param string[] $servicePointCarriers
     * @param \DateTime $failingSince
     * @param \DateTime $lastUpdateAt
     * @param bool $webHookActive
     * @param string $webHookUrl
     */
    public function __construct(
        $id,
        $shopName,
        $system,
        $servicePointsEnabled,
        array $servicePointCarriers,
        \DateTime $failingSince = null,
        \DateTime $lastUpdateAt = null,
        $webHookActive = false,
        $webHookUrl = ''
    ) {
        $this->id = $id;
        $this->shopName = $shopName;
        $this->system = $system;
        $this->failingSince = $failingSince;
        $this->lastUpdatedAt = $lastUpdateAt;
        $this->servicePointsEnabled = $servicePointsEnabled;
        $this->servicePointCarriers = $servicePointCarriers;
        $this->webHookActive = $webHookActive;
        $this->webHookUrl = $webHookUrl;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getShopName()
    {
        return $this->shopName;
    }

    /**
     * @return string
     */
    public function getSystem()
    {
        return $this->system;
    }

    /**
     * @return \DateTime
     */
    public function getFailingSince()
    {
        return $this->failingSince;
    }

    /**
     * @return \DateTime
     */
    public function getLastUpdatedAt()
    {
        return $this->lastUpdatedAt;
    }

    /**
     * @return bool
     */
    public function isServicePointsEnabled()
    {
        return $this->servicePointsEnabled;
    }

    /**
     * @return string[]
     */
    public function getServicePointCarriers()
    {
        return $this->servicePointCarriers;
    }

    /**
     * @return bool
     */
    public function isWebHookActive()
    {
        return $this->webHookActive;
    }

    /**
     * @return string
     */
    public function getWebHookUrl()
    {
        return $this->webHookUrl;
    }

    /**
     * @param string $shopName
     */
    public function setShopName($shopName)
    {
        $this->shopName = $shopName;
    }

    /**
     * @param bool $servicePointsEnabled
     */
    public function setServicePointsEnabled($servicePointsEnabled)
    {
        $this->servicePointsEnabled = $servicePointsEnabled;
    }

    /**
     * @param string[] $servicePointCarriers
     */
    public function setServicePointCarriers($servicePointCarriers)
    {
        $this->servicePointCarriers = $servicePointCarriers;
    }

    /**
     * @param bool $webHookActive
     */
    public function setWebHookActive($webHookActive)
    {
        $this->webHookActive = $webHookActive;
    }

    /**
     * @param string $webHookUrl
     */
    public function setWebHookUrl($webHookUrl)
    {
        $this->webHookUrl = $webHookUrl;
    }
}
