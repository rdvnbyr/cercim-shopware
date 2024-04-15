<?php


namespace Sendcloud\Shipping\Controller\API\Frontend;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\Runnable;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Entity\Process\ProcessEntityRepository;
use Sendcloud\Shipping\Service\Utility\Initializer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AsyncProcessController
 *
 * @package Sendcloud\Shipping\Controller\API\Frontend
 */
#[Route(defaults: ["_routeScope" => ["api"]])]
class AsyncProcessController extends AbstractController
{
    /**
     * @var ProcessEntityRepository
     */
    private $processRepository;

    /**
     * AsyncProcessController constructor.
     *
     * @param Initializer $initializer
     * @param ProcessEntityRepository $processRepository
     */
    public function __construct(Initializer $initializer, ProcessEntityRepository $processRepository)
    {
        $initializer->registerServices();
        $this->processRepository = $processRepository;
    }

    /**
     * Async process starter endpoint
     * @param string $guid
     *
     * @return JsonResponse
     */
    #[Route('/api/v{version}/sendcloud/async/{guid}', name: 'api.sendcloud.async', defaults: ['auth_required' => false], methods: ["GET", "POST"])]
    #[Route('/api/sendcloud/async/{guid}', name: 'api.sendcloud.async.new', defaults: ['auth_required' => false], methods: ["GET", "POST"])]
    public function run(string $guid): JsonResponse
    {
        try {
            $processEntity = $this->processRepository->getProcessByGuid($guid);
            if ($processEntity) {
                /** @var Runnable $runner */
                $runner =unserialize($processEntity->get('runner'));
                $runner->run();
            }

            $this->processRepository->deleteByGuid($guid);
        } catch (\Exception $exception) {
            Logger::logError("Ann error occurred when accessing process table: {$exception->getMessage()}");
        }

        return new JsonResponse(['success' => true]);
    }
}
