<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Client\Hydrator\Struct\Customer;

use KlarnaPayment\Components\Client\Struct\Customer;
use KlarnaPayment\Components\Helper\PaymentHelper\PaymentHelperInterface;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CustomerStructHydrator implements CustomerStructHydratorInterface
{
    public const TYPE_PERSON       = 'person';
    public const TYPE_ORGANIZATION = 'organization';

    /** @var PaymentHelperInterface */
    private $paymentHelper;

    public function __construct(PaymentHelperInterface $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
    }

    public function hydrate(SalesChannelContext $context): ?Customer
    {
        $contextCustomer = $context->getCustomer();

        if ($contextCustomer === null) {
            return null;
        }

        $customer = new Customer();

        if ($this->paymentHelper->isKlarnaPaymentsEnabled($context) && $contextCustomer->getBirthday() !== null) {
            $customer->assign([
                'birthday' => $contextCustomer->getBirthday(),
            ]);
        }

        $billingAddress = $this->getBillingAddress($contextCustomer);

        if ($billingAddress === null) {
            return $customer;
        }

        $type = !empty($billingAddress->getCompany())
            ? self::TYPE_ORGANIZATION
            : self::TYPE_PERSON;

        $customer->assign([
            'type' => $type,
        ]);

        if (!$this->paymentHelper->isKlarnaPaymentsSelected($context)) {
            return $customer;
        }

        $customer->assign([
            'vatId' => $this->getVatId($billingAddress, $contextCustomer),
            'title' => $billingAddress->getTitle(),
        ]);

        if ($type === self::TYPE_ORGANIZATION) {
            $customFields = $billingAddress->getCustomFields();

            if ($customFields === null || !isset($customFields['klarna_customer_entity_type'], $customFields['klarna_customer_registration_id'])) {
                return $customer;
            }

            $customer->assign([
                'organizationEntityType'     => $customFields['klarna_customer_entity_type'],
                'organizationRegistrationId' => $customFields['klarna_customer_registration_id'],
            ]);
        }

        return $customer;
    }

    private function getBillingAddress(CustomerEntity $contextCustomer): ?CustomerAddressEntity
    {
        $billingAddress = $contextCustomer->getActiveBillingAddress();

        if ($billingAddress === null) {
            return null;
        }

        return $billingAddress;
    }

    private function getVatId(CustomerAddressEntity $billingAddress, CustomerEntity $customer): ?string
    {
        // Backwards compatibility for Shopware < 6.3.5.0
        if (method_exists($billingAddress, 'getVatId')) {
            return $billingAddress->getVatId();
        }

        /** @phpstan-ignore-next-line */
        $vatIds = $customer->getVatIds();

        if ($vatIds === null) {
            return null;
        }

        return array_shift($vatIds);
    }
}
