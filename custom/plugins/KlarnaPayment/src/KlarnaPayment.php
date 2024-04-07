<?php

declare(strict_types=1);

namespace KlarnaPayment;

use KlarnaPayment\Installer\KlarnaInstaller;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

include_once 'Components/Helper/BackwardsCompatibility/RouteScope.php';

/**
 * @property ContainerInterface $container
 */
class KlarnaPayment extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $installContext): void
    {
        (new KlarnaInstaller($this->container))->install($installContext);
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $updateContext): void
    {
        (new KlarnaInstaller($this->container))->update($updateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $activateContext): void
    {
        (new KlarnaInstaller($this->container))->activate($activateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $deactivateContext): void
    {
        (new KlarnaInstaller($this->container))->deactivate($deactivateContext);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        (new KlarnaInstaller($this->container))->uninstall($uninstallContext);
    }
}
