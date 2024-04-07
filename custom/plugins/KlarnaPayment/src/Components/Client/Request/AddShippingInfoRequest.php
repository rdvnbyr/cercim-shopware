<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Struct\ShippingInfo;
use Shopware\Core\Framework\Struct\Struct;

class AddShippingInfoRequest extends Struct implements RequestInterface
{
    /** @var string */
    protected $method = 'POST';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/captures/{capture_id}/shipping-info';

    /** @var ?string */
    protected $salesChannel;

    /** @var string */
    protected $captureId;

    /** @var string */
    protected $orderId;

    /** @var ShippingInfo[] */
    protected $shippingInfos;

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getEndpoint(): string
    {
        return str_replace(['{order_id}', '{capture_id}'], [$this->getOrderId(), $this->getCaptureId()], $this->endpoint);
    }

    public function getSalesChannel(): ?string
    {
        return $this->salesChannel;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function getCaptureId(): string
    {
        return $this->captureId;
    }

    /**
     * @return ShippingInfo[]
     */
    public function getShippingInfos(): array
    {
        return $this->shippingInfos;
    }

    public function jsonSerialize(): array
    {
        return [
            'shipping_info' => $this->getShippingInfos(),
        ];
    }
}
