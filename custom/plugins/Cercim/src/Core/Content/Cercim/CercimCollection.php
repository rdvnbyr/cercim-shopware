<?php declare(strict_types=1);

namespace Cercim\Core\Content\Cercim;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void add(CercimEntity $entity)
 * @method void set(string $key, CercimEntity $entity)
 * @method CercimEntity[] getIterator()
 * @method CercimEntity[] getElements()
 * @method CercimEntity|null get(string $key)
 * @method CercimEntity|null first()
 * @method CercimEntity|null last()
 */
class CercimCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return CercimEntity::class;
    }
}
