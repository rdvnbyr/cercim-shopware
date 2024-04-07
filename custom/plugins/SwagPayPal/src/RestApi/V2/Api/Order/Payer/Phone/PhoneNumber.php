<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\Payer\Phone;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Common\PhoneNumber as CommonPhoneNumber;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payer_phone")
 */
#[Package('checkout')]
class PhoneNumber extends CommonPhoneNumber
{
}
