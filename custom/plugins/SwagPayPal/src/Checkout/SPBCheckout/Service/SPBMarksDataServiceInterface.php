<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SPBCheckout\Service;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\SPBCheckout\SPBMarksData;

/**
 * @deprecated tag:v8.0.0 - will be removed without replacement, payment logos have been added natively
 */
#[Package('checkout')]
interface SPBMarksDataServiceInterface
{
    public function getSpbMarksData(SalesChannelContext $salesChannelContext): ?SPBMarksData;
}
