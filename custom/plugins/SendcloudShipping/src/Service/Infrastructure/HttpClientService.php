<?php

namespace Sendcloud\Shipping\Service\Infrastructure;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\HttpClient;
use Sendcloud\Shipping\Core\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use Sendcloud\Shipping\Core\Infrastructure\Utility\Exceptions\HttpRequestException;
use Sendcloud\Shipping\Core\Infrastructure\Utility\HttpResponse;
use Sendcloud\Shipping\Service\Business\ConfigurationService;

/**
 * Class HttpClientService
 *
 * @package Sendcloud\Shipping\Service\Infrastructure
 */
class HttpClientService extends HttpClient
{

    /**
     * @var ConfigurationService
     */
    private $configService;
    /**
     * @var resource
     */
    protected $curlSession;

    /**
     * HttpClientService constructor.
     *
     * @param Configuration $configService
     */
    public function __construct(Configuration $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Create and send request
     *
     * @param string $method RESTful method (GET, POST, PUT, DELETE)
     * @param string $url address of endpoint
     * @param array $headers HTTP header
     * @param string $body In JSON format
     *
     * @return HttpResponse
     *
     * @throws HttpCommunicationException Only in situation when there is no connection, no response, throw this
     * @throws HttpRequestException
     *     exception
     */
    public function sendHttpRequest($method, $url, $headers = array(), $body = ''): HttpResponse
    {
        $this->setCurlSessionAndCommonRequestParts($method, $url, $headers, $body);
        $this->setCurlSessionOptionsForSynchronousRequest();

        return $this->executeAndReturnResponseForSynchronousRequest($url);
    }

    /**
     * Creates and send request asynchronously
     *
     * @param string $method RESTful method (GET, POST, PUT, DELETE)
     * @param string $url address of endpoint
     * @param array $headers HTTP header
     * @param string $body In JSON format
     *
     * @throws HttpRequestException
     */
    public function sendHttpRequestAsync($method, $url, $headers = array(), $body = ''): void
    {
        $this->setCurlSessionAndCommonRequestParts($method, $url, $headers, $body);
        $this->setCurlSessionOptionsForAsynchronousRequest();

        curl_exec($this->curlSession);
    }

    /**
     * Creates curl session and sets common request parts (method, headers and body)
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     *
     * @throws HttpRequestException
     */
    private function setCurlSessionAndCommonRequestParts(string $method, string $url, array $headers, string $body): void
    {
        $this->initializeCurlSession();
        $this->setCurlSessionOptionsBasedOnMethod($method);
        $this->setCurlSessionUrlHeadersAndBody($url, $headers, $body);
        $this->setCommonOptionsForCurlSession();
    }

    /**
     * Initializes curl session
     * @throws HttpRequestException
     */
    private function initializeCurlSession(): void
    {
        $this->curlSession = curl_init();
        if ($this->curlSession === false) {
            throw new HttpRequestException('Curl failed to initialize session');
        }
    }

    /**
     * Sets curl options based on method name
     *
     * @param string $method
     */
    private function setCurlSessionOptionsBasedOnMethod(string $method): void
    {
        if ($method === 'DELETE') {
            curl_setopt($this->curlSession, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if ($method === 'POST') {
            curl_setopt($this->curlSession, CURLOPT_POST, true);
        }

        if ($method === 'PUT') {
            curl_setopt($this->curlSession, CURLOPT_CUSTOMREQUEST, 'PUT');
        }
    }

    /**
     * Sets curl headers and body
     *
     * @param string $url
     * @param array $headers
     * @param string $body
     */
    private function setCurlSessionUrlHeadersAndBody(string $url, array $headers, string $body): void
    {
        curl_setopt($this->curlSession, CURLOPT_URL, $url);
        curl_setopt($this->curlSession, CURLOPT_HTTPHEADER, $headers);
        if (!empty($body)) {
            curl_setopt($this->curlSession, CURLOPT_POSTFIELDS, $body);
        }
    }

    /**
     * Sets common curl options
     */
    private function setCommonOptionsForCurlSession(): void
    {
        curl_setopt($this->curlSession, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curlSession, CURLOPT_FOLLOWLOCATION, true);
    }

    /**
     * Sets curl options for synchronous request
     */
    private function setCurlSessionOptionsForSynchronousRequest(): void
    {
        curl_setopt($this->curlSession, CURLOPT_HEADER, true);
    }

    /**
     * Sets curl options for async request
     */
    private function setCurlSessionOptionsForAsynchronousRequest(): void
    {
        $timeout = $this->configService->getAsyncRequestTimeout();
        // Always ensure the connection is fresh
        curl_setopt($this->curlSession, CURLOPT_FRESH_CONNECT, true);
        // Timeout super fast once connected, so it goes into async
        curl_setopt($this->curlSession, CURLOPT_TIMEOUT_MS, $timeout);
    }

    /**
     * Executes curl and returns response as HttpResponse object
     *
     * @param string $url
     *
     * @return HttpResponse
     * @throws HttpCommunicationException
     */
    protected function executeAndReturnResponseForSynchronousRequest(string $url): HttpResponse
    {
        $apiResponse = curl_exec($this->curlSession);
        if ($apiResponse === false) {
            throw new HttpCommunicationException(
                'Request ' . $url . ' failed. ' . curl_error($this->curlSession), curl_errno($this->curlSession)
            );
        }

        $statusCode = curl_getinfo($this->curlSession, CURLINFO_HTTP_CODE);
        curl_close($this->curlSession);


        $apiResponse = $this->strip100Header($apiResponse);

        return new HttpResponse(
            $statusCode,
            $this->getHeadersFromCurlResponse($apiResponse),
            $this->getBodyFromCurlResponse($apiResponse)
        );
    }

    /**
     * Removes HTTP/1.1 100 if exist in api response
     *
     * @param string $response
     *
     * @return string
     */
    protected function strip100Header(string $response): string
    {
        $delimiter = "\r\n\r\n";
        $needle = 'HTTP/1.1 100';
        if (strpos($response, $needle) === 0) {
            return substr($response, strpos($response, $delimiter) + 4);
        }

        return $response;
    }

    /**
     * Returns header from api response
     *
     * @param string $response
     *
     * @return array
     */
    protected function getHeadersFromCurlResponse(string $response): array
    {
        $headers = [];
        $headersBodyDelimiter = "\r\n\r\n";
        $headerText = substr($response, 0, strpos($response, $headersBodyDelimiter));
        $headersDelimiter = "\r\n";

        foreach (explode($headersDelimiter, $headerText) as $i => $line) {
            if ($i === 0) {
                $headers[] = $line;
            } else {
                [$key, $value] = explode(': ', $line);
                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    /**
     * Returns body from api response
     *
     * @param string $response
     *
     * @return string
     */
    protected function getBodyFromCurlResponse(string $response): string
    {
        $headersBodyDelimiter = "\r\n\r\n";
        $bodyStartingPositionOffset = 4; // number of special signs in delimiter;

        return substr(
            $response,
            strpos($response, $headersBodyDelimiter) + $bodyStartingPositionOffset
        );
    }

}