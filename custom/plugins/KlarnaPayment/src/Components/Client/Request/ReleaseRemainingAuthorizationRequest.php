<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use Shopware\Core\Framework\Struct\Struct;

class ReleaseRemainingAuthorizationRequest extends Struct implements RequestInterface
{
    /** @var string */
    protected $method = 'GET';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/release-remaining-authorization';

    /** @var ?string */
    protected $salesChannel;

    /** @var string */
    protected $orderId = '';

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return str_replace('{order_id}', $this->getOrderId(), $this->endpoint);
    }

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function jsonSerialize(): array
    {
        return [
            'klarna_order_id' => $this->getOrderId(),
            'sales_channel'   => $this->getSalesChannel(),
        ];
    }
}
