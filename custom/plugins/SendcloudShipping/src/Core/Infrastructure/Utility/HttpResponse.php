<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Utility;

/**
 * Class HttpResponse
 * @package Sendcloud\Shipping\Core\Infrastructure\Utility
 */
class HttpResponse
{
    const CLASS_NAME = __CLASS__;

    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $headers;

    /**
     * HttpResponse constructor.
     *
     * @param int $status
     * @param array $headers
     * @param string $body
     */
    public function __construct($status, $headers, $body)
    {
        $this->status = $status;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Return response status
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Return response body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Return response headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Verifies HTTP status code, returns TRUE if in success range [200, 300), FALSE otherwise
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return isset($this->status) && $this->getStatus() >= 200 && $this->getStatus() < 300;
    }
}
