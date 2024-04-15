<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Interfaces;

/**
 * Interface Proxy
 * @package Sendcloud\Shipping\Core\BusinessLogic\Interfaces
 */
interface Proxy
{
    const CLASS_NAME = __CLASS__;

    /**
     * Call http client
     *
     * @param string $method
     * @param string $endpoint
     * @param array $body
     * @param string $publicKey
     * @param string $secretKey
     *
     * @return array
     */
    public function call($method, $endpoint, array $body = array(), $publicKey = '', $secretKey = '');
}
