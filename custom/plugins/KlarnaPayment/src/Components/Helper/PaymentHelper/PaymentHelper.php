<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\PaymentHelper;

use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class PaymentHelper implements PaymentHelperInterface
{
    private const KLARNA_PAYMENT_METHODS = [
        PaymentMethodInstaller::KLARNA_CHECKOUT,
        PaymentMethodInstaller::KLARNA_PAY_LATER,
        PaymentMethodInstaller::KLARNA_FINANCING,
        PaymentMethodInstaller::KLARNA_DIRECT_DEBIT,
        PaymentMethodInstaller::KLARNA_DIRECT_BANK_TRANSFER,
        PaymentMethodInstaller::KLARNA_CREDIT_CARD,
        PaymentMethodInstaller::KLARNA_PAY_NOW,
        // TODO: Enable once Klarna enables the payment method
        //PaymentMethodInstaller::KLARNA_PAY,
    ];

    /** @var EntityRepository */
    private $salesChannelRepository;

    /** @var null|SalesChannelEntity */
    private $salesChannel;

    /** @var EntityRepository */
    private $languageRepository;

    /** @var LanguageEntity[] */
    private $languages = [];

    public function __construct(EntityRepository $salesChannelRepository, EntityRepository $languageRepository)
    {
        $this->salesChannelRepository = $salesChannelRepository;
        $this->languageRepository     = $languageRepository;
    }

    public function isKlarnaCheckoutEnabled(SalesChannelContext $context): bool
    {
        $validPaymentMethods = array_keys(PaymentMethodInstaller::KLARNA_CHECKOUT_CODES);

        return $this->testPaymentMethodExistence($validPaymentMethods, $context);
    }

    public function isKlarnaPaymentsEnabled(SalesChannelContext $context): bool
    {
        $validPaymentMethods = array_keys(PaymentMethodInstaller::KLARNA_PAYMENTS_CODES);

        return $this->testPaymentMethodExistence($validPaymentMethods, $context);
    }

    public function isKlarnaPaymentsSelected(SalesChannelContext $context): bool
    {
        return array_key_exists($context->getPaymentMethod()->getId(), PaymentMethodInstaller::KLARNA_PAYMENTS_CODES);
    }

    public function getShippingCountry(SalesChannelContext $context): CountryEntity
    {
        return $context->getShippingLocation()->getCountry();
    }

    public function getSalesChannelLocale(SalesChannelContext $context): LocaleEntity
    {
        $languageId = $context->getContext()->getLanguageId();

        if (!array_key_exists($languageId, $this->languages)) {
            $criteria = new Criteria([$languageId]);
            $criteria->addAssociation('locale');
            $this->languages[$languageId] = $this->languageRepository->search($criteria, $context->getContext())->first();
        }

        $language = $this->languages[$languageId];

        if (!($language instanceof LanguageEntity) || $language->getLocale() === null) {
            throw new \LogicException('locale is missing from language entity');
        }

        return $language->getLocale();
    }

    public function getKlarnaPaymentMethodIds(): array
    {
        return self::KLARNA_PAYMENT_METHODS;
    }

    /**
     * @param string[] $validPaymentMethods
     */
    private function testPaymentMethodExistence(array $validPaymentMethods, SalesChannelContext $context): bool
    {
        if ($this->salesChannel === null) {
            $this->salesChannel = $this->getSalesChannel($context);
        }

        /** @var PaymentMethodEntity[] $paymentMethods */
        $paymentMethods = $this->salesChannel->getPaymentMethods();

        foreach ($paymentMethods as $paymentMethod) {
            if (!in_array($paymentMethod->getId(), $validPaymentMethods, true)) {
                continue;
            }

            if ($paymentMethod->getActive()) {
                return true;
            }
        }

        return false;
    }

    private function getSalesChannel(SalesChannelContext $context): SalesChannelEntity
    {
        $salesChannelId = $context->getSalesChannel()->getId();

        $criteria = new Criteria([$salesChannelId]);
        $criteria->addAssociation('paymentMethods');
        $criteria->addAssociation('country');
        $criteria->addAssociation('language');
        $criteria->addAssociation('language.locale');

        /** @var null|SalesChannelEntity $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context->getContext())->get($salesChannelId);

        if ($salesChannel === null) {
            throw new \LogicException('could not load sales channel via id');
        }

        return $salesChannel;
    }
}
