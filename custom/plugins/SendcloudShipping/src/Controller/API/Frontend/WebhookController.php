<?php

namespace Sendcloud\Shipping\Controller\API\Frontend;

use Sendcloud\Shipping\Core\BusinessLogic\DTO\WebhookDTO;
use Sendcloud\Shipping\Core\BusinessLogic\Exceptions\ActionNotSupportedException;
use Sendcloud\Shipping\Core\BusinessLogic\Webhook\WebhookResolver;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Service\Utility\Initializer;
use Sendcloud\Shipping\Utility\WebhookBootstrap;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class WebhookController
 *
 * @package Sendcloud\Shipping\Controller\API\Frontend
 */
#[Route(defaults: ["_routeScope" => ["api"]])]
class WebhookController extends AbstractController
{
    /**
     * @var WebhookResolver
     */
    private $webhookResolver;

    /**
     * WebhookController constructor.
     *
     * @param Initializer $initializer
     * @param WebhookResolver $webhookResolver
     */
    public function __construct(Initializer $initializer, WebhookResolver $webhookResolver)
    {
        $initializer->registerServices();
        $this->webhookResolver = $webhookResolver;
        WebhookBootstrap::init();
    }

    /**
     * Handles webhook request
     *
     * @param Request $request
     * @param string|null $token
     *
     * @return JsonApiResponse
     */
    #[Route('/api/sendcloud/webhook/{token}', name: 'api.sendcloud.webhook', defaults: ['auth_required' => false], methods: ["GET", "POST"])]
    public function handle(Request $request, string $token = null): JsonApiResponse
    {
        Logger::logInfo("Webhook from Sendcloud received.");

        $hash = $request->server->get('HTTP_SENDCLOUD_SIGNATURE', '');
        if (empty($hash)) {
            Logger::logError('Sendcloud signature not valid');
        }

        $token = $token ?: '';

        $webhookDTO = new WebhookDTO($request->getContent(), $hash, $token);
        try {
            $success = $this->webhookResolver->resolve($webhookDTO);
            $status = $success ? Response::HTTP_OK : Response::HTTP_CONFLICT;
        } catch (ActionNotSupportedException $exception) {
            Logger::logError($exception->getMessage());
            $success = false;
            $status = Response::HTTP_ACCEPTED;
        }

        return new JsonApiResponse(['success' => $success], $status);
    }
}
