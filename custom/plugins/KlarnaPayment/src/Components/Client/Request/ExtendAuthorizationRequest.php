<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use Shopware\Core\Framework\Struct\Struct;

class ExtendAuthorizationRequest extends Struct implements RequestInterface
{
    /** @var string */
    protected $method = 'POST';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/extend-authorization-time';

    /** @var ?string */
    protected $salesChannel;

    /** @var string */
    protected $orderId;

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getEndpoint(): string
    {
        return str_replace('{order_id}', $this->getOrderId(), $this->endpoint);
    }

    public function jsonSerialize(): array
    {
        return [
            'orderId' => $this->getOrderId(),
        ];
    }
}
