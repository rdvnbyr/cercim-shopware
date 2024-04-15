<?php


namespace Sendcloud\Shipping\Entity\Shipment;


use Sendcloud\Shipping\Migration\Migration1573059308CreateShipmentsTable;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ShipmentEntityDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return Migration1573059308CreateShipmentsTable::SHIPMENTS_TABLE;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getEntityClass(): string
    {
        return ShipmentEntity::class;
    }

    /**
     * @inheritDoc
     *
     * @return string
     */
    public function getCollectionClass(): string
    {
        return ShipmentEntityCollection::class;
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
            new StringField('orderNumber', 'orderNumber'),
            new StringField('sendcloudStatus', 'sendcloudStatus'),
            new StringField('servicePointId', 'servicePointId'),
            new LongTextField('servicePointInfo', 'servicePointInfo'),
            new StringField('trackingNumber', 'trackingNumber'),
            new StringField('trackingUrl', 'trackingUrl'),
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
