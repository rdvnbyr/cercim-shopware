<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\Extension\TemplateData\CheckoutDataExtension;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Core\Checkout\Cart\Error\MissingStateError;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Storefront\Page\Account\Order\AccountEditOrderPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutEventListener implements EventSubscriberInterface
{
    /** @var PaymentHelperInterface */
    private $paymentHelper;

    /** @var EntityRepository */
    private $orderAddressRepository;

    public function __construct(PaymentHelperInterface $paymentHelper, EntityRepository $orderAddressRepository)
    {
        $this->paymentHelper          = $paymentHelper;
        $this->orderAddressRepository = $orderAddressRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class  => [['addKlarnaTemplateData'], ['validateCountryState']],
            AccountEditOrderPageLoadedEvent::class => [['addKlarnaTemplateData']],
            CheckoutFinishPageLoadedEvent::class   => [['addOrderBillingAddress']],
        ];
    }

    public function addKlarnaTemplateData(PageLoadedEvent $event): void
    {
        if (!($event instanceof CheckoutConfirmPageLoadedEvent) && !($event instanceof AccountEditOrderPageLoadedEvent)) {
            return;
        }

        if ($this->paymentHelper->isKlarnaPaymentsEnabled($event->getSalesChannelContext())) {
            $type = CheckoutDataExtension::TYPE_PAYMENTS;
        } elseif ($this->paymentHelper->isKlarnaCheckoutEnabled($event->getSalesChannelContext())) {
            $type = CheckoutDataExtension::TYPE_CHECKOUT;
        } else {
            return;
        }

        $templateData = new CheckoutDataExtension();
        $templateData->assign([
            'klarnaType' => $type,
        ]);

        $event->getPage()->addExtension(CheckoutDataExtension::EXTENSION_NAME, $templateData);
    }

    public function addOrderBillingAddress(CheckoutFinishPageLoadedEvent $event): void
    {
        $order            = $event->getPage()->getOrder();
        $orderAddresses   = $order->getAddresses() ?? new OrderAddressCollection();
        $billingAddressId = $order->getBillingAddressId();

        if ($orderAddresses->has($billingAddressId)) {
            return;
        }

        $billingAddress = $this->orderAddressRepository->search((new Criteria([$billingAddressId]))->addAssociation('country'), $event->getContext())->first();

        $orderAddresses->set($billingAddressId, $billingAddress);
        $order->setAddresses($orderAddresses);
    }

    public function validateCountryState(CheckoutConfirmPageLoadedEvent $event): void
    {
        if (!($this->paymentHelper->isKlarnaPaymentsEnabled($event->getSalesChannelContext()) || $this->paymentHelper->isKlarnaCheckoutEnabled($event->getSalesChannelContext()))) {
            return;
        }

        if (!$this->paymentHelper->isKlarnaPaymentsSelected($event->getSalesChannelContext())) {
            return;
        }

        $customer = $event->getSalesChannelContext()->getCustomer();

        if ($customer === null) {
            return;
        }

        if (is_null($customer->getActiveBillingAddress())) {
            return;
        }

        $activeBillingAddress = $customer->getActiveBillingAddress();

        if ($activeBillingAddress->getCountry() === null) {
            return;
        }

        if ($activeBillingAddress->getCountry()->getIso() === PaymentMethodInstaller::KLARNA_API_REGION_US && $activeBillingAddress->getCountryStateId() === null) {
            $errors = $event->getPage()->getCart()->getErrors();
            $errors->add(new MissingStateError());

            $event->getPage()->getCart()->setErrors($errors);
        }
    }
}
