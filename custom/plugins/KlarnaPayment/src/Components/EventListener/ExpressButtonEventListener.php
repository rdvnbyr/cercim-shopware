<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\EventListener;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\Extension\TemplateData\ExpressDataExtension;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use KlarnaPayment\Components\PaymentHandler\AbstractKlarnaPaymentHandler;
use KlarnaPayment\Components\Struct\Configuration;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;
use Shopware\Core\Checkout\Customer\Event\GuestCustomerRegisterEvent;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpressButtonEventListener implements EventSubscriberInterface
{
    private const ENVIRONMENT_PLAYGROUND = 'playground';
    private const ENVIRONMENT_PRODUCTION = 'production';

    /** @var PaymentHelperInterface */
    private $paymentHelper;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var AbstractContextSwitchRoute */
    private $contextSwitchRoute;

    /** @var SalesChannelRepository */
    private $paymentMethodsRepository;

    public function __construct(
        PaymentHelperInterface $paymentHelper,
        ConfigReaderInterface $configReader,
        AbstractContextSwitchRoute $contextSwitchRoute,
        SalesChannelRepository $paymentMethodsRepository
    ) {
        $this->paymentHelper            = $paymentHelper;
        $this->configReader             = $configReader;
        $this->contextSwitchRoute       = $contextSwitchRoute;
        $this->paymentMethodsRepository = $paymentMethodsRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutRegisterPageLoadedEvent::class => 'addExpressTemplateData',
            CheckoutCartPageLoadedEvent::class     => 'addExpressTemplateData',
            OffcanvasCartPageLoadedEvent::class    => 'addExpressTemplateData',
            GuestCustomerRegisterEvent::class      => 'changeDefaultPaymentMethod',
        ];
    }

    /**
     * @param CheckoutCartPageLoadedEvent|CheckoutRegisterPageLoadedEvent|OffcanvasCartPageLoadedEvent $event
     */
    public function addExpressTemplateData(PageLoadedEvent $event): void
    {
        $salesChannelContext = $event->getSalesChannelContext();

        if (!$this->paymentHelper->isKlarnaPaymentsEnabled($salesChannelContext)) {
            return;
        }

        $configuration = $this->configReader->read($salesChannelContext->getSalesChannel()->getId());

        if (!$configuration->get('isKlarnaExpressActive', false)) {
            return;
        }

        $locale  = $this->paymentHelper->getSalesChannelLocale($salesChannelContext);
        $country = $this->paymentHelper->getShippingCountry($salesChannelContext);

        $testMode    = !empty($configuration->get('testMode'));
        $environment = $testMode ? self::ENVIRONMENT_PLAYGROUND : self::ENVIRONMENT_PRODUCTION;
        $user        = $this->getApiUsername($salesChannelContext, $configuration, $testMode);

        $underscorePosition = strpos($user, '_');

        if ($underscorePosition === false) {
            return;
        }

        $templateData = new ExpressDataExtension(
            substr($user, 0, $underscorePosition),
            $environment,
            substr_replace($locale->getCode(), (string) $country->getIso(), 3, 2),
            $configuration->get('klarnaExpressTheme', 'default'),
            $configuration->get('klarnaExpressLabel', 'default'),
            $configuration->get('klarnaExpressCssClass', ''),
            $configuration->get('klarnaExpressShape', 'default')
        );

        $event->getPage()->addExtension(ExpressDataExtension::EXTENSION_NAME, $templateData);
    }

    public function changeDefaultPaymentMethod(GuestCustomerRegisterEvent $event): void
    {
        $context = $event->getSalesChannelContext();

        // TODO: remove check and just use hasState if min version higher than 6.3
        if (method_exists($context->getContext(), 'hasState')) {
            /** @phpstan-ignore-next-line */
            if (!$context->getContext()->hasState(AbstractKlarnaPaymentHandler::IS_KLARNA_EXPRESS_CHECKOUT)) {
                return;
            }
        } elseif (!$context->hasExtension(AbstractKlarnaPaymentHandler::IS_KLARNA_EXPRESS_CHECKOUT)) {
            return;
        }

        $paymentMethod = $this->getFirstValidKlarnaMethodId($context);

        if ($paymentMethod === null) {
            return;
        }

        $customer = $context->getCustomer();

        if ($customer === null) {
            return;
        }

        $this->contextSwitchRoute->switchContext(
            new RequestDataBag([
                SalesChannelContextService::PAYMENT_METHOD_ID => $paymentMethod,
            ]),
            $context
        );
    }

    private function getFirstValidKlarnaMethodId(SalesChannelContext $context): ?string
    {
        $validPaymentMethods = array_keys(PaymentMethodInstaller::KLARNA_PAYMENTS_CODES);

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('active', true))
            ->addSorting(new FieldSorting('position'));

        /** @var PaymentMethodCollection $availablePaymentMethods */
        $availablePaymentMethods = $this->paymentMethodsRepository
            ->search($criteria, $context)
            ->getEntities();

        $availablePaymentMethods = $availablePaymentMethods->filterByActiveRules($context);

        foreach ($validPaymentMethods as $validPaymentMethod) {
            if ($availablePaymentMethods->has($validPaymentMethod)) {
                return $validPaymentMethod;
            }
        }

        return null;
    }

    /**
     * We are determining the user by the configuration priorizing the config made onto the saleschannel without inheritation
     */
    private function getApiUsername(SalesChannelContext $salesChannelContext, Configuration $configuration, bool $testMode): string
    {
        $salesChannelConfiguration = $this->configReader->read(
            $salesChannelContext->getSalesChannel()->getId(),
            false
        );

        $configKey = $testMode ? 'testApiUsername' : 'apiUsername';

        foreach ([$salesChannelConfiguration, $configuration] as $config) {
            if ($user = $config->get($configKey)) {
                return $user;
            }

            if ($user = $config->get($configKey . 'US')) {
                return $user;
            }
        }

        return '';
    }
}
