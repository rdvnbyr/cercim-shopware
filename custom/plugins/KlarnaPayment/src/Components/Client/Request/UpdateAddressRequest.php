<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Request;

use KlarnaPayment\Components\Client\Struct\Address;
use Shopware\Core\Framework\Struct\Struct;

class UpdateAddressRequest extends Struct implements RequestInterface
{
    /** @var string */
    protected $method = 'PATCH';

    /** @var string */
    protected $endpoint = '/ordermanagement/v1/orders/{order_id}/customer-details';

    /** @var null|string */
    protected $salesChannel;

    /** @var string */
    protected $orderId = '';

    /** @var ?Address */
    protected $billingAddress;

    /** @var Address */
    protected $shippingAddress;

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function getShippingAddress(): Address
    {
        return $this->shippingAddress;
    }

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

    public function jsonSerialize(): array
    {
        return [
            'billing_address'  => $this->getBillingAddress(),
            'shipping_address' => $this->getShippingAddress(),
        ];
    }
}
