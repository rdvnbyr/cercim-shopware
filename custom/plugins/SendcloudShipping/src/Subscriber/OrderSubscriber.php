<?php

namespace Sendcloud\Shipping\Subscriber;

use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\ConnectService;
use Sendcloud\Shipping\Core\BusinessLogic\Sync\OrderCancelTask;
use Sendcloud\Shipping\Core\BusinessLogic\Sync\OrderSyncTask;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\QueueStorageUnavailableException;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Queue;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Task;
use Sendcloud\Shipping\Entity\Order\OrderRepository;
use Sendcloud\Shipping\Entity\ServicePoint\ServicePointEntityRepository;
use Sendcloud\Shipping\Entity\Shipment\ShipmentEntityRepository;
use Sendcloud\Shipping\Service\Business\ConfigurationService;
use Sendcloud\Shipping\Service\Utility\Initializer;
use Sendcloud\Shipping\Struct\ServicePointInfo;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Content\MailTemplate\Service\Event\MailBeforeValidateEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Storefront\Page\Account\Order\AccountOrderPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class OrderSubscriber
 *
 * @package Sendcloud\Shipping\\Subscriber
 */
class OrderSubscriber implements EventSubscriberInterface
{
    private static $orderNumberMap = [];
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var ServicePointEntityRepository
     */
    private $servicePointRepository;
    /**
     * @var Queue
     */
    private $queue;
    /**
     * @var ConnectService
     */
    private $connectService;
    /**
     * @var ConfigurationService
     */
    private $configService;
    /**
     * @var ShipmentEntityRepository
     */
    private $shipmentRepository;
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * OrderSubscriber constructor.
     *
     * @param OrderRepository $orderRepository
     * @param ServicePointEntityRepository $servicePointEntityRepository
     * @param Initializer $initializer
     * @param ConnectService $connectService
     * @param Queue $queue
     * @param Configuration $configService
     * @param ShipmentEntityRepository $shipmentRepository
     */
    public function __construct(
        OrderRepository $orderRepository,
        ServicePointEntityRepository $servicePointEntityRepository,
        Initializer $initializer,
        ConnectService $connectService,
        Queue $queue,
        Configuration $configService,
        ShipmentEntityRepository $shipmentRepository,
        RequestStack $requestStack
    ) {
        $initializer->registerServices();
        $this->orderRepository = $orderRepository;
        $this->servicePointRepository = $servicePointEntityRepository;
        $this->connectService = $connectService;
        $this->queue = $queue;
        $this->configService = $configService;
        $this->shipmentRepository = $shipmentRepository;
        $this->requestStack = $requestStack;
    }

    /**
     * @inheritDoc
     *
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderSave',
            OrderEvents::ORDER_DELETED_EVENT => 'onOrderDelete',
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
            AccountOrderPageLoadedEvent::class => 'onOrderLoaded',
            ControllerEvent::class => 'saveDataForDelete',
            MailBeforeValidateEvent::class => 'onMailBeforeValidate',
        ];
    }

    /**
     * Add service point info for mail template
     *
     * @param CheckoutOrderPlacedEvent $event
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $order = $event->getOrder();
        if ($order) {
            $shipment = $this->shipmentRepository->getShipmentByOrderNumber($order->getOrderNumber());
            $servicePointInfo = $shipment ? json_decode($shipment->get('servicePointInfo'), true) : [];

            $order->addExtension('sendcloud', new ServicePointInfo((array)$servicePointInfo));
        }
    }

    /**
     * Adds service point information on order object if exists
     *
     * @param AccountOrderPageLoadedEvent $event
     * @throws InconsistentCriteriaIdsException
     */
    public function onOrderLoaded(AccountOrderPageLoadedEvent $event): void
    {
        /** @var OrderEntity $order */
        foreach ($event->getPage()->getOrders() as $order) {
            $shipment = $this->shipmentRepository->getShipmentByOrderNumber($order->getOrderNumber());
            if ($shipment) {
                $servicePointInfo = json_decode($shipment->get('servicePointInfo'), true);
                if ($servicePointInfo) {
                    $order->addExtension('sendcloud', new ServicePointInfo((array)$servicePointInfo));
                }
            }
        }
    }

    /**
     * Enqueues OrderCancelTask for each deleted order
     *
     * @param EntityDeletedEvent $event
     *
     * @throws QueueStorageUnavailableException
     */
    public function onOrderDelete(EntityDeletedEvent $event): void
    {
        $ids = $event->getIds();
        foreach ($ids as $id) {
            if (in_array($id, self::$orderNumberMap)) {
                Logger::logInfo("Order delete event detected. Deleted order id: {$id}", 'Integration');
                $this->enqueueTask($this->configService->getEntityQueueName('order', $id), new OrderCancelTask($id));
            }
        }
    }

    /**
     * @param EntityWrittenEvent $event
     * @return void
     * @throws QueueStorageUnavailableException
     */
    public function onOrderSave(EntityWrittenEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->get('_route') !== 'api.createVersion') {
            $ids = $event->getIds();
            $jsonIds = json_encode($ids);
            Logger::logInfo("Order created event detected. Order id: {$jsonIds}", 'Integration');
            $this->saveServicePointInfo(reset($ids));
            foreach ($ids as $id) {
                $this->enqueueTask($this->configService->getEntityQueueName('order', $id), new OrderSyncTask([$id]));
            }
        }
    }

    /**
     * Saves data for delete
     *
     * @param ControllerEvent $controllerEvent
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function saveDataForDelete(ControllerEvent $controllerEvent): void
    {
        $request = $controllerEvent->getRequest();
        $routeName = $request->get('_route');
        if ($routeName === 'api.order.delete') {
            $path = $request->get('path');
            // check if route contains subpaths
            if (!strpos($path, '/')) {
                $this->saveOrderNumber($path);
            }
        }
    }

    /**
     * Add service point data in order email template
     *
     * @param MailBeforeValidateEvent $event
     * @return void
     */
    public function onMailBeforeValidate(MailBeforeValidateEvent $event): void
    {
        $templateData = $event->getTemplateData();
        if (array_key_exists('order', $templateData)) {
            /*** @var OrderEntity $order */
            $order = $templateData['order'];
            $shipment = $this->shipmentRepository->getShipmentByOrderNumber($order->getOrderNumber());
            $servicePointInfo = $shipment ? json_decode($shipment->get('servicePointInfo'), true) : [];

            $order->addExtension('sendcloud', new ServicePointInfo((array)$servicePointInfo));
        }
    }

    /**
     * Enqueues given task
     *
     * @param string $queueName
     * @param Task $task
     *
     * @throws QueueStorageUnavailableException
     */
    private function enqueueTask(string $queueName, Task $task): void
    {
        if ($this->connectService->isIntegrationConnected()) {
            $this->queue->enqueue($queueName, $task);
        }
    }

    /**
     * @param string $orderId
     * @return void
     */
    private function saveServicePointInfo(string $orderId): void
    {
        $order = $this->orderRepository->getOrderById($orderId);

        if ($order && ($deliveries = $order->getDeliveries()) && ($customer = $order->getOrderCustomer())) {
            /** @var OrderDeliveryEntity $delivery */
            $delivery = $deliveries->first() ?: null;
            if ($delivery && $delivery->getShippingMethodId() === $this->configService->getSendCloudServicePointDeliveryMethodId()) {
                $servicePointEntity = $this->servicePointRepository->getServicePointByCustomerNumber($customer->getCustomerNumber());
                if ($servicePointEntity) {
                    $this->shipmentRepository->updateServicePoint($order->getOrderNumber(), (array)json_decode($servicePointEntity->get('servicePointInfo'), true));
                    $this->servicePointRepository->deleteServicePointByCustomerNumber($customer->getCustomerNumber());
                }
            }
        }

    }

    /**
     * Saves order number before it is deleted
     *
     * @param string $orderId
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function saveOrderNumber(string $orderId): void
    {
        $order = $this->orderRepository->getOrderById($orderId);
        if ($order) {
            self::$orderNumberMap[] = $orderId;
        }
    }
}
