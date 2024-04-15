<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Utility\Exceptions;

use Exception;
use Sendcloud\Shipping\Core\Infrastructure\Utility\HttpResponse;

/**
 * Class HttpRequestException
 * @package Sendcloud\Shipping\Core\Infrastructure\Utility\Exceptions
 */
class HttpRequestException extends Exception
{
    /**
     * @var HttpResponse $response
     */
    private $response;
    /**
     * @var array $request
     */
    private $request;

    /**
     * @param string $message
     * @param int $code
     * @param null $previousException
     * @param HttpResponse|null $response
     * @param array $request
     */
    public function __construct($message = '', $code = 0, $previousException = null, HttpResponse $response = null, array $request = array())
    {
        parent::__construct($message, $code, $previousException);
        $this->response = $response;
        $this->request = $request;
    }

    /**
     * @return HttpResponse|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return array
     */
    public function getRequest()
    {
        return $this->request;
    }
}
