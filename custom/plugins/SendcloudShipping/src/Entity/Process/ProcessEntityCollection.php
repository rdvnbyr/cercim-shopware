<?php

namespace Sendcloud\Shipping\Entity\Process;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Class ProcessEntityCollection
 *
 * @package Sendcloud\Shipping\Entity\Process
 *
 * @method void              add(ProcessEntity $entity)
 * @method void              set(string $key, ProcessEntity $entity)
 * @method ProcessEntity[]    getIterator()
 * @method ProcessEntity[]    getElements()
 * @method ProcessEntity|null get(string $key)
 * @method ProcessEntity|null first()
 * @method ProcessEntity|null last()
 */
class ProcessEntityCollection extends EntityCollection
{
    /**
     * @inheritDoc
     *
     * @return string
     */
    protected function getExpectedClass(): string
    {
        return ProcessEntity::class;
    }
}
