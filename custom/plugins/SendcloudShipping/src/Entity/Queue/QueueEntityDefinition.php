<?php

namespace Sendcloud\Shipping\Entity\Queue;

use Sendcloud\Shipping\Migration\Migration1572012872CreateQueuesTable;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Class QueueEntityDefinition
 *
 * @package Sendcloud\Shipping\Entity\Queue
 */
class QueueEntityDefinition extends EntityDefinition
{
    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getEntityName(): string
    {
        return Migration1572012872CreateQueuesTable::QUEUES_TABLE;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return QueueEntity::class;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getCollectionClass(): string
    {
        return QueueEntityCollection::class;
    }

    /**
     * @inheritDoc
     *
     * @return FieldCollection
     */
    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new  PrimaryKey(), new Required()),
            (new IntField('internalId', 'internalId'))->addFlags(new WriteProtected()),
            new StringField('status', 'status'),
            new StringField('type', 'type'),
            new StringField('queueName', 'queueName'),
            new IntField('progress', 'progress'),
            new IntField('lastExecutionProgress', 'lastExecutionProgress'),
            new IntField('retries', 'retries'),
            new StringField('failureDescription', 'failureDescription'),
            new BlobField('serializedTask', 'serializedTask'),
            new IntField('createTimestamp', 'createTimestamp'),
            new IntField('queueTimestamp', 'queueTimestamp'),
            new IntField('lastUpdateTimestamp', 'lastUpdateTimestamp'),
            new IntField('startTimestamp', 'startTimestamp'),
            new IntField('finishTimestamp', 'finishTimestamp'),
            new IntField('failTimestamp', 'failTimestamp'),
        ]);
    }

    /**
     * Do not add timestamps as default fields
     *
     * @return array
     */
    protected function defaultFields(): array
    {
        return [];
    }
}
