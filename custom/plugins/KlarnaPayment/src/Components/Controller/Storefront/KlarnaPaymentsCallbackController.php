<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Storefront;

use KlarnaPayment\Components\Callback\AuthorizationCallback;
use KlarnaPayment\Components\Callback\NotificationCallback;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 * @Route(defaults={"_routeScope": {"storefront"}})
 */
class KlarnaPaymentsCallbackController extends StorefrontController
{
    /** @var NotificationCallback */
    private $notificationCallback;

    /** @var AuthorizationCallback */
    private $authorizationCallback;

    /** @var SalesChannelContextPersister */
    private $contextPersister;

    /** @var SalesChannelContextFactory */
    private $contextFactory;

    /**
     * Because of backwards compatibility to Shopware 6.3, we can't use the typehint in the argument directly
     *
     * @param SalesChannelContextFactory $contextFactory
     */
    public function __construct(NotificationCallback $notificationCallback, AuthorizationCallback $authorizationCallback, SalesChannelContextPersister $contextPersister, $contextFactory)
    {
        $this->notificationCallback  = $notificationCallback;
        $this->authorizationCallback = $authorizationCallback;
        $this->contextPersister      = $contextPersister;
        $this->contextFactory        = $contextFactory;
    }

    /**
     * @Route("/klarna/callback/notification/{transaction_id}", defaults={"csrf_protected": false, "XmlHttpRequest": true}, name="widgets.klarna.callback.notification", methods={"POST"})
     */
    public function notificationCallback(Request $request, SalesChannelContext $context): Response
    {
        $this->notificationCallback->handle($request->get('transaction_id'), (string) $request->get('event_type'), $context);

        return new Response();
    }

    /**
     * @Route("/klarna/callback/authorization/{cart_token}", defaults={"csrf_protected": false, "XmlHttpRequest": true}, name="widgets.klarna.callback.authorization", methods={"POST"})
     */
    public function authorizationCallback(Request $request, SalesChannelContext $context): Response
    {
        $token = $request->get('cart_token');

        // TODO: Remove when compatibility is at least 6.3.4.0
        if (!method_exists($context, 'getSalesChannelId')) {
            /** @phpstan-ignore-next-line */
            $customerPayload = $this->contextPersister->load($token);
        } else {
            /** @phpstan-ignore-next-line */
            $customerPayload = $this->contextPersister->load($token, $context->getSalesChannel()->getId());
        }

        $customerContext = $this->contextFactory->create($token, $context->getSalesChannel()->getId(), $customerPayload);

        $this->authorizationCallback->handle($request->get('authorization_token'), $customerContext);

        return new Response();
    }
}
