<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\Payer;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Common\Name as CommonName;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payer_name")
 */
#[Package('checkout')]
class Name extends CommonName
{
}
