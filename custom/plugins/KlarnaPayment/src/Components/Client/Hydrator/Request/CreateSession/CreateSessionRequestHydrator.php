<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CreateSession;

use KlarnaPayment\Components\Client\Hydrator\Struct\Customer\CustomerStructHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\Delivery\DeliveryStructHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\LineItem\LineItemStructHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Struct\SalesTaxLineItem\SalesTaxLineItemStructHydratorInterface;
use KlarnaPayment\Components\Client\Request\CreateSessionRequest;
use KlarnaPayment\Components\Client\Struct\Options;
use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\Routing\RouterInterface;

class CreateSessionRequestHydrator implements CreateSessionRequestHydratorInterface
{
    /** @var LineItemStructHydratorInterface */
    private $lineItemHydrator;

    /** @var DeliveryStructHydratorInterface */
    private $deliveryHydrator;

    /** @var SalesTaxLineItemStructHydratorInterface */
    private $salesTaxLineItemHydrator;

    /** @var PaymentHelperInterface */
    private $paymentHelper;

    /** @var EntityRepository */
    private $countryRepository;

    /** @var CustomerStructHydratorInterface */
    private $customerHydrator;

    /** @var RouterInterface */
    private $router;

    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(
        LineItemStructHydratorInterface $lineItemHydrator,
        DeliveryStructHydratorInterface $deliveryHydrator,
        SalesTaxLineItemStructHydratorInterface $salesTaxLineItemHydrator,
        PaymentHelperInterface $paymentHelper,
        EntityRepository $countryRepository,
        CustomerStructHydratorInterface $customerHydrator,
        RouterInterface $router,
        ConfigReaderInterface $configReader
    ) {
        $this->lineItemHydrator         = $lineItemHydrator;
        $this->deliveryHydrator         = $deliveryHydrator;
        $this->salesTaxLineItemHydrator = $salesTaxLineItemHydrator;
        $this->paymentHelper            = $paymentHelper;
        $this->countryRepository        = $countryRepository;
        $this->customerHydrator         = $customerHydrator;
        $this->router                   = $router;
        $this->configReader             = $configReader;
    }

    public function hydrate(Cart $cart, SalesChannelContext $context): CreateSessionRequest
    {
        $totalTaxAmount = $this->getTotalTaxAmount($cart->getPrice()->getCalculatedTaxes());

        $options = new Options();
        $options->assign([
            'disable_confirmation_modals' => true,
        ]);

        $billingAddressCountry = $this->getBillingAddressCountry($context);

        $request = new CreateSessionRequest();
        $request->assign([
            'purchaseCountry'  => $this->paymentHelper->getShippingCountry($context)->getIso(),
            'purchaseCurrency' => $context->getCurrency()->getIsoCode(),
            'locale'           => $this->paymentHelper->getSalesChannelLocale($context)->getCode(),
            'options'          => $options,
            'orderAmount'      => $cart->getPrice()->getTotalPrice(),
            'orderTaxAmount'   => $totalTaxAmount,
            'orderLines'       => $this->hydrateOrderLines(
                $cart->getLineItems(),
                $cart->getDeliveries(),
                $context->getCurrency(),
                $context,
                $billingAddressCountry
            ),
            'salesChannel' => $context->getSalesChannel()->getId(),
        ]);

        $customer = $this->customerHydrator->hydrate($context);

        if ($customer === null) {
            return $request;
        }

        $request->assign([
            'customer' => $customer,
        ]);

        $config = $this->configReader->read($context->getSalesChannel()->getId());

        if ($config->get('kpUseAuthorizationCallback') === true) {
            $request->assign([
                'merchantUrls' => $this->getMerchantUrls($cart->getToken()),
            ]);
        }

        if ($billingAddressCountry !== null) {
            $request->assign([
                'locale'          => substr_replace($request->getLocale(), (string) $billingAddressCountry->getIso(), 3, 2),
                'purchaseCountry' => $billingAddressCountry->getIso(),
            ]);
        }

        return $request;
    }

    private function getTotalTaxAmount(CalculatedTaxCollection $taxes): float
    {
        $totalTaxAmount = 0;

        foreach ($taxes as $tax) {
            $totalTaxAmount += $tax->getTax();
        }

        return $totalTaxAmount;
    }

    private function hydrateOrderLines(LineItemCollection $lineItems, DeliveryCollection $deliveries, CurrencyEntity $currency, SalesChannelContext $salesChannelContext, ?CountryEntity $country): array
    {
        $orderLines = [];

        if ($country !== null && $country->getIso() === PaymentMethodInstaller::KLARNA_API_REGION_US) {
            // lineItems with net prices and sales-tax extracted as seperate lineitem for USA
            return $this->salesTaxLineItemHydrator->hydrate($lineItems, $deliveries, $currency, $salesChannelContext);
        }

        foreach ($this->lineItemHydrator->hydrate($lineItems, $currency, $salesChannelContext) as $orderLine) {
            $orderLines[] = $orderLine;
        }

        foreach ($this->deliveryHydrator->hydrate(
            $deliveries,
            $currency,
            $salesChannelContext->getContext()
        ) as $orderLine) {
            $orderLines[] = $orderLine;
        }

        return array_filter($orderLines);
    }

    private function getBillingAddressCountry(SalesChannelContext $context): ?CountryEntity
    {
        $customer = $context->getCustomer();

        if ($customer === null) {
            return null;
        }

        if ($customer->getActiveBillingAddress() === null) {
            return null;
        }

        $criteria = new Criteria([
            $customer->getActiveBillingAddress()->getCountryId(),
        ]);

        return $this->countryRepository->search($criteria, $context->getContext())->first();
    }

    private function getMerchantUrls(string $cartToken): array
    {
        $authorizationUrl = $this->router->generate(
            'widgets.klarna.callback.authorization',
            [
                'cart_token' => $cartToken,
            ],
            RouterInterface::ABSOLUTE_URL
        );

        return [
            'authorization' => $authorizationUrl,
        ];
    }
}
