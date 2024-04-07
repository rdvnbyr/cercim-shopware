<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Components\Struct\Configuration;
use KlarnaPayment\Components\Struct\PaymentMethodBadge;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FooterBadgeEventListener implements EventSubscriberInterface
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var PaymentHelperInterface */
    private $paymentHelper;

    public function __construct(ConfigReaderInterface $configReader, PaymentHelperInterface $paymentHelper)
    {
        $this->configReader  = $configReader;
        $this->paymentHelper = $paymentHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FooterPageletLoadedEvent::class => 'addFooterBadge',
        ];
    }

    public function addFooterBadge(FooterPageletLoadedEvent $event): void
    {
        $context = $event->getSalesChannelContext();
        $config  = $this->configReader->read($context->getSalesChannel()->getId());

        if ($this->displayKlarnaCheckoutBadge($config, $context)) {
            $badge = new PaymentMethodBadge();
            $badge->assign([
                'paymentType' => PaymentMethodBadge::TYPE_CHECKOUT,
                'countryCode' => $this->getFooterBadgeCountry($context),
                'style'       => (string) $config->get('kcoFooterBadgeStyle'),
                'width'       => (int) $config->get('kcoFooterBadgeWidth'),
            ]);

            $event->getPagelet()->addExtension(PaymentMethodBadge::EXTENSION_NAME, $badge);
        } elseif ($this->displayKlarnaPaymentsBadge($config, $context)) {
            $badge = new PaymentMethodBadge();
            $badge->assign([
                'paymentType' => PaymentMethodBadge::TYPE_PAYMENTS,
            ]);

            $event->getPagelet()->addExtension(PaymentMethodBadge::EXTENSION_NAME, $badge);
        }
    }

    private function displayKlarnaCheckoutBadge(Configuration $configuration, SalesChannelContext $context): bool
    {
        if (!$configuration->get('kcoDisplayFooterBadge')) {
            return false;
        }

        if (!$this->paymentHelper->isKlarnaCheckoutEnabled($context)) {
            return false;
        }

        return true;
    }

    private function displayKlarnaPaymentsBadge(Configuration $configuration, SalesChannelContext $context): bool
    {
        if (!$configuration->get('kpDisplayFooterBadge')) {
            return false;
        }

        if (!$this->paymentHelper->isKlarnaPaymentsEnabled($context)) {
            return false;
        }

        return true;
    }

    private function getFooterBadgeCountry(SalesChannelContext $context): string
    {
        $validCountries = [
            'de-AT',
            'fr-BE',
            'nl-BE',
            'da-DK',
            'fi-FI',
            'fr-FR',
            'de-DE',
            'it-IT',
            'nl-NL',
            'nb-NO',
            'pl-PL',
            'es-ES',
            'sv-SE',
            'fr-CH',
            'de-CH',
            'it-CH',
            'en-GB',
            'en-US',
        ];

        $countryCode = $this->paymentHelper->getSalesChannelLocale($context)->getCode();

        if (in_array($countryCode, $validCountries, true)) {
            return strtolower(str_replace('-', '_', $countryCode));
        }

        return 'xx_XX';
    }
}
