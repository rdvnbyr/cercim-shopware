<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Schedule;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Run\Administration\LogCleaner;

/**
 * @internal
 */
#[Package('checkout')]
class CleanUpLogTaskHandler extends AbstractSyncTaskHandler
{
    private LogCleaner $logCleaner;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        EntityRepository $salesChannelRepository,
        LogCleaner $logCleaner
    ) {
        parent::__construct($scheduledTaskRepository, $salesChannelRepository);
        $this->logCleaner = $logCleaner;
    }

    public static function getHandledMessages(): iterable
    {
        return [CleanUpLogTask::class];
    }

    protected function executeTask(SalesChannelEntity $salesChannel, Context $context): void
    {
        $this->logCleaner->cleanUpLog($salesChannel->getId(), $context);
    }
}
