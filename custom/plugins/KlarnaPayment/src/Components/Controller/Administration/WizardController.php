<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Controller\Administration;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 * @Route(defaults={"_routeScope": {"api"}})
 */
class WizardController extends AbstractController
{
    /** @var ConfigReaderInterface */
    private $configReader;

    /** @var SystemConfigService */
    private $systemConfig;

    /** @var EntityRepository */
    private $repository;

    public function __construct(
        ConfigReaderInterface $configReader,
        SystemConfigService $systemConfig,
        EntityRepository $repository
    ) {
        $this->configReader = $configReader;
        $this->systemConfig = $systemConfig;
        $this->repository   = $repository;
    }

    /**
     * @Route("/api/_action/klarna_payment/finalize_installation", name="api.action.klarna_payment.finalize.installation", methods={"POST"})
     * @Route("/api/v{version}/_action/klarna_payment/finalize_installation", name="api.action.klarna_payment.finalize.installation.legacy", methods={"POST"})
     */
    public function finalizeInstallation(RequestDataBag $dataBag): JsonResponse
    {
        $salesChannels = json_decode($dataBag->get('tableData'), true);

        $configKeyIsInitialized = ConfigReaderInterface::SYSTEM_CONFIG_DOMAIN . 'isInitialized';
        $configKeyKlarnaType    = ConfigReaderInterface::SYSTEM_CONFIG_DOMAIN . 'klarnaType';

        $this->systemConfig->set($configKeyIsInitialized, true);

        foreach ($salesChannels as $salesChannel) {
            $this->systemConfig->set($configKeyKlarnaType, $salesChannel['klarnaType'], $salesChannel['id']);
        }

        return new JsonResponse(['status' => 'success'], 200);
    }

    /**
     * @Route("/api/_action/klarna_payment/fetch_data", name="api.action.klarna_payment.fetch.data", methods={"GET"})
     * @Route("/api/v{version}/_action/klarna_payment/fetch_data", name="api.action.klarna_payment.fetch.data.legacy", methods={"GET"})
     */
    public function fetchData(Context $context): JsonResponse
    {
        $salesChannels = $this->fetchSalesChannels($context);

        $result = [
            'isInitialized' => $this->isInitialized(),
            'salesChannels' => $salesChannels,
        ];

        return new JsonResponse(['status' => 'success', 'data' => $result], 200);
    }

    /**
     * @return array<int|string,mixed>
     */
    private function fetchSalesChannels(Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addSorting(new FieldSorting('shortName'));

        /** @var SalesChannelEntity[] $salesChannels */
        $salesChannels = $this->repository->search($criteria, $context)->getEntities()->getElements();

        if (empty($salesChannels)) {
            return [];
        }

        array_unshift($salesChannels, 'all');

        $result = [];
        foreach ($salesChannels as $salesChannel) {
            if ($salesChannel === 'all') {
                $id = null;
            } else {
                $id = $salesChannel->getId();
            }

            $config = $this->configReader->read($id, false);

            $klarnaType = null;

            if (!empty($config->get('klarnaType'))) {
                $klarnaType = $config->get('klarnaType');
            }

            $result[] = compact('id', 'klarnaType');
        }

        return $result;
    }

    private function isInitialized(): bool
    {
        $config = $this->configReader->read(null, false);

        return (bool) $config->get('isInitialized');
    }
}
