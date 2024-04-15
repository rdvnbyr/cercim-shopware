<?php

namespace Sendcloud\Shipping\Controller\API\Frontend;

use Sendcloud\Shipping\Entity\ServicePoint\ServicePointEntityRepository;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ServicePointController
 *
 * @package Sendcloud\Shipping\Controller\API\Frontend
 */
#[Route(defaults: ["_routeScope" => ["api"]])]
class ServicePointController extends AbstractController
{
    /**
     * @var ServicePointEntityRepository
     */
    private $servicePointRepository;

    /**
     * ServicePointController constructor.
     *
     * @param ServicePointEntityRepository $servicePointRepository
     */
    public function __construct(ServicePointEntityRepository $servicePointRepository)
    {
        $this->servicePointRepository = $servicePointRepository;
    }

    /**
     * Saves service point information
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    #[Route('/api/v{version}/sendcloud/servicepoint/save', name: 'api.sendcloud.servicepoint.save', defaults: ['auth_required' => false], methods: ["POST"])]
    #[Route('/api/sendcloud/servicepoint/save', name: 'api.sendcloud.servicepoint.save', defaults: ['auth_required' => false], methods: ["POST"])]
    public function saveServicePointInfo(Request $request): JsonApiResponse
    {
        $success = false;
        $customerNumber = $request->get('customerNumber');
        $servicePointInfo = json_decode($request->getContent(), true);
        if ($customerNumber && $servicePointInfo) {
            $this->servicePointRepository->saveServicePoint($customerNumber, $servicePointInfo);
            $success = true;
        }

        return new JsonApiResponse(['success' => $success]);
    }

    /**
     * Retrieves service point information
     * @param Request $request
     *
     * @return JsonApiResponse
     */
    #[Route('/api/v{version}/sendcloud/servicepoint', name: 'api.sendcloud.servicepoint', defaults: ['auth_required' => false], methods: ["GET"])]
    #[Route('/api/sendcloud/servicepoint', name: 'api.sendcloud.servicepoint.new', defaults: ['auth_required' => false], methods: ["GET"])]
    public function getServicePointInfo(Request $request): JsonApiResponse
    {
        $servicePointInfo = [];
        $customerNumber = $request->get('customerNumber');
        if ($customerNumber) {
            $servicePointEntity = $this->servicePointRepository->getServicePointByCustomerNumber($customerNumber);
            $servicePointInfo = $servicePointEntity ? json_decode($servicePointEntity->get('servicePointInfo'), true) : [];
        }

        return new JsonApiResponse(['servicePointInfo' => $servicePointInfo]);
    }
}
