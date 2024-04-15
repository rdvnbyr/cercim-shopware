<?php

namespace Sendcloud\Shipping\Service\Utility;

use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\ConnectService;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\OrderService;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Proxy as ProxyInterface;
use Sendcloud\Shipping\Core\BusinessLogic\OldQueueItemCleanupTickHandler;
use Sendcloud\Shipping\Core\BusinessLogic\Proxy;
use Sendcloud\Shipping\Core\BusinessLogic\ProxyTransformer;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\TaskRunnerStatusStorage as TaskRunnerStatusStorageInterface;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup as TaskRunnerWakeupInterface;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\HttpClient;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\TaskQueueStorage;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Queue;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\TaskEvents\TickEvent;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\TaskRunner;
use Sendcloud\Shipping\Core\Infrastructure\Utility\Events\EventBus;
use Sendcloud\Shipping\Core\Infrastructure\Utility\GuidProvider;
use Sendcloud\Shipping\Core\Infrastructure\Utility\TimeProvider;

/**
 * Class Initializer
 *
 * @package Sendcloud\Shipping\Service\Utility
 */
class Initializer
{
    /**
     * @var TimeProvider
     */
    private $timeProvider;
    /**
     * @var Queue
     */
    private $queue;
    /**
     * @var TaskRunnerWakeupInterface
     */
    private $taskRunnerWakeUp;
    /**
     * @var TaskRunner
     */
    private $taskRunner;
    /**
     * @var GuidProvider
     */
    private $guidProvider;
    /**
     * @var TaskRunnerStatusStorageInterface
     */
    private $taskRunnerStatusStorage;
    /**
     * @var DefaultLoggerAdapter
     */
    private $defaultLogger;
    /**
     * @var ShopLoggerAdapter
     */
    private $shopLoggerAdapter;
    /**
     * @var HttpClient
     */
    private $httpClient;
    /**
     * @var AsyncProcessStarter
     */
    private $asyncProcessStarter;
    /**
     * @var Configuration
     */
    private $configuration;
    /**
     * @var TaskQueueStorage
     */
    private $taskQueueStorage;
    /**
     * @var ConnectService
     */
    private $connectService;
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * Initializer constructor.
     *
     * @param TimeProvider $timeProvider
     * @param Queue $queue
     * @param TaskRunnerWakeupInterface $taskRunnerWakeUp
     * @param TaskRunner $taskRunner
     * @param GuidProvider $guidProvider
     * @param TaskRunnerStatusStorageInterface $taskRunnerStatusStorage
     * @param HttpClient $httpClient
     * @param AsyncProcessStarter $asyncProcessStarter
     * @param DefaultLoggerAdapter $defaultLogger
     * @param ShopLoggerAdapter $shopLoggerAdapter
     * @param Configuration $configuration
     * @param TaskQueueStorage $taskQueueStorage
     * @param ConnectService $connectService
     * @param OrderService $orderService
     */
    public function __construct(
        TimeProvider $timeProvider,
        Queue $queue,
        TaskRunnerWakeupInterface $taskRunnerWakeUp,
        TaskRunner $taskRunner,
        GuidProvider $guidProvider,
        TaskRunnerStatusStorageInterface $taskRunnerStatusStorage,
        HttpClient $httpClient,
        AsyncProcessStarter $asyncProcessStarter,
        DefaultLoggerAdapter $defaultLogger,
        ShopLoggerAdapter $shopLoggerAdapter,
        Configuration $configuration,
        TaskQueueStorage $taskQueueStorage,
        ConnectService $connectService,
        OrderService $orderService
    ) {
        $this->timeProvider = $timeProvider;
        $this->queue = $queue;
        $this->taskRunnerWakeUp = $taskRunnerWakeUp;
        $this->taskRunner = $taskRunner;
        $this->guidProvider = $guidProvider;
        $this->taskRunnerStatusStorage = $taskRunnerStatusStorage;
        $this->defaultLogger = $defaultLogger;
        $this->shopLoggerAdapter = $shopLoggerAdapter;
        $this->httpClient = $httpClient;
        $this->asyncProcessStarter = $asyncProcessStarter;
        $this->configuration = $configuration;
        $this->taskQueueStorage = $taskQueueStorage;

        $this->connectService = $connectService;
        $this->orderService = $orderService;
    }

    /**
     * Register all services
     */
    public function registerServices(): void
    {
        try {
            $this->registerInfrastructureServices();
            $this->registerBusinessServices();
            $this->registerEventHandlers();
        } catch (\InvalidArgumentException $exception) {
            //
        }
    }

    /**
     * Register infrastructure services
     */
    private function registerInfrastructureServices(): void
    {
        ServiceRegister::registerService(
            TimeProvider::CLASS_NAME,
            function () {
                return $this->timeProvider;
            }
        );

        ServiceRegister::registerService(
            EventBus::CLASS_NAME,
            function () {
                return EventBus::getInstance();
            }
        );

        ServiceRegister::registerService(
            Queue::CLASS_NAME,
            function () {
                return $this->queue;
            }
        );

        ServiceRegister::registerService(
            TaskRunnerWakeupInterface::CLASS_NAME,
            function () {
                return $this->taskRunnerWakeUp;
            }
        );

        ServiceRegister::registerService(
            TaskRunner::CLASS_NAME,
            function () {
                return $this->taskRunner;
            }
        );

        ServiceRegister::registerService(
            GuidProvider::CLASS_NAME,
            function () {
                return $this->guidProvider;
            }
        );

        ServiceRegister::registerService(
            DefaultLoggerAdapter::CLASS_NAME,
            function () {
                return $this->defaultLogger;
            }
        );

        ServiceRegister::registerService(
            ShopLoggerAdapter::CLASS_NAME,
            function () {
                return $this->shopLoggerAdapter;
            }
        );

        ServiceRegister::registerService(
            TaskRunnerStatusStorageInterface::CLASS_NAME,
            function () {
                return $this->taskRunnerStatusStorage;
            }
        );

        ServiceRegister::registerService(
            HttpClient::CLASS_NAME,
            function () {
                return $this->httpClient;
            }
        );

        ServiceRegister::registerService(
            AsyncProcessStarter::CLASS_NAME,
            function () {
                return $this->asyncProcessStarter;
            }
        );

        ServiceRegister::registerService(
            Configuration::CLASS_NAME,
            function () {
                return $this->configuration;
            }
        );

        ServiceRegister::registerService(
            TaskQueueStorage::CLASS_NAME,
            function () {
                return $this->taskQueueStorage;
            }
        );
    }

    /**
     * Register business services
     */
    private function registerBusinessServices(): void
    {
        ServiceRegister::registerService(
            ConnectService::CLASS_NAME,
            function () {
                return $this->connectService;
            }
        );

        ServiceRegister::registerService(
            OrderService::CLASS_NAME,
            function () {
                return $this->orderService;
            }
        );

        ServiceRegister::registerService(
            ProxyInterface::CLASS_NAME,
            function () {
                return new Proxy();
            }
        );

        ServiceRegister::registerService(
            ProxyTransformer::CLASS_NAME,
            function () {
                return new ProxyTransformer();
            }
        );
    }

    /**
     * Register business logic event handlers
     */
    private function registerEventHandlers()
    {
        EventBus::getInstance()->when(TickEvent::CLASS_NAME, function () {
            $handler = new OldQueueItemCleanupTickHandler();
            $handler->handle();
        });
    }
}
