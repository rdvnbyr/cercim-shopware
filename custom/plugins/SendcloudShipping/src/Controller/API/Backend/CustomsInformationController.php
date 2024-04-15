<?php

namespace Sendcloud\Shipping\Controller\API\Backend;

use Sendcloud\Shipping\Core\BusinessLogic\Sync\InitialSyncTask;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Queue;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CustomsInformationController
 *
 * @package Sendcloud\Shipping\Controller\API\Backend
 */
#[Route(defaults: ["_routeScope" => ["api"]])]
class CustomsInformationController extends AbstractController
{
    /**
     * @var Queue
     */
    private $queueService;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * CustomsInformationController constructor.
     *
     * @param Queue $queueService
     * @param Configuration $configService
     */
    public function __construct(Queue $queueService, Configuration $configService)
    {
        $this->queueService = $queueService;
        $this->configService = $configService;
    }

    /**
     * Returns customs configuration
     *
     * @return JsonApiResponse
     */
    #[Route('/api/v{version}/sendcloud/getcustoms', name: 'api.sendcloud.getcustoms', methods: ["GET", "POST"])]
    #[Route('/api/sendcloud/getcustoms', name: 'api.sendcloud.getcustoms.new', methods: ["GET", "POST"])]
    public function getCustomsConfiguration(): JsonApiResponse
    {
        $config = [];
        if ($this->configService->getDefaultShipmentType() !== null &&
            $this->configService->getDefaultHsCode() !== null &&
            $this->configService->getDefaultOriginCountry() !== null &&
            $this->configService->getMappedHsCode() !== null &&
            $this->configService->getMappedOriginCountry() !== null) {
            $config['shipmentType'] = $this->configService->getDefaultShipmentType();
            $config['hsCode'] = $this->configService->getDefaultHsCode();
            $config['originCountry'] = $this->configService->getDefaultOriginCountry();
            $config['mappedHsCode'] = $this->configService->getMappedHsCode();
            $config['mappedOriginCountry'] = $this->configService->getMappedOriginCountry();
        }

        return new JsonApiResponse($config);
    }

    /**
     * Saves customs configuration
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    #[Route('/api/v{version}/sendcloud/savecustoms', name: 'api.sendcloud.savecustoms', methods: ["GET", "POST"])]
    #[Route('/api/sendcloud/savecustoms', name: 'api.sendcloud.savecustoms.new', methods: ["GET", "POST"])]
    public function saveCustomsConfiguration(Request $request): JsonApiResponse
    {
        if ($this->isConfigurationChanged($request)) {
            $this->queueInitialSync();
        }

        $this->configService->setDefaultShipmentType($request->get('shipmentType'));
        $this->configService->setDefaultHsCode($request->get('hsCode'));
        $this->configService->setDefaultOriginCountry($request->get('originCountry'));
        $this->configService->setMappedHsCode($request->get('mappedHsCode'));
        $this->configService->setMappedOriginCountry($request->get('mappedOriginCountry'));

        return new JsonApiResponse(['success' => true]);
    }

    /**
     * Checks if configuration has changed
     *
     * @param Request $request
     * @return bool
     */
    private function isConfigurationChanged(Request $request): bool
    {
        return $this->configService->getDefaultShipmentType() !== $request->get('shipmentType') ||
            $this->configService->getDefaultHsCode() !== $request->get('hsCode') ||
            $this->configService->getDefaultOriginCountry() !== $request->get('originCountry') ||
            $this->configService->getMappedHsCode() !== $request->get('mappedHsCode') ||
            $this->configService->getMappedOriginCountry() !== $request->get('mappedOriginCountry');
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
            Logger::logError("Queueing of InitialSyncTask failed: " . $e->getMessage());
        }
    }
}
