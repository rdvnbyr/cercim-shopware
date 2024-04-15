<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Services;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\CredentialsDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Configuration;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\ConnectService as ConnectServiceInterface;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class ConnectService
 * @package Sendcloud\Shipping\Core\BusinessLogic\Services
 */
class ConnectService implements ConnectServiceInterface
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Returns connect redirect url
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        $integrationCode = $this->getConfiguration()->getIntegrationName();
        $queryParameters = array(
            'url_webshop' => $this->getConfiguration()->getBaseUrl(),
            'webhook_url' => $this->getConfiguration()->getWebHookEndpoint(true),
            'shop_name' => $this->getConfiguration()->getShopName(),
            'shop_code' => $integrationCode,
        );

        return $this->getConfiguration()->getConnectUrl() . 'shops/' . $integrationCode .'/connect/?' . http_build_query($queryParameters);
    }

    /**
     * Initializes module connection with SendCloud
     *
     * @param CredentialsDTO $credentials
     */
    public function initializeConnection(CredentialsDTO $credentials)
    {
        $configuration = $this->getConfiguration();
        $configuration->setPublicKey($credentials->getPublicKey());
        $configuration->setSecretKey($credentials->getSecretKey());
        $configuration->setIntegrationId((int)$credentials->getIntegrationId());
    }

    /**
     * Verifies if callback matches the one in redirect callback url
     *
     * @param CredentialsDTO $credentialsDTO
     * @param string $token
     *
     * @return bool
     */
    public function isCallbackValid(CredentialsDTO $credentialsDTO, $token)
    {
        $publicKey = $credentialsDTO->getPublicKey();
        $secretKey = $credentialsDTO->getSecretKey();
        $integrationId = $credentialsDTO->getIntegrationId();
        $timestamp = $credentialsDTO->getTimestamp();
        $action = $credentialsDTO->getAction();
        $payloadValid = isset($publicKey, $secretKey, $integrationId, $timestamp, $action);

        return $payloadValid && $this->getConfiguration()->isWebHookTokenValid($token);
    }

    /**
     * Verifies if integration id, secret and public keys are set and not empty
     *
     * @return bool
     */
    public function isIntegrationConnected()
    {
        $integrationId = $this->getConfiguration()->getIntegrationId();
        $publicKey = $this->getConfiguration()->getPublicKey();
        $secretKey = $this->getConfiguration()->getSecretKey();

        return !empty($integrationId) && !empty($publicKey) && !empty($secretKey);
    }

    /**
     * Returns an instance of configuration service
     *
     * @return Configuration
     */
    private function getConfiguration()
    {
        if (!$this->configuration) {
            $this->configuration = ServiceRegister::getService(Configuration::CLASS_NAME);
        }

        return $this->configuration;
    }
}
