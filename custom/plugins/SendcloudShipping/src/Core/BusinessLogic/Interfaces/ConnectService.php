<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Interfaces;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\CredentialsDTO;

/**
 * Interface ConnectService
 * @package Sendcloud\Shipping\Core\BusinessLogic\Interfaces
 */
interface ConnectService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Returns connect redirect url
     *
     * @return string
     */
    public function getRedirectUrl();

    /**
     * Initializes module connection with SendCloud
     *
     * @param CredentialsDTO $credentials
     */
    public function initializeConnection(CredentialsDTO $credentials);

    /**
     * Verifies if callback matches the one in redirect callback url
     *
     * @param CredentialsDTO $credentialsDTO
     * @param string $token
     *
     * @return bool
     */
    public function isCallbackValid(CredentialsDTO $credentialsDTO, $token);

    /**
     * Verifies if integration id, secret and public keys are set and not empty
     *
     * @return bool
     */
    public function isIntegrationConnected();
}
