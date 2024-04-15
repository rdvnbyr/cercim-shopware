<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\DTO;

/**
 * Class CarrierDTO
 * @package Sendcloud\Shipping\Core\BusinessLogic\DTO
 */
class CarrierDTO
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $code;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var int[]
     */
    private $senderAddresses;

    /**
     * CarrierDTO constructor.
     *
     * @param string $name
     * @param string $code
     * @param bool $enabled
     * @param int[] $senderAddresses
     */
    public function __construct($name, $code, $enabled, array $senderAddresses)
    {
        $this->name = $name;
        $this->code = $code;
        $this->enabled = $enabled;
        $this->senderAddresses = $senderAddresses;
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
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return int[]
     */
    public function getSenderAddresses()
    {
        return $this->senderAddresses;
    }

}
