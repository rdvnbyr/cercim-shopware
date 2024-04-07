<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Client\Struct\LineItem;
use Shopware\Core\Framework\Struct\Struct;

class CreateRefundRequest extends Struct implements RequestInterface
{
    /** @var string */
    protected $method = 'POST';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/refunds';

    /** @var ?string */
    protected $salesChannel;

    /** @var string */
    protected $orderId = '';

    /** @var float */
    protected $refundAmount = 0.0;

    /** @var ?string */
    protected $description;

    /** @var string */
    protected $reference = '';

    /** @var LineItem[] */
    protected $orderLines = [];

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return str_replace('{order_id}', $this->orderId, $this->endpoint);
    }

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getRefundAmount(): float
    {
        return $this->refundAmount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    /**
     * @return LineItem[]
     */
    public function getOrderLines(): array
    {
        return $this->orderLines;
    }

    public function jsonSerialize(): array
    {
        return [
            'refunded_amount' => (int) round($this->getRefundAmount() * 100, 0),
            'description'     => $this->getDescription(),
            'reference'       => $this->getReference(),
            'order_lines'     => $this->getOrderLines(),
        ];
    }
}
