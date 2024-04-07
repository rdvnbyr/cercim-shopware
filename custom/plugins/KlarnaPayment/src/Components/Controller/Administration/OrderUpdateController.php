<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Administration;

use KlarnaPayment\Components\Helper\OrderFetcherInterface;
use KlarnaPayment\Components\Helper\OrderValidator\OrderValidatorInterface;
use KlarnaPayment\Exception\OrderUpdateDeniedException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 * @Route(defaults={"_routeScope": {"api"}})
 */
class OrderUpdateController extends AbstractController
{
    /** @var OrderFetcherInterface */
    private $orderFetcher;

    /** @var OrderValidatorInterface */
    private $orderValidator;

    public function __construct(
        OrderFetcherInterface $orderFetcher,
        OrderValidatorInterface $orderValidator
    ) {
        $this->orderFetcher   = $orderFetcher;
        $this->orderValidator = $orderValidator;
    }

    /**
     * @Route("/api/_action/klarna_payment/update_order", name="api.action.klarna_payment.order_update.update", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/update_order", name="api.action.klarna_payment.order_update.update.legacy", methods={"POST"})
     *
     * @throws OrderUpdateDeniedException
     *
     * @see \KlarnaPayment\Components\EventListener\OrderChangeEventListener::validateKlarnaOrder Change accordingly to keep functionality synchronized
     */
    public function update(RequestDataBag $dataBag, Context $context): JsonResponse
    {
        $orderId = $dataBag->get('orderId', '');

        try {
            $orderEntity = $this->orderFetcher->getOrderFromOrder(Uuid::fromHexToBytes($orderId), $context);
        } catch (InvalidUuidException $e) {
            return new JsonResponse(['status' => 'success'], 200);
        }

        if (!$orderEntity) {
            return new JsonResponse(['status' => 'success'], 200);
        }

        if (!$this->orderValidator->isKlarnaOrder($orderEntity)) {
            return new JsonResponse(['status' => 'success'], 200);
        }

        if (!$this->orderValidator->validateAndInitLineItemsHash($orderEntity, $context)
            || !$this->orderValidator->validateAndInitOrderAddressHash($orderEntity, $context)) {
            throw new OrderUpdateDeniedException($orderId);
        }

        return new JsonResponse(['status' => 'success'], 200);
    }
}
