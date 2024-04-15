<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Logger;

/**
 * Class LogContextData
 * @package Sendcloud\Shipping\Core\Infrastructure\Logger
 */
class LogContextData
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
     * LogContextData constructor.
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
