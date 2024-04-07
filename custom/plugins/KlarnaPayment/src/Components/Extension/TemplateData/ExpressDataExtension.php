<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Extension\TemplateData;

use Shopware\Core\Framework\Struct\Struct;

class ExpressDataExtension extends Struct
{
    public const EXTENSION_NAME = 'klarna_express_data';

    /** @var string */
    protected $merchantId;

    /** @var string */
    protected $environment;

    /** @var string */
    protected $locale;

    /** @var string */
    protected $theme;

    /** @var string */
    protected $label;

    /** @var string */
    protected $cssClassName;

    /** @var string */
    protected $shape;

    public function __construct(
        string $merchantId,
        string $environment,
        string $locale,
        string $theme,
        string $label,
        string $cssClassName,
        string $shape
    ) {
        $this->merchantId   = $merchantId;
        $this->environment  = $environment;
        $this->locale       = $locale;
        $this->theme        = $theme;
        $this->label        = $label;
        $this->cssClassName = $cssClassName;
        $this->shape        = $shape;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function getTheme(): string
    {
        return $this->theme;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCssClassName(): string
    {
        return $this->cssClassName;
    }

    public function getShape(): string
    {
        return $this->shape;
    }
}
