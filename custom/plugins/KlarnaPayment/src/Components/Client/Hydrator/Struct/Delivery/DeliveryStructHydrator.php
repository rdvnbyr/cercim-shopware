<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\Delivery;

use KlarnaPayment\Components\Client\Struct\LineItem;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Currency\CurrencyEntity;

class DeliveryStructHydrator implements DeliveryStructHydratorInterface
{
    public const NAME = 'SHIPPING_COSTS';

    /** @var EntityRepository */
    private $shippingMethodRepository;

    public function __construct(EntityRepository $shippingMethodRepository)
    {
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    /**
     * @return LineItem[]
     */
    public function hydrate(DeliveryCollection $deliveries, CurrencyEntity $currency, Context $context): array
    {
        $lineItems = [];

        foreach ($deliveries as $delivery) {
            if (empty($delivery->getShippingCosts()->getTotalPrice())) {
                continue;
            }

            $shippingMethodName = $this->getShippingMethodName($delivery, $context);

            if ($context->getTaxState() === CartPrice::TAX_STATE_FREE) {
                $lineItem = new LineItem();
                $lineItem->assign([
                    'type'        => LineItem::TYPE_PHYSICAL,
                    'reference'   => self::NAME,
                    'name'        => $shippingMethodName,
                    'quantity'    => $delivery->getShippingCosts()->getQuantity(),
                    'unitPrice'   => $delivery->getShippingCosts()->getUnitPrice(),
                    'totalAmount' => $delivery->getShippingCosts()->getTotalPrice(),
                ]);

                $lineItems[] = $lineItem;
            }

            foreach ($delivery->getShippingCosts()->getCalculatedTaxes() as $tax) {
                $totalAmount = $tax->getPrice();
                $unitPrice   = $tax->getPrice();

                if ($context->getTaxState() === CartPrice::TAX_STATE_NET) {
                    $totalAmount += $tax->getTax();
                    $unitPrice += $tax->getTax();
                }

                $lineItem = new LineItem();
                $lineItem->assign([
                    'type'           => LineItem::TYPE_PHYSICAL,
                    'reference'      => self::NAME,
                    'name'           => $shippingMethodName,
                    'quantity'       => $delivery->getShippingCosts()->getQuantity(),
                    'unitPrice'      => $unitPrice,
                    'totalAmount'    => $totalAmount,
                    'totalTaxAmount' => $tax->getTax(),
                    'taxRate'        => $tax->getTaxRate(),
                ]);

                $lineItems[] = $lineItem;
            }
        }

        return $lineItems;
    }

    private function getShippingMethodName(Delivery $delivery, Context $context): string
    {
        $shippingMethod = $delivery->getShippingMethod();

        $criteria = (new Criteria([$shippingMethod->getId()]))
            ->addAssociation('translations')
            ->setLimit(1);

        $shippingMethod = $this->shippingMethodRepository->search($criteria, $context)->first();

        if ($shippingMethod === null || $shippingMethod->getName() === null) {
            return self::NAME;
        }

        return $shippingMethod->getName();
    }
}
