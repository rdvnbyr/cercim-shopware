<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Storefront;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Components\PaymentHandler\AbstractKlarnaPaymentHandler;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractRegisterRoute;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @RouteScope(scopes={"storefront"})
 * @Route(defaults={"_routeScope": {"storefront"}})
 */
class KlarnaExpressCallbackController extends StorefrontController
{
    /** @var AbstractRegisterRoute */
    private $registerRoute;

    /** @var EntityRepository */
    private $countryRepository;

    /** @var EntityRepository */
    private $stateRepository;

    /** @var RouterInterface */
    private $router;

    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        AbstractRegisterRoute $registerRoute,
        EntityRepository $countryRepository,
        EntityRepository $stateRepository,
        RouterInterface $router,
        ConfigReaderInterface $configReader,
        LoggerInterface $logger
    ) {
        $this->registerRoute     = $registerRoute;
        $this->countryRepository = $countryRepository;
        $this->stateRepository   = $stateRepository;
        $this->router            = $router;
        $this->configReader      = $configReader;
        $this->logger            = $logger;
    }

    /**
     * @Route("/klarna/callback/express/userAuthenticated", defaults={"csrf_protected": false, "XmlHttpRequest": true}, name="frontend.klarna.express.callback.userAuthenticated", methods={"POST"})
     */
    public function userAuthenticatedCallback(RequestDataBag $dataBag, SalesChannelContext $context): Response
    {
        $configuration = $this->configReader->read($context->getSalesChannel()->getId());

        try {
            $registerData = new RequestDataBag([
                'firstName'             => $dataBag->get('first_name'),
                'lastName'              => $dataBag->get('last_name'),
                'email'                 => $dataBag->get('email'),
                'salutationId'          => $configuration->get('klarnaExpressDefaultSalutation', null),
                'birthday'              => $this->parseBirthday($dataBag->get('date_of_birth')),
                'guest'                 => true,
                'createCustomerAccount' => false,
            ]);

            $address = $dataBag->get('address');

            if (!$address instanceof RequestDataBag) {
                $this->logger->notice('Klarna Express Login MissingAddress', [
                    'payload' => $dataBag->all(),
                ]);

                $this->addFlash('danger', $this->trans('KlarnaPayment.errorMessages.expressLoginError'));

                return new JsonResponse(['url' => $this->getRegisterUrl()], 400);
            }

            $countryCode = (string) $address->get('country');
            $regionCode  = (string) $address->get('region');

            $registerData->set('billingAddress', new RequestDataBag([
                'additionalAddressLine1' => $address->get('street_address2'),
                'street'                 => $address->get('street_address'),
                'zipcode'                => $address->get('postal_code'),
                'city'                   => $address->get('city'),
                'phoneNumber'            => $registerData->get('phone'),
                'countryId'              => $this->fetchCountryId($countryCode, $context),
                'countryStateId'         => $this->fetchCountryStateId($countryCode, $regionCode, $context),
            ]));

            try {
                // TODO: remove check and just use addState if min version higher than 6.3
                if (method_exists($context, 'addState')) {
                    /** @phpstan-ignore-next-line */
                    $context->addState(AbstractKlarnaPaymentHandler::IS_KLARNA_EXPRESS_CHECKOUT);
                } else {
                    $context->addExtension(AbstractKlarnaPaymentHandler::IS_KLARNA_EXPRESS_CHECKOUT, new ArrayStruct());
                }

                $this->registerRoute->register(
                    $registerData->toRequestDataBag(),
                    $context,
                    false
                );
            } catch (ConstraintViolationException $exception) {
                $this->logger->notice('Klarna Express Login ConstraintViolationError', [
                    'message'    => $exception->getMessage(),
                    'violations' => (string) $exception->getViolations(),
                    'payload'    => $dataBag->all(),
                ]);

                $this->addFlash('danger', $this->trans('KlarnaPayment.errorMessages.expressLoginError'));

                return new JsonResponse(['url' => $this->getRegisterUrl()], 400);
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Klarna Express Login Error', [
                'message' => $exception->getMessage(),
                'payload' => $dataBag->all(),
            ]);

            $this->addFlash('danger', $this->trans('KlarnaPayment.errorMessages.expressLoginError'));

            return new JsonResponse(['url' => $this->getRegisterUrl()], 400);
        }

        return new JsonResponse(['url' => $this->getCheckoutUrl()]);
    }

    private function getCheckoutUrl(): string
    {
        return $this->router->generate(
            'frontend.checkout.confirm.page',
            [AbstractKlarnaPaymentHandler::IS_KLARNA_EXPRESS_CHECKOUT => true],
            RouterInterface::ABSOLUTE_URL
        );
    }

    private function getRegisterUrl(): string
    {
        return $this->router->generate(
            'frontend.checkout.register.page',
            [],
            RouterInterface::ABSOLUTE_URL
        );
    }

    private function fetchCountryId(string $region, SalesChannelContext $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $region));

        /** @var null|CountryEntity $country */
        $country = $this->countryRepository->search($criteria, $context->getContext())->first();

        if ($country === null) {
            return $context->getShippingLocation()->getCountry()->getId();
        }

        return $country->getId();
    }

    private function fetchCountryStateId(string $countryCode, string $regionCode, SalesChannelContext $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortCode', sprintf('%s-%s', $countryCode, $regionCode)));

        /** @var null|CountryStateEntity $state */
        $state = $this->stateRepository->search($criteria, $context->getContext())->first();

        if ($state === null) {
            return null;
        }

        return $state->getId();
    }

    private function parseBirthday(?string $birthday): ?\DateTime
    {
        if (empty($birthday)) {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $birthday);

        if (!$date instanceof \DateTime) {
            return null;
        }

        return $date;
    }
}
