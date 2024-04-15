<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\DTO;

/**
 * Class CredentialsDTO
 * @package Sendcloud\Shipping\Core\BusinessLogic\DTO
 */
class CredentialsDTO
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
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * CredentialsDTO constructor.
     *
     * @param string $action
     * @param int $timestamp
     * @param int $integrationId
     * @param string $publicKey
     * @param string $secretKey
     */
    public function __construct($action, $timestamp, $integrationId, $publicKey, $secretKey)
    {
        $this->action = $action;
        $this->timestamp = $timestamp;
        $this->integrationId = (int)$integrationId;
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
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
     * @return string
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secretKey;
    }
}
