<?php

namespace Sendcloud\Shipping\Controller\API\Backend;

use Sendcloud\Shipping\Core\BusinessLogic\Services\ConnectService;
use Sendcloud\Shipping\Service\Utility\Initializer;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class WelcomeController
 *
 * @package Sendcloud\Shipping\Controller\API\Backend
 */
#[Route(defaults: ["_routeScope" => ["api"]])]
class WelcomeController extends AbstractController
{
    /**
     * @var ConnectService
     */
    private $connectService;

    /**
     * WelcomeController constructor.
     *
     * @param Initializer $initializer
     * @param ConnectService $connectService
     */
    public function __construct(Initializer $initializer, ConnectService $connectService)
    {
        $initializer->registerServices();
        $this->connectService = $connectService;
    }

    /**
     * Returns redirect url for SendCloud connection screen
     * @return JsonApiResponse
     */
    #[Route('/api/v{version}/sendcloud/redirectUrl', name: 'api.sendcloud.redirectUrl', methods: ["GET", "POST"])]
    #[Route('/api/sendcloud/redirectUrl', name: 'api.sendcloud.redirectUrl.new', methods: ["GET", "POST"])]
    public function getRedirectUrl(): JsonApiResponse
    {
        $data = [
            'redirectUrl' => $this->connectService->getRedirectUrl(),
        ];

        return new JsonApiResponse($data);
    }
}
