<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Callback;

use http\Exception\RuntimeException;
use KlarnaPayment\Components\DataAbstractionLayer\Entity\Cart\KlarnaCartEntity;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthorizationCallback
{
    /** @var OrderService */
    private $orderService;

    /** @var PaymentService */
    private $paymentService;

    /** @var RequestStack */
    private $requestStack;

    /** @var EntityRepository */
    private $cartDataRepository;

    public function __construct(OrderService $orderService, PaymentService $paymentService, RequestStack $requestStack, EntityRepository $cartDataRepository)
    {
        $this->orderService       = $orderService;
        $this->paymentService     = $paymentService;
        $this->requestStack       = $requestStack;
        $this->cartDataRepository = $cartDataRepository;
    }

    public function handle(string $authorizationToken, SalesChannelContext $context): void
    {
        $dataBag = new RequestDataBag([
            'tos'                      => true,
            'klarnaAuthorizationToken' => $authorizationToken,
        ]);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('cartToken', $context->getToken()));

        /** @var KlarnaCartEntity $cartData */
        $cartData = $this->cartDataRepository->search($criteria, $context->getContext())->first();

        if ($cartData instanceof KlarnaCartEntity && !empty($cartData->getPayload())) {
            $dataBag->add($cartData->getPayload());
        }

        $currentRequest = $this->requestStack->getCurrentRequest();

        if ($currentRequest === null) {
            throw new RuntimeException('Current request is null in requestStack');
        }

        $currentRequest->request->add($dataBag->all());

        $orderId  = $this->orderService->createOrder($dataBag, $context);
        $response = $this->paymentService->handlePaymentByOrder($orderId, $dataBag, $context);

        if ($response === null) {
            throw new RuntimeException('Empty response for handling payment');
        }

        // Follow the redirects to complete the payment
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($curl, CURLOPT_URL, $response->getTargetUrl());
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYSTATUS, false);

        curl_exec($curl);
        curl_close($curl);

        if ($cartData instanceof KlarnaCartEntity) {
            $this->cartDataRepository->delete([['id' => $cartData->getUniqueIdentifier()]], $context->getContext());
        }
    }
}
