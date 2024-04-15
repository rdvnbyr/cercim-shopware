<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\DTO\SendcloudExceptionLogs;

/**
 * Class ExceptionLog
 * @package Sendcloud\Shipping\Core\BusinessLogic\DTO\SendcloudExceptionLogs
 */
class ExceptionLog
{
    /**
     * @var string
     */
    private $exception;
    /**
     * @var string|null
     */
    private $exceptionType;
    /**
     * @var string|null
     */
    private $method;
    /**
     * @var int|null
     */
    private $responseCode;
    /**
     * @var ExceptionLogRequest
     */
    private $request;
    /**
     * @var ExceptionLogResponse
     */
    private $response;
    /**
     * @var string|null
     */
    private $baseUrl;
    /**
     * @var string|null
     */
    private $fullUrl;
    /**
     * @var string
     */
    private $createdAt;

    /**
     * ExceptionLog constructor
     *
     * @param string $exception
     * @param string|null $exceptionType
     * @param string|null $method
     * @param int|null $responseCode
     * @param ExceptionLogRequest $request
     * @param ExceptionLogResponse $response
     * @param string|null $baseUrl
     * @param string|null $fullUrl
     * @param string $createdAt
     */
    public function __construct($exception, $exceptionType, $method, $responseCode, ExceptionLogRequest $request,
                                ExceptionLogResponse $response, $baseUrl, $fullUrl, $createdAt)
    {
        $this->exception = $exception;
        $this->exceptionType = $exceptionType;
        $this->method = $method;
        $this->responseCode = $responseCode;
        $this->request = $request;
        $this->response = $response;
        $this->baseUrl = $baseUrl;
        $this->fullUrl = $fullUrl;
        $this->createdAt = $createdAt;
    }

    /**
     * @return string
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @return string|null
     */
    public function getExceptionType()
    {
        return $this->exceptionType;
    }

    /**
     * @return string|null
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return int|null
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @return ExceptionLogRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ExceptionLogResponse
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return string|null
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string|null
     */
    public function getFullUrl()
    {
        return $this->fullUrl;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
