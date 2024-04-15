<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Webhook\Utility;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\CredentialsDTO;
use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookIntegrationDTO;
use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookParcelDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Exceptions\WebHookPayloadValidationException;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class WebHookHelper
 * @package Sendcloud\Shipping\Core\BusinessLogic\Webhook
 */
class WebhookHelper
{
    const PARCEL_STATUS_CHANGED = 'parcel_status_changed';
    const INTEGRATION_UPDATED = 'integration_updated';
    const INTEGRATION_CONNECTED = 'integration_connected';
    const INTEGRATION_DELETED = 'integration_deleted';
    const INTEGRATION_CREDENTIALS = 'integration_credentials';

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Validates web hook integrity and authority,
     * returns TRUE if hash signature matches integration, FALSE otherwise
     *
     * @param string $hash - Hash value sent by SendCloud in SendCloud-Signature header
     * @param string $payloadRaw - Raw web hook body json payload
     *
     * @return bool
     * @throws WebHookPayloadValidationException
     */
    public function isValid($hash, $payloadRaw)
    {
        if (!is_string($payloadRaw)) {
            throw new WebHookPayloadValidationException('Payload to verify hash is expected to be string');
        }

        $secretKey = $this->getConfiguration()->getSecretKey();

        return hash_hmac('sha256', $payloadRaw, $secretKey) === $hash;
    }

    /**
     * Parses raw web hook payload and returns it as web hook DTO
     *
     * @param array $payload Webhook body json payload
     *
     * @return WebhookParcelDTO
     * @throws WebHookPayloadValidationException
     */
    public function parseParcelPayload(array $payload)
    {
        if (!is_array($payload) || empty($payload)) {
            throw new WebHookPayloadValidationException('Payload is expected to be array.');
        }

        $webhookDTO = new WebhookParcelDTO($payload['action'], $payload['timestamp'], $payload['parcel']['id']);
        $webhookDTO->setTrackingNumber($payload['parcel']['tracking_number']);
        $webhookDTO->setStatusId($payload['parcel']['status']['id']);
        $webhookDTO->setStatusMessage($payload['parcel']['status']['message']);
        $webhookDTO->setOrderNumber($payload['parcel']['order_number']);
        $webhookDTO->setShipmentUuid($payload['parcel']['shipment_uuid']);
        $webhookDTO->setOrderId($payload['parcel']['external_order_id']);

        return $webhookDTO;
    }

    /**
     * Parses raw web hook payload and returns it as web hook DTO
     *
     * @param array $payload Webhook body json payload
     *
     * @return WebhookIntegrationDTO
     * @throws WebHookPayloadValidationException
     */
    public function parseIntegrationPayload(array $payload)
    {
        if (!is_array($payload) || empty($payload)) {
            throw new WebHookPayloadValidationException('Payload is expected to be array.');
        }

        $webhookDTO = new WebhookIntegrationDTO(
            $payload['action'],
            $payload['timestamp'],
            $payload['integration']['id'],
            $payload['integration']['service_point_enabled'],
            $payload['integration']['service_point_carriers']
        );

        return $webhookDTO;
    }

    /**
     * Parses raw web hook payload and returns it as credentials DTO
     *
     * @param array $payload Webhook body json payload
     *
     * @return CredentialsDTO
     * @throws WebHookPayloadValidationException
     */
    public function parseIntegrationCredentials(array $payload)
    {
        if (!is_array($payload) || empty($payload)) {
            throw new WebHookPayloadValidationException('Payload is expected to be array.');
        }

        $credentialsDTO = new CredentialsDTO(
            $payload['action'],
            $payload['timestamp'],
            $payload['integration_id'],
            $payload['public_key'],
            $payload['secret_key']
        );

        return $credentialsDTO;
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
