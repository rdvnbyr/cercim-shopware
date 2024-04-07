<?php

declare(strict_types=1);

namespace KlarnaPayment\Installer\Modules;

use KlarnaPayment\Components\ConfigReader\ConfigReaderInterface;
use KlarnaPayment\Installer\InstallerInterface;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\DeactivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ConfigInstaller implements InstallerInterface
{
    private const DEFAULT_VALUES = [
        'allowedKlarnaPaymentsCodes'         => ['pay_now', 'pay_later', 'pay_over_time'],
        'kpSendExtraMerchantData'            => true,
        'enableCorporateCustomerIntegration' => true,
        'externalPaymentMethods'             => [],
        'externalCheckouts'                  => [],
        'automaticRefund'                    => 'deactivated',
        'automaticCapture'                   => 'deactivated',
        'testMode'                           => true,
        'kpDisplayFooterBadge'               => true,
        'kcoDisplayFooterBadge'              => true,
        'kcoFooterBadgeStyle'                => 'long-blue',
        'isInitialized'                      => false,
        'klarnaType'                         => 'deactivated',
        'kpUseAuthorizationCallback'         => false,
    ];

    /** @var SystemConfigService */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context): void
    {
        $this->setDefaultValues();
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context): void
    {
        $this->setDefaultValues();
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context): void
    {
        // Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context): void
    {
        // Nothing to do here
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context): void
    {
        // Nothing to do here
    }

    private function setDefaultValues(): void
    {
        foreach (self::DEFAULT_VALUES as $key => $value) {
            $configKey = ConfigReaderInterface::SYSTEM_CONFIG_DOMAIN . $key;

            $currentValue = $this->systemConfigService->get($configKey);

            if ($currentValue !== null) {
                continue;
            }

            $this->systemConfigService->set($configKey, $value);
        }
    }
}
