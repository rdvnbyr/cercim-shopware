<?php declare(strict_types=1);

namespace Sendcloud\Shipping\Migration;

use Doctrine\DBAL\Connection;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\QueueItem;
use Sendcloud\Shipping\Service\Utility\Task\UpdateIntegrationTask;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1596724410ChangeWebhookUrl extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1596724410;
    }

    public function update(Connection $connection): void
    {
        $task = new UpdateIntegrationTask();
        $connection->insert(
            'sendcloud_queues',
            [
                '`id`' => Uuid::randomBytes(),
                '`status`' => QueueItem::QUEUED,
                '`type`' => $task->getType(),
                '`queueName`' => 'global-utility-queue',
                '`serializedTask`' => serialize($task),
                '`createTimestamp`' => time(),
                '`queueTimestamp`' => time(),
            ]
        );
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
