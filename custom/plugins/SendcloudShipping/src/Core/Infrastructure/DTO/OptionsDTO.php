<?php

namespace Sendcloud\Shipping\Core\Infrastructure\DTO;

/**
 * Class OptionsDTO
 * @package Sendcloud\Shipping\Core\Infrastructure\DTO
 */
class OptionsDTO
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $value;

    /**
     * OptionsDTO constructor.
     *
     * @param string $name
     * @param string $value
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
}
