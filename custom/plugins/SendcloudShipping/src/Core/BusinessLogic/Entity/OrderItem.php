<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Entity;

/**
 * Class OrderItem
 * @package Sendcloud\Shipping\Core\BusinessLogic\Entity
 */
class OrderItem
{
    /**
     * Internal ID of the product
     *
     * @var string
     */
    private $productId;
    /**
     * Product SKU
     *
     * @var string
     */
    private $sku;
    /**
     * Product description
     *
     * @var string
     */
    private $description;
    /**
     * Country two letter ISO code
     *
     * @var string
     */
    private $originCountry = '';
    /**
     * Harmonized System Code
     *
     * @var string
     */
    private $hsCode = '';
    /**
     * Quantity of items shipped
     *
     * @var int
     */
    private $quantity;
    /**
     * Price value of each one of the items
     *
     * @var float
     */
    private $value;
    /**
     * Weight of each one of the items
     *
     * @var float
     */
    private $weight;
    /**
     * The list of properties of the product in key => value manner
     *
     * @var array
     */
    private $properties = array();

    /**
     * @return string
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
    }

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->sku;
    }

    /**
     * @param string $sku
     */
    public function setSku($sku)
    {
        $this->sku = $sku;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getOriginCountry()
    {
        return $this->originCountry;
    }

    /**
     * @param string $originCountry
     */
    public function setOriginCountry($originCountry)
    {
        $this->originCountry = $originCountry;
    }

    /**
     * @return string
     */
    public function getHsCode()
    {
        return $this->hsCode;
    }

    /**
     * @param string $hsCode
     */
    public function setHsCode($hsCode)
    {
        $this->hsCode = $hsCode;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return float
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @param float $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties($properties)
    {
        $this->properties = $properties;
    }
}
