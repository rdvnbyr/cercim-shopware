<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\DTO\SendcloudExceptionLogs;

/**
 * Class ExceptionLogRequest
 * @package Sendcloud\Shipping\Core\BusinessLogic\DTO\SendcloudExceptionLogs
 */
class ExceptionLogRequest
{
    /**
     * @var array
     */
    private $headers;
    /**
     * @var string
     */
    private $body;

    /**
     * @param array $headers
     * @param string $body
     */
    public function __construct(array $headers, $body)
    {
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'headers' => $this->getHeaders(),
            'payload' => $this->getBody()
        );
    }
}
