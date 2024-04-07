<?php

declare(strict_types=1);

namespace KlarnaPayment\Installer\Modules;

use KlarnaPayment\Components\PaymentHandler\KlarnaPaymentsPaymentHandler;
use KlarnaPayment\Installer\InstallerInterface;
use KlarnaPayment\Installer\Modules\Helper\LanguageProvider;
use KlarnaPayment\KlarnaPayment;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\InvoicePayment;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class PaymentMethodInstaller implements InstallerInterface
{
    public const KLARNA_PAYMENTS_PAY_NOW_CODE = 'pay_now';
    public const KLARNA_PAYMENTS_KLARNA_CODE  = 'klarna';

    public const KLARNA_CHECKOUT             = 'e38e7c972cf4433dbbf7794fdea32cf9';
    public const KLARNA_PAY_LATER            = 'ede05b719b214143a4cb1c0216b852de';
    public const KLARNA_FINANCING            = 'ad4ca642046b40248444eba38bb8f5e8';
    public const KLARNA_DIRECT_DEBIT         = '9f4ac7bef3394487b0ab9298d12eb1bd';
    public const KLARNA_DIRECT_BANK_TRANSFER = 'a03b53a6e3d34836b150cc6eeaf6d97d';
    public const KLARNA_CREDIT_CARD          = 'd245c39e8707e85f053e806abffcbb36';
    public const KLARNA_PAY_NOW              = 'f1ef36538c594dc580b59e28206a1297';
    public const KLARNA_PAY                  = '2eb76b63b549a0de4fae2d0677c09062';

    public const KLARNA_INSTANT_SHOPPING = '0e9d7933f84244879a78acfc5b8a8d99';

    public const KLARNA_PAYMENTS_CODES = [
        self::KLARNA_PAY_LATER            => 'pay_later',
        self::KLARNA_FINANCING            => 'pay_over_time',
        self::KLARNA_DIRECT_DEBIT         => 'direct_debit',
        self::KLARNA_DIRECT_BANK_TRANSFER => 'direct_bank_transfer',
        self::KLARNA_CREDIT_CARD          => 'card',
        self::KLARNA_PAY_NOW              => self::KLARNA_PAYMENTS_PAY_NOW_CODE,
        // TODO: Enable once Klarna enables the payment method
        //self::KLARNA_PAY                  => self::KLARNA_PAYMENTS_KLARNA_CODE,
    ];

    public const KLARNA_PAYMENTS_CODES_PAY_NOW_STANDALONE = [
        self::KLARNA_CREDIT_CARD,
        self::KLARNA_DIRECT_BANK_TRANSFER,
        self::KLARNA_DIRECT_DEBIT,
    ];

    public const KLARNA_PAYMENTS_CODES_WITH_PAY_NOW_COMBINED = [
        self::KLARNA_PAY_NOW,
        self::KLARNA_PAY_LATER,
        self::KLARNA_FINANCING,
    ];

    public const KLARNA_CHECKOUT_CODES = [
        self::KLARNA_CHECKOUT => 'checkout',
    ];

    public const KLARNA_API_REGION_US = 'US';
    public const KLARNA_API_REGION_EU = 'EU';

    /**
     * Example:
     *
     * [
     *     'id'                => 'UUID',
     *     'handlerIdentifier' => Handler::class,
     * ]
     *
     * Klarna codes: 'pay_later','pay_over_time','direct_debit','direct_bank_transfer','card','pay_now', 'klarna'
     */
    private const PAYMENT_METHODS = [
        self::KLARNA_PAY_LATER => [
            'id'                => self::KLARNA_PAY_LATER,
            'handlerIdentifier' => KlarnaPaymentsPaymentHandler::class,
            'afterOrderEnabled' => true,
            'name'              => 'Klarna Pay Later',
            'translations'      => [],
        ],
        self::KLARNA_FINANCING => [
            'id'                => self::KLARNA_FINANCING,
            'handlerIdentifier' => KlarnaPaymentsPaymentHandler::class,
            'afterOrderEnabled' => true,
            'name'              => 'Klarna Financing',
            'translations'      => [],
        ],
        self::KLARNA_DIRECT_DEBIT => [
            'id'                => self::KLARNA_DIRECT_DEBIT,
            'handlerIdentifier' => KlarnaPaymentsPaymentHandler::class,
            'afterOrderEnabled' => true,
            'name'              => 'Klarna Direct Debit',
            'translations'      => [],
        ],
        self::KLARNA_DIRECT_BANK_TRANSFER => [
            'id'                => self::KLARNA_DIRECT_BANK_TRANSFER,
            'handlerIdentifier' => KlarnaPaymentsPaymentHandler::class,
            'afterOrderEnabled' => true,
            'name'              => 'Klarna Online Bank Transfer',
            'translations'      => [],
        ],
        self::KLARNA_INSTANT_SHOPPING => [
            'id'                => self::KLARNA_INSTANT_SHOPPING,
            'handlerIdentifier' => InvoicePayment::class,
            'name'              => 'Klarna Instant Shopping (No longer available)',
            'translations'      => [],
            'active'            => false,
        ],
        self::KLARNA_CREDIT_CARD => [
            'id'                => self::KLARNA_CREDIT_CARD,
            'handlerIdentifier' => KlarnaPaymentsPaymentHandler::class,
            'afterOrderEnabled' => true,
            'name'              => 'Klarna Credit Card',
            'translations'      => [],
        ],
        self::KLARNA_PAY_NOW => [
            'id'                => self::KLARNA_PAY_NOW,
            'handlerIdentifier' => KlarnaPaymentsPaymentHandler::class,
            'afterOrderEnabled' => true,
            'name'              => 'Klarna Pay Now',
            'translations'      => [],
        ],
        // TODO: Enable once Klarna enables the payment method
        /*
        self::KLARNA_PAY => [
            'id'                => self::KLARNA_PAY,
            'handlerIdentifier' => KlarnaPaymentsPaymentHandler::class,
            'afterOrderEnabled' => true,
            'name'              => 'Pay with Klarna',
            'translations'      => [],
        ],
        */
    ];

    // TODO: Adjust this if compatibility is at least > 6.4.0.0
    /** @var EntityRepository|\Shopware\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodRepositoryDecorator */
    private $paymentMethodRepository;

    /** @var EntityRepository */
    // TODO: Enable once Klarna enables the payment method
    /** @phpstan-ignore-next-line */
    private $salesChannelRepository;

    /** @var PluginIdProvider */
    private $pluginIdProvider;

    /** @var LanguageProvider */
    private $languageProvider;

    /** @var null|string[] */
    private $availableLanguageCodes = null;

    // TODO: Adjust this if compatibility is at least > 6.4.0.0

    /**
     * @param EntityRepository|\Shopware\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodRepositoryDecorator $paymentMethodRepository
     */
    public function __construct(
        $paymentMethodRepository,
        EntityRepository $salesChannelRepository,
        PluginIdProvider $pluginIdProvider,
        LanguageProvider $languageProvider
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelRepository  = $salesChannelRepository;
        $this->languageProvider        = $languageProvider;
        $this->pluginIdProvider        = $pluginIdProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            if ($paymentMethod['id'] !== self::KLARNA_INSTANT_SHOPPING) {
                $this->upsertPaymentMethod($paymentMethod, $context->getContext());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            if ($paymentMethod['id'] !== self::KLARNA_INSTANT_SHOPPING
                && !$this->paymentMethodIsInstalled($paymentMethod['id'], $context->getContext())) {
                $this->upsertPaymentMethod($paymentMethod, $context->getContext());
            }
        }

        // upsert and deactivate InstantShopping if it was installed previously
        $instantShoppingPaymentMethod = self::PAYMENT_METHODS[self::KLARNA_INSTANT_SHOPPING];

        if ($this->paymentMethodIsInstalled($instantShoppingPaymentMethod['id'], $context->getContext())) {
            $this->upsertPaymentMethod($instantShoppingPaymentMethod, $context->getContext());
            $this->setPaymentMethodStatus(self::PAYMENT_METHODS[self::KLARNA_INSTANT_SHOPPING], false, $context->getContext());
        }

        // TODO: Enable once Klarna enables the payment method
        //$this->addNewKlarnaPaymentMethodToSalesChannels($context->getContext());

        $this->removeKlarnaCheckout($context->getContext());
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $this->setPaymentMethodStatus($paymentMethod, false, $context->getContext());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
        foreach (self::PAYMENT_METHODS as $paymentMethod) {
            $this->setPaymentMethodStatus($paymentMethod, false, $context->getContext());
        }
    }

    /**
     * @param array<string,mixed> $paymentMethod
     */
    private function upsertPaymentMethod(array $paymentMethod, Context $context): void
    {
        if ($this->availableLanguageCodes === null) {
            $this->availableLanguageCodes = $this->languageProvider->getAvailableLanguageCodes($context);
        }

        $pluginId                  = $this->pluginIdProvider->getPluginIdByBaseClass(KlarnaPayment::class, $context);
        $paymentMethod['pluginId'] = $pluginId;

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($paymentMethod): void {
            $paymentMethod = $this->removeUnsupportedLanguagesFromPaymentMethod($paymentMethod);

            $this->paymentMethodRepository->upsert([$paymentMethod], $context);
        });
    }

    /**
     * @param array<string,mixed> $paymentMethod
     */
    private function setPaymentMethodStatus(array $paymentMethod, bool $active, Context $context): void
    {
        $hasPaymentMethod = $this->paymentMethodIsInstalled($paymentMethod['id'], $context);

        if (!$hasPaymentMethod) {
            return;
        }

        $data = [
            'id'     => $paymentMethod['id'],
            'active' => $active,
        ];

        $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($data): void {
            $this->paymentMethodRepository->upsert([$data], $context);
        });
    }

    private function removeKlarnaCheckout(Context $context): void
    {
        $context->scope(Context::SYSTEM_SCOPE, function (Context $context): void {
            $this->paymentMethodRepository->delete([['id' => self::KLARNA_CHECKOUT]], $context);
        });
    }

    /**
     * @param array<string,mixed> $paymentMethod
     *
     * @return array<string,mixed>
     */
    private function removeUnsupportedLanguagesFromPaymentMethod(array $paymentMethod): array
    {
        $availablePaymentMethodTranslations = array_filter($paymentMethod['translations'], function ($localeCode) {
            if ($this->availableLanguageCodes === null || !in_array($localeCode, $this->availableLanguageCodes, true)) {
                return false;
            }

            return true;
        }, ARRAY_FILTER_USE_KEY);

        $paymentMethod['translations'] = $availablePaymentMethodTranslations;

        return $paymentMethod;
    }

    private function paymentMethodIsInstalled(string $id, Context $context): bool
    {
        $paymentMethodCriteria = new Criteria([$id]);

        return $this->paymentMethodRepository->searchIds($paymentMethodCriteria, $context)->getTotal() > 0;
    }

    // TODO: Enable once Klarna enables the payment method
//    private function addNewKlarnaPaymentMethodToSalesChannels(Context $context): void
//    {
//        $this->setPaymentMethodStatus(self::PAYMENT_METHODS[self::KLARNA_PAY], true, $context);
//
//        /** @var SalesChannelEntity[] $salesChannels */
//        $salesChannels = $this->fetchSalesChannels($context);
//
//        $toProcess = [];
//        foreach ($salesChannels as $salesChannel) {
//            $paymentMethods = $salesChannel->getPaymentMethods();
//
//            if ($paymentMethods === null) {
//                continue;
//            }
//
//            if ($paymentMethods->has(self::KLARNA_PAY)) {
//                continue;
//            }
//
//            foreach ($paymentMethods as $paymentMethod) {
//                if (!array_key_exists($paymentMethod->getId(), self::KLARNA_PAYMENTS_CODES)) {
//                    continue;
//                }
//
//                if ($paymentMethod->getActive()) {
//                    $toProcess[$salesChannel->getId()] = $salesChannel->getId();
//                }
//            }
//        }
//
//        $upsertData = [];
//        foreach ($toProcess as $salesChannelId) {
//            $upsertData[] = [
//                'id'             => $salesChannelId,
//                'paymentMethods' => [
//                    [
//                        'id' => self::KLARNA_PAY,
//                    ],
//                ],
//            ];
//        }
//
//        $this->salesChannelRepository->upsert($upsertData, $context);
//    }
//
//    private function fetchSalesChannels(Context $context): array
//    {
//        $criteria = new Criteria();
//        $criteria->addAssociation('paymentMethods');
//
//        return $this->salesChannelRepository->search($criteria, $context)->getElements();
//    }
}
