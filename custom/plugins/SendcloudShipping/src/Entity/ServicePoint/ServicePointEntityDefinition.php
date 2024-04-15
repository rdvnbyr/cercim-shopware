<?php

namespace Sendcloud\Shipping\Entity\ServicePoint;

use Sendcloud\Shipping\Migration\Migration1574260096CreateServicePointsTable;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

/**
 * Class ServicePointEntityDefinition
 *
 * @package Sendcloud\Shipping\Entity\ServicePoint
 */
class ServicePointEntityDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return Migration1574260096CreateServicePointsTable::SERVICE_POINTS_TABLE;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return ServicePointEntity::class;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getCollectionClass(): string
    {
        return ServicePointEntityCollection::class;
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
            new StringField('customerNumber', 'customerNumber'),
            new LongTextField('servicePointInfo', 'servicePointInfo'),
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
