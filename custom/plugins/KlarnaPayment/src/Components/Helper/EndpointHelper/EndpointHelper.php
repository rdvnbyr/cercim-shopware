<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Helper\EndpointHelper;

use Doctrine\DBAL\Connection;
use KlarnaPayment\Components\Client\Request\RequestInterface;
use KlarnaPayment\Components\Helper\BackwardsCompatibility\DbalConnectionHelper;
use KlarnaPayment\Installer\Modules\PaymentMethodInstaller;

class EndpointHelper implements EndpointHelperInterface
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function resolveEndpointRegion(RequestInterface $request): string
    {
        if (method_exists($request, 'getOrderId')) {
            return $this->getBillingCountryISOCode($request->getOrderId());
        }

        if (method_exists($request, 'getPurchaseCountry')) {
            if ($request->getPurchaseCountry() === PaymentMethodInstaller::KLARNA_API_REGION_US) {
                return $request->getPurchaseCountry();
            }
        }

        return PaymentMethodInstaller::KLARNA_API_REGION_EU;
    }

    private function getBillingCountryISOCode(string $orderId): string
    {
        $isoCode = DbalConnectionHelper::fetchColumn($this->connection,
            'SELECT c.iso
            FROM `order` o
            LEFT JOIN order_transaction ot ON o.id = ot.order_id
            LEFT JOIN order_address oa ON o.billing_address_id = oa.id
            LEFT JOIN country c ON oa.country_id = c.id
            WHERE ot.custom_fields LIKE :orderId',
            ['orderId' => '%' . $orderId . '%']
        );

        if ($isoCode === PaymentMethodInstaller::KLARNA_API_REGION_US) {
            return $isoCode;
        }

        return PaymentMethodInstaller::KLARNA_API_REGION_EU;
    }
}
