<?php

namespace Sendcloud\Shipping\Controller\API\Backend;

use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\ConnectService;
use Sendcloud\Shipping\Core\BusinessLogic\Sync\InitialSyncTask;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Queue;
use Sendcloud\Shipping\Service\Utility\Initializer;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class RouterController
 *
 * @package Sendcloud\Shipping\Controller\API\Backend
 */
#[Route(defaults: ["_routeScope" => ["api"]])]
class RouterController extends AbstractController
{
    public const WELCOME_STATE_CODE = 'welcome';
    public const DASHBOARD_STATE_CODE = 'dashboard';

    /**
     * @var ConnectService
     */
    private $connectService;
    /**
     * @var Queue
     */
    private $queueService;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * RouterController constructor.
     *
     * @param Initializer $initializer
     * @param ConnectService $connectService
     * @param Queue $queueService
     * @param Configuration $configService
     */
    public function __construct(
        Initializer $initializer,
        ConnectService $connectService,
        Queue $queueService,
        Configuration $configService
    ) {
        $initializer->registerServices();
        $this->connectService = $connectService;
        $this->queueService = $queueService;
        $this->configService = $configService;
    }

    /**
     * Returns page for display
     * @return JsonApiResponse
     */
    #[Route('/api/v{version}/sendcloud/router', name: 'api.sendcloud.router', methods: ["GET", "POST"])]
    #[Route('/api/sendcloud/router', name: 'api.sendcloud.router.new', methods: ["GET", "POST"])]
    public function handle(): JsonApiResponse
    {
        return new JsonApiResponse(['page' => $this->getPage()]);
    }

    /**
     * Returns page code
     *
     * @return string
     */
    private function getPage(): string
    {
        if (!$this->connectService->isIntegrationConnected()) {
            return  self::WELCOME_STATE_CODE;
        }

        $this->queueInitialSyncTaskIfNotQueued();

        return self::DASHBOARD_STATE_CODE;
    }

    /**
     * Queues InitialSyncTask if not queued
     */
    private function queueInitialSyncTaskIfNotQueued(): void
    {
        $initialSyncTaskItem = $this->queueService->findLatestByType(InitialSyncTask::getClassName());
        if (!$initialSyncTaskItem) {
            $this->queueInitialSync();
        }
    }

    /**
     * Creates and queues initial synchronization task
     *
     * @return void
     */
    private function queueInitialSync(): void
    {
        try {
            $this->queueService->enqueue($this->configService->getQueueName(), new InitialSyncTask());
        } catch (QueueStorageUnavailableException $e) {
            // If task enqueue fails do nothing but report that initial sync is in progress
        }
    }
}
