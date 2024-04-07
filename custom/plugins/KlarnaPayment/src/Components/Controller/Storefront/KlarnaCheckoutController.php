<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Storefront;

use KlarnaPayment\Components\DataAbstractionLayer\Entity\Order\OrderExtension;
use KlarnaPayment\Components\Event\OrderCreatedThroughAuthorizationCallback;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\CheckoutController;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 * @Route(defaults={"_routeScope": {"storefront"}})
 */
class KlarnaCheckoutController extends StorefrontController
{
    /** @var CheckoutController */
    private $parent;

    /** @var EntityRepository */
    private $orderRepository;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var EntityRepository */
    private $cartDataRepository;

    public function __construct(CheckoutController $parent, EntityRepository $orderRepository, EventDispatcherInterface $eventDispatcher, EntityRepository $cartDataRepository)
    {
        $this->parent             = $parent;
        $this->orderRepository    = $orderRepository;
        $this->eventDispatcher    = $eventDispatcher;
        $this->cartDataRepository = $cartDataRepository;
    }

    public function order(RequestDataBag $data, SalesChannelContext $context, Request $request): Response
    {
        if (!$data->has('klarnaAuthorizationToken')) {
            return $this->parent->order($data, $context, $request);
        }

        // If the order has been created through the authorization callback, forward the user to the finish page
        $criteria = new Criteria();
        $criteria
            ->addAssociation(OrderExtension::EXTENSION_NAME)
            ->addFilter(new EqualsFilter(OrderExtension::EXTENSION_NAME . '.authorizationToken', $data->get('klarnaAuthorizationToken')));

        $orderId = $this->orderRepository->searchIds($criteria, $context->getContext())->getIds()[0] ?? null;

        if (empty($orderId)) {
            return $this->parent->order($data, $context, $request);
        }

        $finishPage = $this->generateUrl('frontend.checkout.finish.page', ['orderId' => $orderId]);

        $this->eventDispatcher->dispatch(new OrderCreatedThroughAuthorizationCallback());

        return new RedirectResponse($finishPage);
    }

    /**
     * @Route("/klarna/checkout/saveFormData", defaults={"csrf_protected": false, "XmlHttpRequest": true}, name="widgets.klarna.checkout.save", methods={"POST"})
     */
    public function saveFormData(RequestDataBag $dataBag, SalesChannelContext $context): Response
    {
        // Save inputs excluding tos and inputs containing klarna
        $params = array_filter($dataBag->all(), static function ($param) {
            return !(strpos((string) $param, 'klarna') !== false) && $param !== 'tos';
        }, ARRAY_FILTER_USE_KEY);

        $this->cartDataRepository->upsert([['cartToken' => $context->getToken(), 'payload' => $params]], $context->getContext());

        return new Response();
    }

    public function cartPage(Request $request, SalesChannelContext $context): Response
    {
        return $this->parent->cartPage($request, $context);
    }

    public function cartJson(Request $request, SalesChannelContext $context): Response
    {
        if (!method_exists($this->parent, 'cartJson')) {
            return new Response();
        }

        return $this->parent->cartJson($request, $context);
    }

    public function confirmPage(Request $request, SalesChannelContext $context): Response
    {
        return $this->parent->confirmPage($request, $context);
    }

    public function finishPage(Request $request, SalesChannelContext $context, RequestDataBag $dataBag): Response
    {
        /** @phpstan-ignore-next-line */
        return $this->parent->finishPage($request, $context, $dataBag);
    }

    public function info(Request $request, SalesChannelContext $context): Response
    {
        return $this->parent->info($request, $context);
    }

    public function offcanvas(Request $request, SalesChannelContext $context): Response
    {
        return $this->parent->offcanvas($request, $context);
    }
}
