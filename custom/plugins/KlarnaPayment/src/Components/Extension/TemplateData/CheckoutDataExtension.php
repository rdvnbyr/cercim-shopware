<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Extension\TemplateData;

use Shopware\Core\Framework\Struct\Struct;

class CheckoutDataExtension extends Struct
{
    public const EXTENSION_NAME = 'klarna_checkout_data';

    public const TYPE_CHECKOUT = 'checkout';
    public const TYPE_PAYMENTS = 'payments';

    /** @var string */
    protected $klarnaType;

    public function getKlarnaType(): string
    {
        return $this->klarnaType;
    }
}
