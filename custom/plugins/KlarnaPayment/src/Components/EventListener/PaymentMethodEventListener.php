<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityIdSearchResultLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntitySearchResultLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntityIdSearchResultLoadedEvent;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelEntitySearchResultLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentMethodEventListener implements EventSubscriberInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    public function __construct(ConfigReaderInterface $configReader)
    {
        $this->configReader = $configReader;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.payment_method.search.id.result.loaded' => ['onSalesChannelIdSearchResultLoaded', -1],
            'payment_method.search.id.result.loaded'               => ['onIdSearchResultLoaded', -1],
            'sales_channel.payment_method.search.result.loaded'    => ['onSalesChannelSearchResultLoaded', -1],
            'payment_method.search.result.loaded'                  => ['onSearchResultLoaded', -1],
        ];
    }

    public function onSalesChannelIdSearchResultLoaded(SalesChannelEntityIdSearchResultLoadedEvent $event): void
    {
        $source = $event->getContext()->getSource();

        if (!($source instanceof SalesChannelApiSource)) {
            return;
        }

        $this->removeDeactivatedPaymentMethodsIds($event->getResult(), $source->getSalesChannelId());
    }

    public function onIdSearchResultLoaded(EntityIdSearchResultLoadedEvent $event): void
    {
        $source = $event->getContext()->getSource();

        if (!($source instanceof SalesChannelApiSource)) {
            return;
        }

        $this->removeDeactivatedPaymentMethodsIds($event->getResult(), $source->getSalesChannelId());
    }

    public function onSalesChannelSearchResultLoaded(SalesChannelEntitySearchResultLoadedEvent $event): void
    {
        $source = $event->getContext()->getSource();

        if (!($source instanceof SalesChannelApiSource)) {
            return;
        }

        $this->removeDeactivatedPaymentMethods($event->getResult(), $source->getSalesChannelId());
    }

    public function onSearchResultLoaded(EntitySearchResultLoadedEvent $event): void
    {
        $source = $event->getContext()->getSource();

        if (!($source instanceof SalesChannelApiSource)) {
            return;
        }

        $this->removeDeactivatedPaymentMethods($event->getResult(), $source->getSalesChannelId());
    }

    private function removeDeactivatedPaymentMethods(EntitySearchResult $result, string $salesChannelId = null): void
    {
        $validPaymentMethods     = $this->getValidPaymentMethods($salesChannelId);
        $allKlarnaPaymentMethods = $this->getAllKlarnaPaymentMethods();

        $filter = static function (PaymentMethodEntity $entity) use ($validPaymentMethods, $allKlarnaPaymentMethods) {
            if (!in_array($entity->getId(), $allKlarnaPaymentMethods, true)) {
                return true;
            }

            return in_array($entity->getId(), $validPaymentMethods, true);
        };

        $filteredPaymentMethods = $result->getEntities()->filter($filter);

        $result->assign([
            'total'    => count($filteredPaymentMethods),
            'entities' => $filteredPaymentMethods,
            'elements' => $filteredPaymentMethods->getElements(),
        ]);
    }

    private function removeDeactivatedPaymentMethodsIds(IdSearchResult $result, string $salesChannelId = null): void
    {
        $validPaymentMethods     = $this->getValidPaymentMethods($salesChannelId);
        $allKlarnaPaymentMethods = $this->getAllKlarnaPaymentMethods();

        $filter = static function (string $paymentMethod) use ($validPaymentMethods, $allKlarnaPaymentMethods) {
            if (!in_array($paymentMethod, $allKlarnaPaymentMethods, true)) {
                return true;
            }

            return in_array($paymentMethod, $validPaymentMethods, true);
        };

        /** @var array<string> $ids */
        $ids = $result->getIds();

        $filteredPaymentMethods = array_filter($ids, $filter);

        $result->assign([
            'total'    => count($filteredPaymentMethods),
            'ids'      => $filteredPaymentMethods,
            'entities' => $filteredPaymentMethods,
            'elements' => $filteredPaymentMethods,
        ]);
    }

    /**
     * @return string[]
     */
    private function getValidPaymentMethods(string $salesChannelId = null): array
    {
        $config = $this->configReader->read($salesChannelId);

        if ($config->get('klarnaType') === 'checkout') {
            return array_keys(PaymentMethodInstaller::KLARNA_CHECKOUT_CODES);
        }

        if ($config->get('klarnaType') === 'payments') {
            $validPaymentMethods = array_keys(PaymentMethodInstaller::KLARNA_PAYMENTS_CODES);

            $merchantValidKlarnaPaymentsMethods   = $config->get('allowedKlarnaPaymentsCodes', []);
            $merchantValidKlarnaPaymentsMethods[] = PaymentMethodInstaller::KLARNA_PAYMENTS_KLARNA_CODE;

            if (in_array(PaymentMethodInstaller::KLARNA_PAYMENTS_PAY_NOW_CODE, $merchantValidKlarnaPaymentsMethods, true)) {
                $additionalValidCodes = array_map(
                    static function (string $paymentMethodId) {
                        return PaymentMethodInstaller::KLARNA_PAYMENTS_CODES[$paymentMethodId];
                    },
                    PaymentMethodInstaller::KLARNA_PAYMENTS_CODES_PAY_NOW_STANDALONE
                );
                $merchantValidKlarnaPaymentsMethods = array_unique(array_merge($merchantValidKlarnaPaymentsMethods, $additionalValidCodes));
            }

            return array_filter(
                $validPaymentMethods,
                static function (string $paymentMethodId) use ($merchantValidKlarnaPaymentsMethods) {
                    return in_array(PaymentMethodInstaller::KLARNA_PAYMENTS_CODES[$paymentMethodId], $merchantValidKlarnaPaymentsMethods, true);
                }
            );
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function getAllKlarnaPaymentMethods(): array
    {
        return array_merge(
            array_keys(PaymentMethodInstaller::KLARNA_CHECKOUT_CODES),
            array_keys(PaymentMethodInstaller::KLARNA_PAYMENTS_CODES)
        );
    }
}
