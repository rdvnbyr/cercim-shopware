<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Interfaces;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration as BaseConfiguration;

/**
 * Interface Configuration
 * @package Sendcloud\Shipping\Core\BusinessLogic\Interfaces
 */
interface Configuration extends BaseConfiguration
{
    /**
     * Retrieves integration id
     *
     * @return int
     */
    public function getIntegrationId();

    /**
     * Set integration id
     *
     * @param int $id Integration id
     */
    public function setIntegrationId($id);

    /**
     * Retrieve public key if saved in storage, otherwise  null
     *
     * @return string|null
     */
    public function getPublicKey();

    /**
     * Set integration public key
     *
     * @param string $publicKey
     */
    public function setPublicKey($publicKey);

    /**
     * Retrieve secret key if saved in storage, otherwise  null
     *
     * @return string|null
     */
    public function getSecretKey();

    /**
     * Set integration secret key
     *
     * @param string $secretKey
     */
    public function setSecretKey($secretKey);

    /**
     * Returns shop base url, if it is sub-shop it should return its specific url
     *
     * @return string
     */
    public function getBaseUrl();


    /**
     * Returns name of current shop.
     *
     * @return string
     */
    public function getShopName();

    /**
     * Returns web hook endpoint
     *
     * @param bool $addToken
     *
     * @return string
     */
    public function getWebHookEndpoint($addToken = false);

    /**
     * Gets web hook token from configuration.
     *
     * @return string
     */
    public function getWebHookToken();

    /**
     * Checks if webhook token is valid.
     *
     * @param string $token
     *
     * @return bool
     */
    public function isWebHookTokenValid($token);

    /**
     * Sets webhook token in configuration.
     *
     * @param string $token
     */
    public function setWebHookToken($token);

    /**
     * Sets webhook token time in configuration.
     *
     * @param int $time
     */
    public function setWebHookTokenTime($time);

    /**
     * Returns service point enabled flag.
     *
     * @return bool
     */
    public function isServicePointEnabled();

    /**
     * Sets service point enabled flag
     *
     * @param bool $enabled
     */
    public function setServicePointEnabled($enabled);

    /**
     * Returns list of enabled carriers
     *
     * @return array
     */
    public function getCarriers();

    /**
     * Sets a list of available carriers
     *
     * @param array $carriers
     */
    public function setCarriers(array $carriers = array());

    /**
     * Gets max retention period in seconds for queue items in completed status. Default value is 604800 (7 days)
     * @return int
     */
    public function getCompletedTasksRetentionPeriod();

    /**
     * Gets max retention period in seconds for queue items in failed status. Default value is 2592000 (30 days)
     * @return int
     */
    public function getFailedTasksRetentionPeriod();

    /**
     * Retrieves old time cleanup time threshold in seconds. Default value is 86400 (1 day)
     *
     * @return int
     */
    public function getOldTaskCleanupTimeThreshold();

    /**
     * Gets old task cleanup queue name.
     *
     * @return string
     */
    public function getOldTaskCleanupQueueName();
}
