<?php

namespace Sendcloud\Shipping\Controller\API\Backend;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Sendcloud\Shipping\Components\Sendcloud\ShipmentTypes;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Exposed\TaskRunnerWakeup;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Entity\Config\SystemConfigurationRepository;
use Sendcloud\Shipping\Service\Utility\Initializer;
use Shopware\Core\Framework\Api\Response\JsonApiResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\Context;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DashboardController
 *
 * @package Sendcloud\Shipping\Controller\API\Backend
 */
#[Route(defaults: ["_routeScope" => ["api"]])]
class DashboardController extends AbstractController
{
    public const SENDCLOUD_URL = 'https://panel.sendcloud.sc/';

    private const SHIPMENT_TYPE_PRESELECTED_VALUE = 2;

    private const CUSTOMS_FIELD_DATA_TYPES = ['select', 'text', 'number'];

    private const MULTI_SELECT_COMPONENT_NAME = 'sw-multi-select';

    /**
     * @var \Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Configuration
     */
    private $configService;
    /**
     * @var TaskRunnerWakeup
     */
    private $wakeupService;
    /**
     * @var SystemConfigurationRepository
     */
    private $systemConfigurationRepository;
    /**
     * @var ParameterBagInterface
     */
    private $params;

    /**
     * DashboardController constructor.
     *
     * @param Initializer $initializer
     * @param Configuration $configService
     * @param TaskRunnerWakeup $wakeupService
     * @param SystemConfigurationRepository $systemConfigurationRepository
     * @param ParameterBagInterface $params
     */
    public function __construct(
        Initializer $initializer,
        Configuration $configService,
        TaskRunnerWakeup $wakeupService,
        SystemConfigurationRepository $systemConfigurationRepository,
        ParameterBagInterface $params
    )
    {
        $initializer->registerServices();
        $this->configService = $configService;
        $this->wakeupService = $wakeupService;
        $this->systemConfigurationRepository = $systemConfigurationRepository;
        $this->params = $params;
    }

    /**
     * Returns dashboard configuration
     *
     * @param Request $request
     *
     * @return JsonApiResponse
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/api/v{version}/sendcloud/dashboard', name: 'api.sendcloud.dashboard', methods: ["GET", "POST"])]
    #[Route('/api/sendcloud/dashboard', name: 'api.sendcloud.dashboard.new', methods: ["GET", "POST"])]
    public function getDashboardConfig(Request $request): JsonApiResponse
    {
        $this->wakeupService->wakeup();
        $data = [
            'isServicePointEnabled' => $this->configService->isServicePointEnabled(),
            'salesChannel' => $this->systemConfigurationRepository->getDefaultShopName(),
            'sendcloudUrl' => static::SENDCLOUD_URL,
            'countries' => $this->getCountryList(),
            'customFields' => $this->getCustomFields($request->getLocale()),
            'shipmentTypePreselectedValue' => self::SHIPMENT_TYPE_PRESELECTED_VALUE,
            'shipmentTypes' => ShipmentTypes::SHIPMENT_TYPES,
            'version' => $this->params->get('kernel.shopware_version'),
        ];

        return new JsonApiResponse($data);
    }

    /**
     * Gets all available countries from shopware system
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getCountryList(): array
    {
        $countryRepository = $this->container->get('country.repository');
        $countryList = $countryRepository->search(
            (new Criteria())->addSorting(new FieldSorting('name', FieldSorting::ASCENDING)),
            Context::createDefaultContext()
        )->getElements();
        $data = [];
        foreach ($countryList as $country) {
            $data[] = [
                'value' => $country->getIso(),
                'label' => $country->getName(),
            ];
        }

        return $data;
    }

    /**
     * Returns all available custom fields
     *
     * @param string $locale
     *
     * @return array
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getCustomFields(string $locale): array
    {
        $customFieldsRepository = $this->container->get('custom_field.repository');
        $criteria = new Criteria();
        $criteria->addAssociation('customFieldSet.relations');
        $criteria->addFilter(new EqualsFilter('customFieldSet.relations.entityName', 'product'));
        $criteria->addFilter(new EqualsAnyFilter('config.customFieldType', static::CUSTOMS_FIELD_DATA_TYPES));
        $fields = $customFieldsRepository->search($criteria, Context::createDefaultContext())->getElements();

        $data = [];
        foreach ($fields as $field) {
            if ($field->getConfig()['componentName'] !== self::MULTI_SELECT_COMPONENT_NAME) {
                $data[] = [
                    'value' => $field->getName(),
                    'label' => $field->getConfig()['label'][$locale] ?? $field->getName()
                ];
            }
        }

        return $data;
    }
}
