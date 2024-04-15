<?php

namespace Sendcloud\Shipping\Service\Infrastructure;

use Composer\Plugin\PluginInterface;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\Runnable;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\AsyncProcessStarter;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\HttpClient;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Exceptions\ProcessStarterSaveException;
use Sendcloud\Shipping\Core\Infrastructure\Utility\Exceptions\HttpRequestException;
use Sendcloud\Shipping\Core\Infrastructure\Utility\GuidProvider;
use Sendcloud\Shipping\Entity\Process\ProcessEntityRepository;
use Shopware\Core\PlatformRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AsyncProcessStarterService
 *
 * @package Sendcloud\Shipping\Service\Infrastructure
 */
class AsyncProcessStarterService implements AsyncProcessStarter
{
    /**
     * @var HttpClient
     */
    private $httpClient;
    /**
     * @var ProcessEntityRepository
     */
    private $processRepository;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * AsyncProcessStarterService constructor.
     *
     * @param HttpClient $httpClient
     * @param ProcessEntityRepository $processRepository
     * @param UrlGeneratorInterface $urlGenerator
     * @param ParameterBagInterface $params
     */
    public function __construct(
        HttpClient $httpClient,
        ProcessEntityRepository $processRepository,
        UrlGeneratorInterface $urlGenerator,
        ParameterBagInterface $params
    ) {
        $this->httpClient = $httpClient;
        $this->processRepository = $processRepository;
        $this->urlGenerator = $urlGenerator;
        $this->params = $params;
    }

    /**
     * Starts given runner asynchronously (in new process/web request or similar)
     *
     * @param Runnable $runner Runner that should be started async
     *
     * @throws ProcessStarterSaveException
     * @throws HttpRequestException
     */
    public function start(Runnable $runner): void
    {
        $guidProvider = new GuidProvider();
        $guid = trim($guidProvider->generateGuid());

        $this->saveGuidAndRunner($guid, $runner);
        $this->startRunnerAsynchronously($guid);
    }

    /**
     * Saves guid and runner into process table
     *
     * @param string $guid process identifier
     * @param Runnable $runner instance of TaskRunnerStarter or QueueItemStarter
     *
     * @throws ProcessStarterSaveException
     */
    private function saveGuidAndRunner(string $guid, Runnable $runner): void
    {
        try {
            $this->processRepository->saveGuidAndRunner($guid, serialize($runner));
        } catch (\Exception $e) {
            Logger::logError('Failed to save process: ' . $e->getMessage(), 'Integration');
            throw new ProcessStarterSaveException($e->getMessage());
        }
    }

    /**
     * Sends async request to AsyncProcessController with guid as query parameter
     *
     * @param string $guid process identifier
     *
     * @throws HttpRequestException
     */
    public function startRunnerAsynchronously(string $guid): void
    {
        try {
            $this->httpClient->requestAsync('GET', $this->formatAsyncProcessStartUrl($guid));
        } catch (\Exception $e) {
            Logger::logError('Failed to send async request: ' . $e->getMessage(), 'Integration');
            throw new HttpRequestException($e->getMessage());
        }
    }

    /**
     * Returns async process controller url
     *
     * @param string $guid
     *
     * @return string
     */
    private function formatAsyncProcessStartUrl(string $guid): string
    {
        $routeName = 'api.sendcloud.async.new';
        $params = ['guid' => $guid];
        if (version_compare($this->params->get('kernel.shopware_version'), '6.4.0', 'lt')) {
            $routeName = 'api.sendcloud.async';
            $params['version'] = PluginInterface::PLUGIN_API_VERSION;
        }

        return $this->urlGenerator->generate($routeName, $params, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
