<?php

namespace Sendcloud\Shipping\Subscriber;

use Doctrine\DBAL\DBALException;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\OrderService;
use Sendcloud\Shipping\Entity\Customer\CustomerRepository;
use Sendcloud\Shipping\Entity\ServicePoint\ServicePointEntityRepository;
use Sendcloud\Shipping\Entity\Shipment\ShipmentEntityRepository;
use Sendcloud\Shipping\Service\Business\ConfigurationService;
use Sendcloud\Shipping\Service\Utility\Initializer;
use Sendcloud\Shipping\Struct\DisableSubmitButton;
use Sendcloud\Shipping\Struct\ServicePointConfig;
use Sendcloud\Shipping\Struct\ServicePointInfo;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\PlatformRequest;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class CheckoutSubscriber
 *
 * @package Sendcloud\Shipping\\Subscriber
 */
class CheckoutSubscriber implements EventSubscriberInterface
{
    private static $removeServicePoint = true;

    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var ConfigurationService
     */
    private $configService;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var ShipmentEntityRepository
     */
    private $shipmentRepository;
    /**
     * @var ServicePointEntityRepository
     */
    private $servicePointRepository;
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var RequestStack
     */
    private $requestStack;
    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * CheckoutSubscriber constructor.
     *
     * @param Initializer $initializer
     * @param ConfigurationService $configService
     * @param UrlGeneratorInterface $urlGenerator
     * @param ShipmentEntityRepository $shipmentRepository
     * @param ServicePointEntityRepository $servicePointRepository
     * @param CustomerRepository $customerRepository
     * @param OrderService $orderService
     * @param RequestStack $requestStack
     * @param ParameterBagInterface $params
     */
    public function __construct(
        Initializer $initializer,
        ConfigurationService $configService,
        UrlGeneratorInterface $urlGenerator,
        ShipmentEntityRepository $shipmentRepository,
        ServicePointEntityRepository $servicePointRepository,
        CustomerRepository $customerRepository,
        OrderService $orderService,
        RequestStack $requestStack,
        ParameterBagInterface $params
    ) {
        $initializer->registerServices();
        $this->configService = $configService;
        $this->urlGenerator = $urlGenerator;
        $this->shipmentRepository = $shipmentRepository;
        $this->servicePointRepository = $servicePointRepository;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->requestStack = $requestStack;
        $this->params = $params;
    }

    /**
     * @inheritDoc
     *
     * @return array
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => 'onConfirmPageLoad',
            CheckoutFinishPageLoadedEvent::class => 'onFinishPageLoad',
            CustomerEvents::CUSTOMER_WRITTEN_EVENT => 'onCustomerUpdate',
            CustomerEvents::CUSTOMER_ADDRESS_WRITTEN_EVENT => 'onCustomerAddressChange',
            CustomerEvents::CUSTOMER_LOGOUT_EVENT => 'onCustomerLogout',
            KernelEvents::CONTROLLER => 'setRemoveServicePointFlag'
        ];
    }

    /**
     * On customer address event change
     *
     * @param EntityWrittenEvent $event
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function onCustomerAddressChange(EntityWrittenEvent $event): void
    {
        $payloads = $event->getPayloads();
        $payload = reset($payloads);
        if (empty($payload['customerId'])) {
            return;
        }

        $customer = $this->customerRepository->getCustomerById($payload['customerId']);
        if ($customer && $customer->getDefaultShippingAddressId() === $payload['id']) {
            $this->removeSelectedServicePoint($customer->getCustomerNumber());
        }
    }

    /**
     * Saves data for delete
     *
     * @param ControllerEvent $controllerEvent
     */
    public function setRemoveServicePointFlag(ControllerEvent $controllerEvent): void
    {
        $request = $controllerEvent->getRequest();
        $routeName = $request->get('_route');
        self::$removeServicePoint = ($routeName !== 'frontend.checkout.finish.order');
    }

    /**
     * Unset selected service point if customer is logout
     *
     * @param CustomerLogoutEvent $customerLogoutEvent
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function onCustomerLogout(CustomerLogoutEvent $customerLogoutEvent): void
    {
        $customer = $customerLogoutEvent->getCustomer();
        if ($customer) {
            $this->removeSelectedServicePoint($customer->getCustomerNumber());
        }
    }

    /**
     * Unset selected service point if user change his data
     *
     * @param EntityWrittenEvent $event
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function onCustomerUpdate(EntityWrittenEvent $event): void
    {
        if (self::$removeServicePoint) {
            $request = $this->requestStack->getCurrentRequest();
            $ids = $event->getIds();
            $customerId = reset($ids);
            $customer = $this->customerRepository->getCustomerById($customerId);
            if ($customer && $this->isShippingAddressChanged($request, $customer)) {
                $this->removeSelectedServicePoint($customer->getCustomerNumber());
            }
        }
    }

    /**
     * Check if shipping address changed
     *
     * @param Request|null $request
     * @param CustomerEntity $customer
     *
     * @return bool
     */
    private function isShippingAddressChanged(?Request $request, CustomerEntity $customer): bool
    {
        $selectAddress = $request ? $request->get('selectAddress') : null;
        $selectAddressType = !empty($selectAddress['type']) ? $selectAddress['type'] : null;
        $route = $request ? $request->get('_route') : null;
        $type = $request ? $request->get('type') : null;

        $addresses = $customer->getAddresses();
        $addressesCount = $addresses ? $addresses->count() : 0;

        return ($selectAddressType === 'shipping' ||
                ($selectAddressType === 'billing' && ($customer->getAddresses() !== null && $addressesCount === 1))) ||
            (($route === 'frontend.account.address.set-default-address' && $type === 'shipping'));
    }

    /**
     * Removes selected service point for specific customer

     * @param string $customerNumber
     *
     * @throws InconsistentCriteriaIdsException
     */
    private function removeSelectedServicePoint(string $customerNumber): void
    {
        $this->servicePointRepository->deleteServicePointByCustomerNumber($customerNumber);
    }

    /**
     * Extend order info with service point location
     *
     * @param CheckoutFinishPageLoadedEvent $event
     *
     * @throws InconsistentCriteriaIdsException
     */
    public function onFinishPageLoad(CheckoutFinishPageLoadedEvent $event): void
    {
        $shipment = $this->shipmentRepository->getShipmentByOrderNumber($event->getPage()->getOrder()->getOrderNumber());
        if ($shipment) {
            $servicePointInfo = json_decode($shipment->get('servicePointInfo'), true);
            if ($servicePointInfo) {
                $event->getPage()->getOrder()->addExtension('sendcloud', new ServicePointInfo((array)$servicePointInfo));
            }
        }
    }

    /**
     * @param CheckoutConfirmPageLoadedEvent $event
     * @return void
     *
     * @throws DBALException
     */
    public function onConfirmPageLoad(CheckoutConfirmPageLoadedEvent $event): void
    {
        $shippingMethods = $event->getPage()->getShippingMethods();
        $servicePointDeliveryId = $this->configService->getSendCloudServicePointDeliveryMethodId();

        /** @var ShippingMethodEntity $shippingMethod */
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->getId() === $servicePointDeliveryId) {
                $extensions = $shippingMethod->getExtensions();
                $extensions['sendcloud'] = new ServicePointConfig([
                    'isServicePointDelivery' => true,
                    'servicePointDeliveryId' => $servicePointDeliveryId,
                    'servicePointEndpointUrl' => $this->generateServicePointUrl(),
                    'apiKey' => $this->configService->getPublicKey(),
                    'carriers' => implode(',', $this->configService->getCarriers()),
                    'weight' => $this->orderService->calculateTotalWeight($event->getPage()->getCart()->getLineItems()),
                ]);

                $shippingMethod->setExtensions($extensions);
                $shippingMethod->jsonSerialize();
            }
        }

        $deliveries = $event->getPage()->getCart()->getDeliveries();
        /** @var Delivery $delivery */
        foreach ($deliveries as $delivery) {
            if ($delivery->getShippingMethod()->getId() === $servicePointDeliveryId) {
                $customer = $event->getSalesChannelContext()->getCustomer();
                if ($customer) {
                    $servicePoint = $this->servicePointRepository->getServicePointByCustomerNumber($customer->getCustomerNumber());
                    if (!$servicePoint) {
                        $event->getPage()->addExtension('sendcloud', new DisableSubmitButton(true));
                    }
                }
            }
        }
    }

    /**
     * @return string
     */
    private function generateServicePointUrl(): string
    {
        $routeName = 'api.sendcloud.servicepoint.new';
        $params = [];
        if (version_compare($this->params->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $routeName = 'api.sendcloud.servicepoint';
            $params['version'] = PlatformRequest::API_VERSION;
        }

        return $this->urlGenerator->generate($routeName, $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
