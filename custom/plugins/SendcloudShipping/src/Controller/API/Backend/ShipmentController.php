<?php

namespace Sendcloud\Shipping\Controller\API\Backend;

use Sendcloud\Shipping\Core\BusinessLogic\Sync\OrderSyncTask;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Queue;
use Sendcloud\Shipping\Entity\Order\OrderRepository;
use Sendcloud\Shipping\Entity\Shipment\ShipmentEntityRepository;
use Sendcloud\Shipping\Service\Business\ConfigurationService;
use Sendcloud\Shipping\Service\Utility\Initializer;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ShipmentController
 *
 * @package Sendcloud\Shipping\Controller\API\Backend
 */
#[Route(defaults: ["_routeScope" => ["api"]])]
class ShipmentController extends AbstractController
{
    public const SENDCLOUD_SCRIPT_URL = 'https://embed.sendcloud.sc/spp/1.0.0/api.min.js';

    /**
     * @var ShipmentEntityRepository
     */
    private $shipmentRepository;
    /**
     * @var ConfigurationService
     */
    private $configService;
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * ShipmentController constructor.
     *
     * @param Initializer $initializer
     * @param ShipmentEntityRepository $shipmentRepository
     * @param Configuration $configService
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        Initializer $initializer,
        ShipmentEntityRepository $shipmentRepository,
        Configuration $configService,
        OrderRepository $orderRepository
    ) {
        $initializer->registerServices();
        $this->shipmentRepository = $shipmentRepository;
        $this->configService = $configService;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Retrieves shipment info for the given order
     *
     * @param string $orderNumber
     *
     * @return JsonApiResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    #[Route('/api/v{version}/sendcloud/shipment/{orderNumber}', name: 'api.sendcloud.shipment.get', methods: ["GET"])]
    #[Route('/api/sendcloud/shipment/{orderNumber}', name: 'api.sendcloud.shipment.get.new', methods: ["GET"])]
    public function getShipmentInfo(string $orderNumber): JsonApiResponse
    {
        $shipment = $this->shipmentRepository->getShipmentByOrderNumber($orderNumber);

        $data = [
            'status' => $shipment ? $shipment->get('sendcloudStatus') : '',
            'trackingNumber' => $shipment ? $shipment->get('trackingNumber') : '',
            'trackingUrl' => $shipment ? $shipment->get('trackingUrl') : '',
            'servicePointInfo' => $shipment ? $shipment->get('servicePointInfo') : '',
            'apiKey' => $this->configService->getPublicKey(),
            'carriers' => $this->configService->getCarriers(),
            'sendcloudScriptUrl' => static::SENDCLOUD_SCRIPT_URL,
        ];

        return new JsonApiResponse($data);
    }

    /**
     * Saves service point information for the given order
     *
     * @param string $orderNumber
     * @param Request $request
     *
     * @return JsonApiResponse
     * @throws QueueStorageUnavailableException
     */
    #[Route('/api/v{version}/sendcloud/shipment/save/{orderNumber}', name: 'api.sendcloud.shipment.save', methods: ["POST"])]
    #[Route('/api/sendcloud/shipment/save/{orderNumber}', name: 'api.sendcloud.shipment.save.new', methods: ["POST"])]
    public function saveServicePointInformation(string $orderNumber, Request $request): JsonApiResponse
    {
        $success = false;
        $servicePoint = json_decode($request->getContent(), true);
        if (array_key_exists('id', $servicePoint)) {
            $this->shipmentRepository->updateServicePoint($orderNumber, $servicePoint);
            if ($order = $this->orderRepository->getOrderByNumber($orderNumber)) {
                $this->enqueueOrderSyncTask($order);
            }

            $success = true;
        }

        return new JsonApiResponse(['success' => $success]);
    }

    /**
     * @param OrderEntity $order
     *
     * @throws QueueStorageUnavailableException
     */
    private function enqueueOrderSyncTask(OrderEntity $order): void
    {
        /** @var Queue $queue */
        $queue = ServiceRegister::getService(Queue::CLASS_NAME);
        $task = new OrderSyncTask([$order->getId()]);
        $queue->enqueue($this->configService->getEntityQueueName('order', $order->getId()), $task);
    }
}
