<?php

namespace Sendcloud\Shipping;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\InvalidArgumentException;
use Sendcloud\Shipping\Entity\Config\ConfigEntityRepository;
use Sendcloud\Shipping\Service\Utility\DatabaseHandler;
use Sendcloud\Shipping\Service\Utility\ShippingMethodHandler;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

/**
 * Class SendcloudShipping
 *
 * @package Sendcloud\Shipping
 */
class SendcloudShipping extends Plugin
{
    /**
     * @param InstallContext $installContext
     * @return void
     */
    public function postInstall(InstallContext $installContext): void
    {
        parent::postInstall($installContext);

        $this->getShippingMethodHandler()->addServicePointShippingMethod();
    }

    /**
     * @param UninstallContext $uninstallContext
     * @return void
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if (!$uninstallContext->keepUserData()) {
            $this->removeAllSendCloudTables();
        }
    }

    /**
     * @param UpdateContext $updateContext
     *
     * @return void
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);
        if (version_compare($updateContext->getCurrentPluginVersion(), '1.1.6', 'lt')) {
            $this->removeIntegrationConnectTask();
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function removeAllSendCloudTables(): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $databaseHandler = new DatabaseHandler($connection);
        $databaseHandler->removeSendCloudTables();
    }

    /**
     * @return void
     * @throws Exception
     */
    private function removeIntegrationConnectTask(): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $databaseHandler = new DatabaseHandler($connection);
        $databaseHandler->removeIntegrationConnectConnectTask();
    }

    /**
     * Returns shipping method handler
     *
     * @return ShippingMethodHandler
     */
    private function getShippingMethodHandler(): ShippingMethodHandler
    {
        /** @var EntityRepository $deliveryTimeRepository */
        $deliveryTimeRepository = $this->container->get('delivery_time.repository');
        /** @var EntityRepository $rulesRepository */
        $rulesRepository = $this->container->get('rule.repository');
        /** @var EntityRepository $shippingMethodRepository */
        $shippingMethodRepository = $this->container->get('shipping_method.repository');
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        $configRepository = new ConfigEntityRepository($connection);

        /** @var EntityRepository $systemConfigRepository */
        $systemConfigRepository = $this->container->get('system_config.repository');

        return new ShippingMethodHandler(
            $shippingMethodRepository, $rulesRepository, $deliveryTimeRepository, $configRepository, $systemConfigRepository
        );
    }
}
