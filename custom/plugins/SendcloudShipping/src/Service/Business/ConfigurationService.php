<?php

namespace Sendcloud\Shipping\Service\Business;

use Doctrine\DBAL\DBALException;
use Sendcloud\Shipping\Core\BusinessLogic\DTO\IntegrationDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Configuration;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Proxy;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\Utility\GuidProvider;
use Sendcloud\Shipping\Entity\Config\ConfigEntityRepository;
use Sendcloud\Shipping\Entity\Config\SystemConfigurationRepository;
use Sendcloud\Shipping\Interfaces\EntityQueueNameAware;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class ConfigurationService
 *
 * @package Sendcloud\Shipping\Service\Business
 */
class ConfigurationService implements Configuration, EntityQueueNameAware
{
    public const INTEGRATION_NAME = 'shopware6';
    public const DEFAULT_BATCH_SIZE = 100;
    public const DEFAULT_MAX_STARTED_TASK_LIMIT = 8;
    public const TOKEN_VALID_FOR = 3600;
    public const CONFIG_VALID_FOR = 86400;
    public const ASYNC_PROCESS_TIMEOUT = 1000;
    public const COMPLETED_TASKS_RETENTION_PERIOD = 604800;
    public const FAILED_TASKS_RETENTION_PERIOD = 2592000;
    public const OLD_TASKS_CLEANUP_THRESHOLD = 86400;

    /**
     * @var string
     */
    private $context;
    /**
     * @var array
     */
    private $userInfo;
    /**
     * @var ConfigEntityRepository
     */
    private $configRepository;
    /**
     * @var SystemConfigurationRepository
     */
    private $systemConfigurationRepository;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var array
     */
    private static $callbackMap = [];

    /**
     * ConfigurationService constructor.
     *
     * @param ConfigEntityRepository $configRepository
     * @param UrlGeneratorInterface $urlGenerator
     * @param SystemConfigurationRepository $systemConfigurationRepository
     */
    public function __construct(
        ConfigEntityRepository $configRepository,
        UrlGeneratorInterface $urlGenerator,
        SystemConfigurationRepository $systemConfigurationRepository
    )
    {
        $this->configRepository = $configRepository;
        $this->urlGenerator = $urlGenerator;
        $this->systemConfigurationRepository = $systemConfigurationRepository;
    }

    /**
     * Sets task execution context.
     *
     * When integration supports multiple accounts (middleware integration) proper context must be set based on
     * middleware account that is using core library functionality. This context should then be used by business
     * services to fetch account specific data.Core will set context provided upon task enqueueing before task
     * execution.
     *
     * @param string $context Context to set
     */
    public function setContext($context): void
    {
        $this->context = $context;
    }

    /**
     * Set default logger status (enabled/disabled)
     *
     * @param bool $status
     *
     * @throws DBALException
     */
    public function setDefaultLoggerEnabled($status): void
    {
        $this->configRepository->updateValue('SENDCLOUD_DEFAULT_LOGGER_STATUS', $status);
    }

    /**
     * Sets task runner status information as JSON encoded string.
     *
     * @param string $guid
     * @param int $timestamp
     *
     * @throws DBALException
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    public function setTaskRunnerStatus($guid, $timestamp): void
    {
        $taskRunnerStatus = json_encode(['guid' => $guid, 'timestamp' => $timestamp]);
        $response = $this->configRepository->updateValue('SENDCLOUD_TASK_RUNNER_STATUS', $taskRunnerStatus);

        if ($response === false) {
            throw new TaskRunnerStatusStorageUnavailableException('Task runner status storage is not available.');
        }
    }

    /**
     * Set integration id
     *
     * @param int $id Integration id
     *
     * @throws DBALException
     */
    public function setIntegrationId($id): void
    {
        $this->configRepository->updateValue('SENDCLOUD_INTEGRATION_ID', $id);
    }

    /**
     * Set integration public key
     *
     * @param string $publicKey
     *
     * @throws DBALException
     */
    public function setPublicKey($publicKey): void
    {
        $this->configRepository->updateValue('SENDCLOUD_PUBLIC_KEY', $publicKey);
    }

    /**
     * Retrieve secret key if saved in storage, otherwise  null
     *
     * @return string|null
     * @throws DBALException
     */
    public function getSecretKey(): ?string
    {
        return $this->configRepository->getValue('SENDCLOUD_SECRET_KEY');
    }

    /**
     * Retrieves min log level from integration database
     *
     * @return int
     * @throws DBALException
     */
    public function getMinLogLevel(): int
    {
        $configValue = $this->configRepository->getValue('SENDCLOUD_MIN_LOG_LEVEL');

        return $configValue ? (int)$configValue : Logger::INFO;
    }

    /**
     * Resets authorization credentials to null
     *
     * @throws DBALException
     */
    public function resetAuthorizationCredentials(): void
    {
        $this->setPublicKey('');
        $this->setSecretKey('');
    }

    /**
     * Retrieves integration id
     *
     * @return int
     * @throws DBALException
     */
    public function getIntegrationId(): int
    {
        return (int)$this->configRepository->getValue('SENDCLOUD_INTEGRATION_ID');
    }

    /**
     * Returns shop base url
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->urlGenerator->generate('root.fallback', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * Saves min log level in integration database
     *
     * @param int $minLogLevel
     *
     * @throws DBALException
     */
    public function saveMinLogLevel($minLogLevel): void
    {
        $this->configRepository->updateValue('SENDCLOUD_MIN_LOG_LEVEL', $minLogLevel);
    }

    /**
     * Retrieves integration name
     *
     * @return string
     */
    public function getIntegrationName(): string
    {
        return self::INTEGRATION_NAME;
    }

    /**
     * Return whether default logger is enabled or not
     *
     * @return bool
     * @throws DBALException
     */
    public function isDefaultLoggerEnabled(): bool
    {
        return ((int)$this->configRepository->getValue('SENDCLOUD_DEFAULT_LOGGER_STATUS')) === 1;
    }

    /**
     * Gets maximum number of failed task execution retries. System will retry task execution in case of error until
     * this number is reached. Return null to use default system value (5)
     *
     * @return int|null
     * @throws DBALException
     */
    public function getMaxTaskExecutionRetries(): ?int
    {
        return (int)$this->configRepository->getValue('SENDCLOUD_MAX_TASK_EXECUTION_RETRIES') ?: null;
    }

    /**
     * @param $maxTaskExecutionRetries
     *
     * @throws DBALException
     */
    public function setMaxTaskExecutionRetries($maxTaskExecutionRetries): void
    {
        $this->configRepository->updateValue('SENDCLOUD_MAX_TASK_EXECUTION_RETRIES', $maxTaskExecutionRetries);
    }

    /**
     * Retrieve public key if saved in storage, otherwise  null
     *
     * @return string|null
     * @throws DBALException
     */
    public function getPublicKey(): ?string
    {
        return $this->configRepository->getValue('SENDCLOUD_PUBLIC_KEY');
    }

    /**
     * Set integration secret key
     *
     * @param string $secretKey
     *
     * @throws DBALException
     */
    public function setSecretKey($secretKey): void
    {
        $this->configRepository->updateValue('SENDCLOUD_SECRET_KEY', $secretKey);
    }

    /**
     * Gets task execution context
     *
     * @return string Context in which task is being executed. If no context is provided empty string is returned
     *     (global context)
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Returns timeout for async request
     *
     * @return int
     */
    public function getAsyncRequestTimeout(): int
    {
        try {
            $configValue = $this->configRepository->getValue('SENDCLOUD_ASYNC_REQUEST_TIMEOUT');

            return $configValue ? (int)$configValue : self::ASYNC_PROCESS_TIMEOUT;
        } catch (\Exception $exception) {
            Logger::logError(
                "An error occurred when reading async request timeout from config table: {$exception->getMessage()}",
                'Integration'
            );

            return self::ASYNC_PROCESS_TIMEOUT;
        }
    }

    /**
     * Set timeout for async request
     *
     * @param int $timeout
     *
     * @throws DBALException
     */
    public function setAsyncRequestTimeout(int $timeout): void
    {
        $this->configRepository->updateValue('SENDCLOUD_ASYNC_REQUEST_TIMEOUT', $timeout);
    }

    /**
     * Save user information in integration database
     *
     * @param array $userInfo
     *
     * @throws DBALException
     */
    public function setUserInfo($userInfo): void
    {
        $this->configRepository->updateValue('SENDCLOUD_USER_INFO', json_encode($userInfo));
        $this->userInfo = $userInfo;
    }

    /**
     * Returns default batch size
     *
     * @return int
     * @throws DBALException
     */
    public function getDefaultBatchSize(): int
    {
        return (int)$this->configRepository->getValue('SENDCLOUD_BATCH_SIZE') ?: self::DEFAULT_BATCH_SIZE;
    }

    /**
     * Sets synchronization batch size.
     *
     * @param int $batchSize
     *
     * @throws DBALException
     */
    public function setDefaultBatchSize($batchSize): void
    {
        $this->configRepository->updateValue('SENDCLOUD_BATCH_SIZE', $batchSize);
    }

    /**
     * Gets the number of maximum allowed started task at the point in time. This number will determine how many tasks
     * can be in "in_progress" status at the same time
     *
     * @return int
     * @throws DBALException
     */
    public function getMaxStartedTasksLimit(): int
    {
        return (int)$this->configRepository->getValue('SENDCLOUD_MAX_STARTED_TASK_LIMIT')
            ?: self::DEFAULT_MAX_STARTED_TASK_LIMIT;
    }

    /**
     * @param int $maxStartedTaskLimit
     *
     * @throws DBALException
     */
    public function setMaxStartedTaskLimit($maxStartedTaskLimit): void
    {
        $this->configRepository->updateValue('SENDCLOUD_MAX_STARTED_TASK_LIMIT', $maxStartedTaskLimit);
    }

    /**
     * Returns name of current shop.
     *
     * @return string
     * @throws InconsistentCriteriaIdsException
     */
    public function getShopName(): string
    {
        return $this->systemConfigurationRepository->getDefaultShopName();
    }

    /**
     * Returns web hook endpoint
     *
     * @param bool $addToken
     *
     * @return string
     * @throws DBALException
     */
    public function getWebHookEndpoint($addToken = false): string
    {
        $params = [];
        if ($addToken) {
            /** @var GuidProvider $guidProvider */
            $guidProvider = ServiceRegister::getService(GuidProvider::CLASS_NAME);
            $token = md5($guidProvider->generateGuid());
            $params['token'] = $token;

            $this->setWebHookToken($token);
            $this->setWebHookTokenTime(time());
        }

        $url = $this->urlGenerator->generate('api.sendcloud.webhook', $params, UrlGeneratorInterface::ABSOLUTE_URL);

        // only for development purposes, just uncomment this section
        if (!empty(self::$callbackMap['host']) && !empty(self::$callbackMap['replace'])) {
            $url = str_replace(self::$callbackMap['host'], self::$callbackMap['replace'], $url);
        }

        return $url;
    }

    /**
     * @inheritDoc
     */
    public function getWebHookToken(): string
    {
        return (string)$this->configRepository->getValue('WEBHOOK_TOKEN');
    }

    /**
     * Checks if webhook token is valid
     *
     * @param string $token
     *
     * @return bool
     * @throws DBALException
     */
    public function isWebHookTokenValid($token): bool
    {
        $savedToken = $this->configRepository->getValue('WEBHOOK_TOKEN');
        $tokenTime = (int)$this->configRepository->getValue('WEBHOOK_TOKEN_TIME');

        $tokenValidUntil = $tokenTime + self::TOKEN_VALID_FOR;

        return $token === $savedToken && $tokenValidUntil >= time();
    }

    /**
     * Automatic task runner wakeup delay in seconds. Task runner will sleep at the end of its lifecycle for this value
     * seconds before it sends wakeup signal for a new lifecycle. Return null to use default system value (10)
     *
     * @return int|null
     * @throws DBALException
     */
    public function getTaskRunnerWakeupDelay(): ?int
    {
        return (int)$this->configRepository->getValue('SENDCLOUD_TASK_RUNNER_WAKEUP_DELAY') ?: null;
    }

    /**
     * @param int $taskRunnerWakeUpDelay
     *
     * @throws DBALException
     */
    public function setTaskRunnerWakeUpDelay($taskRunnerWakeUpDelay): void
    {
        $this->configRepository->updateValue('SENDCLOUD_TASK_RUNNER_WAKEUP_DELAY', $taskRunnerWakeUpDelay);
    }

    /**
     * Gets maximal time in seconds allowed for runner instance to stay in alive (running) status. After this period
     * system will automatically start new runner instance and shutdown old one. Return null to use default system
     * value (60)
     *
     * @return int|null
     * @throws DBALException
     */
    public function getTaskRunnerMaxAliveTime(): ?int
    {
        return (int)$this->configRepository->getValue('SENDCLOUD_MAX_ALIVE_TIME') ?: null;
    }

    /**
     * @param int $taskRunnerMaxAliveTime
     *
     * @throws DBALException
     */
    public function setTaskRunnerMaxAliveTime($taskRunnerMaxAliveTime): void
    {
        $this->configRepository->updateValue('SENDCLOUD_MAX_ALIVE_TIME', $taskRunnerMaxAliveTime);
    }

    /**
     * Gets max inactivity period for a task in seconds. After inactivity period is passed, system will fail such tasks
     * as expired. Return null to use default system value (30)
     *
     * @return int|null
     * @throws DBALException
     */
    public function getMaxTaskInactivityPeriod(): ?int
    {
        return (int)$this->configRepository->getValue('SENDCLOUD_MAX_TASK_INACTIVITY_PERIOD') ?: null;
    }

    /**
     * @param int $maxTaskInactivityPeriod
     *
     * @throws DBALException
     */
    public function setMaxTaskInactivityPeriod($maxTaskInactivityPeriod): void
    {
        $this->configRepository->updateValue('SENDCLOUD_MAX_TASK_INACTIVITY_PERIOD', $maxTaskInactivityPeriod);
    }

    /**
     * @return array
     * @throws DBALException
     */
    public function getTaskRunnerStatus(): array
    {
        $status = json_decode($this->configRepository->getValue('SENDCLOUD_TASK_RUNNER_STATUS'), true);

        return (array)$status;
    }

    /**
     * Gets integration queue name
     *
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->getIntegrationName() . ' - Default';
    }

    /**
     * Returns service point enabled flag
     *
     * @return bool
     * @throws DBALException
     */
    public function isServicePointEnabled(): bool
    {
        $timestamp = (int)$this->configRepository->getValue('SENDCLOUD_CONFIG_UPDATE_DATE');
        if (!$timestamp || time() > $timestamp + self::CONFIG_VALID_FOR) {
            //refresh config value
            $this->refreshIntegrationConfig();
        }

        $value = json_decode($this->configRepository->getValue('SENDCLOUD_SERVICE_POINT_ENABLED'), true);

        return $value === true;
    }

    /**
     * Sets service point enabled flag
     *
     * @param bool $enabled
     *
     * @throws DBALException
     */
    public function setServicePointEnabled($enabled): void
    {
        $this->configRepository->updateValue('SENDCLOUD_SERVICE_POINT_ENABLED', json_encode($enabled));
        $this->configRepository->updateValue('SENDCLOUD_CONFIG_UPDATE_DATE', time());
    }

    /**
     * Returns list of enabled carriers
     *
     * @return array
     * @throws DBALException
     */
    public function getCarriers(): array
    {
        $timestamp = (int)$this->configRepository->getValue('SENDCLOUD_CONFIG_UPDATE_DATE');
        if (!$timestamp || time() > $timestamp + self::CONFIG_VALID_FOR) {
            //refresh config value
            $this->refreshIntegrationConfig();
        }

        $value = json_decode($this->configRepository->getValue('SENDCLOUD_CARRIERS'), true);

        return is_array($value) ? $value : [];
    }

    /**
     * Sets a list of available carriers
     *
     * @param array $carriers
     *
     * @throws DBALException
     */
    public function setCarriers(array $carriers = []): void
    {
        $this->configRepository->updateValue('SENDCLOUD_CARRIERS', json_encode($carriers));
        $this->configRepository->updateValue('SENDCLOUD_CONFIG_UPDATE_DATE', time());
    }

    /**
     * Stores service point delivery method id into db
     *
     * @param int|string $id
     *
     * @throws DBALException
     */
    public function saveSendCloudServicePointDeliveryMethodId($id): void
    {
        $this->configRepository->updateValue('SENDCLOUD_SERVICE_POINT_DELIVERY_METHOD_ID', $id);
    }

    /**
     * Get service point delivery method id into db
     *
     * @return string|null
     * @throws DBALException
     */
    public function getSendCloudServicePointDeliveryMethodId(): ?string
    {
        return $this->configRepository->getValue('SENDCLOUD_SERVICE_POINT_DELIVERY_METHOD_ID');
    }

    /**
     * @throws DBALException
     * @throws \Sendcloud\Shipping\Core\Infrastructure\Utility\Exceptions\HttpAuthenticationException
     * @throws \Sendcloud\Shipping\Core\Infrastructure\Utility\Exceptions\HttpCommunicationException
     * @throws \Sendcloud\Shipping\Core\Infrastructure\Utility\Exceptions\HttpRequestException
     */
    public function updateWebhookUrl(): void
    {
        $integrationId = $this->getIntegrationId();
        if (empty($integrationId)) {
            return;
        }

        /** @var \Sendcloud\Shipping\Core\BusinessLogic\Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        $integrationData = new IntegrationDTO(
            $integrationId,
            $this->getShopName(),
            $this->getIntegrationName(),
            $this->isServicePointEnabled(),
            $this->getCarriers(),
            null,
            null,
            true,
            $this->getWebHookEndpoint(true)
        );

        $proxy->updateIntegration($integrationData);
    }

    /**
     * Refreshes integration configuration by pulling it from SendCloud
     *
     * @throws DBALException
     */
    private function refreshIntegrationConfig(): void
    {
        if (empty($this->getPublicKey()) || empty($this->getSecretKey())) {
            return;
        }

        /** @var \Sendcloud\Shipping\Core\BusinessLogic\Proxy $proxy */
        $proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        try {
            $integration = $proxy->getIntegrationById($this->getIntegrationId());
            $this->setCarriers($integration->getServicePointCarriers());
            $this->setServicePointEnabled($integration->isServicePointsEnabled());
        } catch (\Exception $e) {
            $this->setCarriers([]);
            $this->setServicePointEnabled(false);
            Logger::logError($e->getMessage(), 'Integration');
        }
    }

    /**
     * Sets webhook token in configuration.
     *
     * @param string $token
     *
     * @throws DBALException
     */
    public function setWebHookToken($token): void
    {
        $this->configRepository->updateValue('WEBHOOK_TOKEN', $token);
    }

    /**
     * Sets webhook token time in configuration.
     *
     * @param int $time
     *
     * @throws DBALException
     */
    public function setWebHookTokenTime($time): void
    {
        $this->configRepository->updateValue('WEBHOOK_TOKEN_TIME', $time);
    }

    /**
     * @inheritDoc
     * @throws DBALException
     */
    public function getCompletedTasksRetentionPeriod(): int
    {
        $configValue = $this->configRepository->getValue('SENDCLOUD_COMPLETED_TASKS_RETENTION_PERIOD');

        return $configValue ?? self::COMPLETED_TASKS_RETENTION_PERIOD;
    }

    /**
     * @param int $retentionPeriod
     * @throws DBALException
     */
    public function setCompletedTasksRetentionPeriod(int $retentionPeriod): void
    {
        $this->configRepository->updateValue('SENDCLOUD_COMPLETED_TASKS_RETENTION_PERIOD', $retentionPeriod);
    }

    /**
     * @inheritDoc
     * @throws DBALException
     */
    public function getFailedTasksRetentionPeriod(): int
    {
        $configValue = $this->configRepository->getValue('SENDCLOUD_FAILED_TASKS_RETENTION_PERIOD');

        return $configValue ?? self::FAILED_TASKS_RETENTION_PERIOD;
    }

    /**
     * @param int $retentionPeriod
     * @throws DBALException
     */
    public function setFailedTasksRetentionPeriod(int $retentionPeriod): void
    {
        $this->configRepository->updateValue('SENDCLOUD_FAILED_TASKS_RETENTION_PERIOD', $retentionPeriod);
    }

    /**
     * @inheritDoc
     * @throws DBALException
     */
    public function getOldTaskCleanupTimeThreshold(): int
    {
        $configValue = $this->configRepository->getValue('SENDCLOUD_OLD_TASKS_CLEANUP_THRESHOLD');

        return $configValue ?? self::OLD_TASKS_CLEANUP_THRESHOLD;
    }

    /**
     * @param int $threshold
     * @throws DBALException
     */
    public function setOldTaskCleanupTimeThreshold(int $threshold): void
    {
        $this->configRepository->updateValue('SENDCLOUD_OLD_TASKS_CLEANUP_THRESHOLD', $threshold);
    }

    /**
     * @inheritDoc
     */
    public function getOldTaskCleanupQueueName(): string
    {
        return 'oldTaskCleanup';
    }

    /**
     * Gets default shipment type
     *
     * @throws DBALException
     */
    public function getDefaultShipmentType(): ?string
    {
        return  $this->configRepository->getCustomsField('SENDCLOUD_DEFAULT_SHIPMENT_TYPE');
    }

    /**
     * Sets default shipment type
     *
     * @param string $defaultShipmentType
     * @throws DBALException
     */
    public function setDefaultShipmentType(string $defaultShipmentType): void
    {
        $this->configRepository->updateValue('SENDCLOUD_DEFAULT_SHIPMENT_TYPE', $defaultShipmentType);
    }

    /**
     * Gets default hs code
     *
     * @throws DBALException
     */
    public function getDefaultHsCode(): ?string
    {
        return $this->configRepository->getCustomsField('SENDCLOUD_DEFAULT_HS_CODE');
    }

    /**
     * Sets default hs code
     *
     * @param string $defaultHsCode
     * @throws DBALException
     */
    public function setDefaultHsCode(string $defaultHsCode): void
    {
        $this->configRepository->updateValue('SENDCLOUD_DEFAULT_HS_CODE', $defaultHsCode);
    }

    /**
     * Gets default country of origin
     */
    public function getDefaultOriginCountry(): ?string
    {
        return $this->configRepository->getCustomsField('SENDCLOUD_DEFAULT_ORIGIN_COUNTRY');
    }

    /**
     * Sets default country of origin
     *
     * @param string $defaultOriginCountry
     * @throws DBALException
     */
    public function setDefaultOriginCountry(string $defaultOriginCountry): void
    {
        $this->configRepository->updateValue('SENDCLOUD_DEFAULT_ORIGIN_COUNTRY', $defaultOriginCountry);
    }

    /**
     * Gets product attribute that hs code is mapped to
     */
    public function getMappedHsCode(): ?string
    {
        return $this->configRepository->getCustomsField('SENDCLOUD_MAPPED_HS_CODE');
    }

    /**
     * Sets product attribute that hs code is mapped to
     *
     * @param $mappedHsCode
     * @throws DBALException
     */
    public function setMappedHsCode(string $mappedHsCode): void
    {
        $this->configRepository->updateValue('SENDCLOUD_MAPPED_HS_CODE', $mappedHsCode);
    }

    /**
     * Gets product attribute that country of origin is mapped to
     */
    public function getMappedOriginCountry(): ?string
    {
        return $this->configRepository->getCustomsField('SENDCLOUD_MAPPED_ORIGIN_COUNTRY');
    }

    /**
     * Sets product attribute that country of origin is mapped to
     *
     * @param string $mappedOriginCountry
     * @throws DBALException
     */
    public function setMappedOriginCountry(string $mappedOriginCountry): void
    {
        $this->configRepository->updateValue('SENDCLOUD_MAPPED_ORIGIN_COUNTRY', $mappedOriginCountry);
    }

    /**
     * Provides entity specific queue name.
     *
     * @param string $type
     * @param string $id
     *
     * @return string
     */
    public function getEntityQueueName($type, $id)
    {
        return substr(hash('md5', $type . '-' . $id), 0, 16);
    }

    /**
     * @inheritDoc
     */
	public function getSendCloudPanelUrl(): string
	{
		return 'https://panel.sendcloud.sc/';
	}

    /**
     * @inheritDoc
     */
    public function getBaseApiUrl(): string
    {
        return 'https://api.sendcloud.com/';
    }

    /**
     * @inheritDoc
     */
    public function getConnectUrl(): string
    {
        return 'https://panel.sendcloud.sc/';
    }
}
