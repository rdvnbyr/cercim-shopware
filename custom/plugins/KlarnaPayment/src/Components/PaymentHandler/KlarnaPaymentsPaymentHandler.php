<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\PaymentHandler;

use KlarnaPayment\Components\Client\ClientInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\CreateOrder\CreateOrderRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateAddress\UpdateAddressRequestHydratorInterface;
use KlarnaPayment\Components\Client\Hydrator\Request\UpdateOrder\UpdateOrderRequestHydratorInterface;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtension;
use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Components\Helper\RequestHasherInterface;
use KlarnaPayment\Components\Helper\StateHelper\Authorize\AuthorizeStateHelperInterface;
use KlarnaPayment\Components\Helper\StateHelper\Capture\CaptureStateHelperInterface;
use KlarnaPayment\Components\Helper\SynchronizationHelper\SynchronizationHelperInterface;
use KlarnaPayment\Components\Validator\OrderTransitionChangeValidator;
use KlarnaPayment\Core\Framework\ContextScope;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class KlarnaPaymentsPaymentHandler extends AbstractKlarnaPaymentHandler implements AsynchronousPaymentHandlerInterface
{
    /** @var CreateOrderRequestHydratorInterface */
    private $requestHydrator;

    /** @var ClientInterface */
    private $client;

    /** @var EntityRepository */
    private $orderRepository;

    /** @var TranslatorInterface */
    private $translator;

    /** @var RequestHasherInterface */
    private $updateOrderRequestHasher;

    /** @var RequestHasherInterface */
    private $updateAddressRequestHasher;

    /** @var UpdateAddressRequestHydratorInterface */
    private $addressRequestHydrator;

    /** @var UpdateOrderRequestHydratorInterface */
    private $orderRequestHydrator;

    /** @var OrderFetcherInterface */
    private $orderFetcher;

    /** @var OrderTransitionChangeValidator */
    private $orderStatusValidator;

    /** @var CaptureStateHelperInterface */
    private $captureStateHelper;

    /** @var AuthorizeStateHelperInterface */
    private $authorizeStateHelper;

    /** @var SynchronizationHelperInterface */
    private $synchronizationHelper;

    public function __construct(
        CreateOrderRequestHydratorInterface $requestHydrator,
        ClientInterface $client,
        EntityRepository $transactionRepository,
        EntityRepository $orderRepository,
        TranslatorInterface $translator,
        RequestHasherInterface $updateOrderRequestHasher,
        RequestHasherInterface $updateAddressRequestHasher,
        UpdateAddressRequestHydratorInterface $addressRequestHydrator,
        UpdateOrderRequestHydratorInterface $orderRequestHydrator,
        OrderFetcherInterface $orderFetcher,
        RequestStack $requestStack,
        OrderTransitionChangeValidator $orderStatusValidator,
        CaptureStateHelperInterface $captureStateHelper,
        AuthorizeStateHelperInterface $authorizeStateHelper,
        SynchronizationHelperInterface $synchronizationHelper
    ) {
        $this->requestHydrator            = $requestHydrator;
        $this->client                     = $client;
        $this->transactionRepository      = $transactionRepository;
        $this->orderRepository            = $orderRepository;
        $this->translator                 = $translator;
        $this->updateOrderRequestHasher   = $updateOrderRequestHasher;
        $this->updateAddressRequestHasher = $updateAddressRequestHasher;
        $this->addressRequestHydrator     = $addressRequestHydrator;
        $this->orderRequestHydrator       = $orderRequestHydrator;
        $this->orderFetcher               = $orderFetcher;
        $this->requestStack               = $requestStack;
        $this->orderStatusValidator       = $orderStatusValidator;
        $this->captureStateHelper         = $captureStateHelper;
        $this->authorizeStateHelper       = $authorizeStateHelper;
        $this->synchronizationHelper      = $synchronizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $requestData = $this->fetchRequestData();

        $request  = $this->requestHydrator->hydrate($transaction, $requestData, $salesChannelContext);
        $response = $this->client->request($request, $salesChannelContext->getContext());

        if ($response->getHttpStatus() !== 200
            || strtolower($response->getResponse()['fraud_status']) === strtolower(AbstractKlarnaPaymentHandler::FRAUD_STATUS_REJECTED)
        ) {
            $errorMessage = $this->translator->trans('KlarnaPayment.errorMessages.paymentDeclined');

            throw new AsyncPaymentProcessException($transaction->getOrderTransaction()->getId(), $errorMessage);
        }

        $this->saveTransactionData($transaction->getOrderTransaction(), $response, $salesChannelContext->getContext(), $dataBag->get('klarnaAuthorizationToken'));

        $this->synchronizationHelper->syncBillingAddress($transaction, $response->getResponse()['order_id'], $salesChannelContext);

        return new RedirectResponse($response->getResponse()['redirect_url']);
    }

    /**
     * {@inheritdoc}
     */
    public function finalize(AsyncPaymentTransactionStruct $transaction, Request $request, SalesChannelContext $salesChannelContext): void
    {
        $orderEntity = $this->orderFetcher->getOrderFromOrder($transaction->getOrder()->getId(), $salesChannelContext->getContext());

        if (!$orderEntity || $orderEntity->getStateMachineState() === null) {
            $errorMessage = $this->translator->trans('KlarnaPayment.errorMessages.genericError');

            throw new AsyncPaymentFinalizeException($transaction->getOrderTransaction()->getId(), $errorMessage);
        }

        $addressRequest = $this->addressRequestHydrator->hydrate($orderEntity, $salesChannelContext->getContext());
        $orderRequest   = $this->orderRequestHydrator->hydrate($orderEntity, $salesChannelContext->getContext());
        $customFields   = $transaction->getOrderTransaction()->getCustomFields() ?? [];

        $update = [
            'id'                           => $orderEntity->getId(),
            OrderExtension::EXTENSION_NAME => [
                'orderAddressHash'     => $this->updateAddressRequestHasher->getHash($addressRequest),
                'orderCartHash'        => $this->updateOrderRequestHasher->getHash($orderRequest, self::CART_HASH_CURRENT_VERSION),
                'orderCartHashVersion' => self::CART_HASH_CURRENT_VERSION,
                'authorizationToken'   => $customFields['klarna_authorization_token'],
            ],
        ];

        $salesChannelContext->getContext()->scope(ContextScope::INTERNAL_SCOPE, function (Context $context) use ($update): void {
            $this->orderRepository->update([$update], $context);
        });

        if ($transaction->getOrderTransaction()->getCustomFields() === null || !array_key_exists('klarna_fraud_status', $transaction->getOrderTransaction()->getCustomFields())) {
            return;
        }

        if (strtolower($transaction->getOrderTransaction()->getCustomFields()['klarna_fraud_status']) === strtolower(AbstractKlarnaPaymentHandler::FRAUD_STATUS_ACCEPTED)) {
            if ($this->orderStatusValidator->isAutomaticCapture(
                null,
                $orderEntity->getStateMachineState()->getTechnicalName(),
                $salesChannelContext->getSalesChannel()->getId(),
                $salesChannelContext->getContext()
            )) {
                $this->captureStateHelper->processOrderCapture($orderEntity, $salesChannelContext->getContext());

                return;
            }

            $this->authorizeStateHelper->processOrderAuthorize($orderEntity, $salesChannelContext->getContext());
        }
    }
}
