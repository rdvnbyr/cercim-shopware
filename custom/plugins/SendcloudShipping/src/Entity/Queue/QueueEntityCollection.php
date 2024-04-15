<?php


namespace Sendcloud\Shipping\Entity\Queue;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Class QueueEntityCollection
 *
 * @package Sendcloud\Shipping\Entity\Queue
 *
 * @method void              add(QueueEntity $entity)
 * @method void              set(string $key, QueueEntity $entity)
 * @method QueueEntity[]    getIterator()
 * @method QueueEntity[]    getElements()
 * @method QueueEntity|null get(string $key)
 * @method QueueEntity|null first()
 * @method QueueEntity|null last()
 */
class QueueEntityCollection extends EntityCollection
{
    /**
     * @inheritDoc
     *
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return QueueEntity::class;
    }
}
