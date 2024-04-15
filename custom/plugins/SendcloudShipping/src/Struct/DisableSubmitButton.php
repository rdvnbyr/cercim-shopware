<?php

namespace Sendcloud\Shipping\Struct;

use Shopware\Core\Framework\Struct\Struct;

/**
 * Class DisableSubmitButton
 *
 * @package Sendcloud\Shipping\Service\Utility\Struct
 */
class DisableSubmitButton extends Struct
{
    /**
     * @var bool
     */
    public $disableSubmitButton;

    /**
     * DisableSubmitButton constructor.
     *
     * @param $disableSubmitButton
     */
    public function __construct(bool $disableSubmitButton)
    {
        $this->disableSubmitButton = $disableSubmitButton;
    }
}
