<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigDefinition;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigWrittenSubscriber implements EventSubscriberInterface
{
    private const SETTING_ALLOWED_KLARNA_PAYMENTS_CODES = 'KlarnaPayment.settings.allowedKlarnaPaymentsCodes';

    // TODO: Adjust this if compatibility is at least > 6.4.0.0
    /** @var EntityRepository|\Shopware\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodRepositoryDecorator */
    private $paymentMethodRepository;

    /** @var EntityRepository */
    private $salesChannelRepository;

    /** @var SystemConfigService */
    private $systemConfigService;

    // TODO: Adjust this if compatibility is at least > 6.4.0.0

    /**
     * @param EntityRepository|\Shopware\Core\Checkout\Payment\DataAbstractionLayer\PaymentMethodRepositoryDecorator $paymentMethodRepository
     */
    public function __construct(
        $paymentMethodRepository,
        EntityRepository $salesChannelRepository,
        SystemConfigService $systemConfigService
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelRepository  = $salesChannelRepository;
        $this->systemConfigService     = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EntityWrittenContainerEvent::class => 'onEntityWrittenContainerEvent',
        ];
    }

    public function onEntityWrittenContainerEvent(EntityWrittenContainerEvent $containerEvent): void
    {
        $event = $containerEvent->getEventByEntityName(SystemConfigDefinition::ENTITY_NAME);

        if ($event === null || $event->hasErrors() === true
            || $event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $context = $event->getContext();

        $writeResults = $event->getWriteResults();
        /** @var EntityWriteResult $writeResult */
        $writeResult = end($writeResults);
        $payload     = $writeResult->getPayload();

        if (!array_key_exists('configurationKey', $payload)
            || !array_key_exists('configurationValue', $payload)) {
            return;
        }

        $payload = $writeResult->getPayload();

        if (!isset($payload['configurationKey'], $payload['configurationValue'], $payload['salesChannelId'])) {
            return;
        }

        $configurationKey            = $payload['configurationKey'];
        $configurationValue          = $payload['configurationValue'];
        $configurationSalesChannelId = $payload['salesChannelId'] ?? null;

        if ($configurationKey !== self::SETTING_ALLOWED_KLARNA_PAYMENTS_CODES) {
            return;
        }

        $activeMethodCodes = $configurationValue;

        if ($configurationSalesChannelId !== null) {
            array_push($activeMethodCodes, ...$this->getActiveMethodCodes());
        }

        $salesChannelIds = $this->salesChannelRepository->searchIds(new Criteria(), $context);

        foreach ($salesChannelIds->getIds() as $checkSalesChannelId) {
            if (is_string($checkSalesChannelId) && $checkSalesChannelId !== $configurationSalesChannelId) {
                array_push($activeMethodCodes, ...$this->getActiveMethodCodes($checkSalesChannelId));
            }
        }

        $activeMethodCodes   = array_filter(array_unique($activeMethodCodes));
        $inactiveMethodCodes = array_filter(array_values(array_diff(PaymentMethodInstaller::KLARNA_PAYMENTS_CODES, $activeMethodCodes)));
        $upsertStatement     = [];

        foreach (PaymentMethodInstaller::KLARNA_PAYMENTS_CODES as $paymentMethodId => $code) {
            $upsertStatement[] = [
                'id'     => $paymentMethodId,
                'active' => !in_array($code, $inactiveMethodCodes, true),
            ];
        }

        $this->paymentMethodRepository->update($upsertStatement, $context);
    }

    /**
     * @return string[]
     */
    private function getActiveMethodCodes(?string $salesChannelId = null): array
    {
        $activeMethodCodes = [];
        $values            = $this->systemConfigService->get(self::SETTING_ALLOWED_KLARNA_PAYMENTS_CODES, $salesChannelId);

        if (!is_array($values)) {
            return [];
        }

        foreach ($values as $code) {
            if ($code === PaymentMethodInstaller::KLARNA_PAYMENTS_PAY_NOW_CODE) {
                $activeMethodCodes[] = PaymentMethodInstaller::KLARNA_PAYMENTS_PAY_NOW_CODE;

                foreach (PaymentMethodInstaller::KLARNA_PAYMENTS_CODES_PAY_NOW_STANDALONE as $standalonePaymentId) {
                    $activeMethodCodes[] = PaymentMethodInstaller::KLARNA_PAYMENTS_CODES[$standalonePaymentId];
                }

                continue;
            }

            $activeMethodCodes[] = $code;
        }

        return $activeMethodCodes;
    }
}
