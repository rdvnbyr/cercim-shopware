<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Request\CreateRefund;

use KlarnaPayment\Components\Client\Request\CreateRefundRequest;
use KlarnaPayment\Components\Client\Struct\LineItem;
use KlarnaPayment\Components\Client\Struct\ProductIdentifier;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;

class CreateRefundRequestHydrator implements CreateRefundRequestHydratorInterface
{
    public function hydrate(RequestDataBag $dataBag, Context $context): CreateRefundRequest
    {
        $refundAmount = (float) $dataBag->get('refundAmount');

        $orderLines = json_decode($dataBag->get('orderLines'), true);

        $request = new CreateRefundRequest();
        $request->assign([
            'salesChannel' => $dataBag->get('salesChannel'),
            'orderId'      => $dataBag->get('klarna_order_id'),
            'refundAmount' => $refundAmount,
            'reference'    => Uuid::randomHex(),
            'orderLines'   => $this->hydrateOrderLines($orderLines),
        ]);

        if (!empty($dataBag->get('description'))) {
            $request->assign([
                'description' => substr($dataBag->get('description'), 0, 255),
            ]);
        }

        return $request;
    }

    /**
     * @param array<string,mixed> $orderLines
     *
     * @return LineItem[]
     */
    private function hydrateOrderLines(array $orderLines): array
    {
        $lineItems = [];

        foreach ($orderLines as $orderLine) {
            $lineItem = new LineItem();

            if (!empty($orderLine['product_identifiers'])) {
                $productIdentifier = new ProductIdentifier();
                $productIdentifier->assign([
                    'brand'                  => $orderLine['product_identifiers']['brand'] ?? null,
                    'categoryPath'           => $orderLine['product_identifiers']['category_path'] ?? null,
                    'globalTradeItemNumber'  => $orderLine['product_identifiers']['global_trade_item_number'] ?? null,
                    'manufacturerPartNumber' => $orderLine['product_identifiers']['manufacturer_part_number'] ?? null,
                ]);

                $lineItem->assign([
                    'productIdentifier' => $productIdentifier,
                ]);
            }

            $lineItem->assign([
                'reference'      => $orderLine['reference'],
                'type'           => $orderLine['type'],
                'quantity'       => $orderLine['quantity'],
                'quantityUnit'   => $orderLine['quantity_unit'] ?? null,
                'name'           => $orderLine['name'],
                'totalAmount'    => $orderLine['total_amount'] / 100,
                'unitPrice'      => $orderLine['unit_price'] / 100,
                'taxRate'        => $orderLine['tax_rate'] / 100,
                'totalTaxAmount' => $orderLine['total_tax_amount'] / 100,
            ]);

            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }
}
