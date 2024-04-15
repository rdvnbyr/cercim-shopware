<?php

namespace Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required;

use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Sendcloud\Shipping\Core\Infrastructure\Utility\Exceptions\HttpCommunicationException;
use Sendcloud\Shipping\Core\Infrastructure\Utility\HttpResponse;

/**
 * Class HttpClient
 * @package Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required
 */
abstract class HttpClient
{
    const CLASS_NAME = __CLASS__;

    /**
     * Create, log and send request
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     *
     * @return HttpResponse
     * @throws HttpCommunicationException
     */
    public function request($method, $url, $headers = array(), $body = '')
    {
        Logger::logDebug(
            "Sending http request to $url",
            'Core',
            array(
                'Type' => $method,
                'Endpoint' => $url,
                'Headers' => json_encode($headers),
                'Content' => $body,
            )
        );

        /** @var HttpResponse $response */
        $response = $this->sendHttpRequest($method, $url, $headers, $body);

        Logger::logDebug(
            "Http response from $url",
            'Core',
            array(
                'ResponseFor' => "{$method} at {$url}",
                'Status' => $response->getStatus(),
                'Headers' => json_encode($response->getHeaders()),
                'Content' => $response->getBody(),
            )

        );

        return $response;
    }

    /**
     * Create, log and send request asynchronously
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     */
    public function requestAsync($method, $url, $headers = array(), $body = '')
    {
        Logger::logDebug(
            "Sending async http request to $url",
            'Core',
            array(
                'Type' => $method,
                'Endpoint' => $url,
                'Headers' => json_encode($headers),
                'Content' => $body,
            )
        );

        $this->sendHttpRequestAsync($method, $url, $headers, $body);
    }

    /**
     * Auto configures http call options
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     *
     * @return bool
     */
    public function autoConfigure($method, $url, $headers = array(), $body = '')
    {
        $passed = $this->isRequestSuccessful($method, $url, $headers, $body);
        if ($passed) {
            return true;
        }

        $combinations = $this->getAdditionalOptions();
        foreach ($combinations as $combination) {
            $this->setAdditionalOptions($combination);
            $passed = $this->isRequestSuccessful($method, $url, $headers, $body);
            if ($passed) {
                return true;
            }

            $this->resetAdditionalOptions();
        }

        return false;
    }

    /**
     * Create and send request
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body In JSON format
     *
     * @return HttpResponse
     * @throws HttpCommunicationException Only in situation when there is no connection, no response, throw this
     *     exception
     */
    abstract protected function sendHttpRequest($method, $url, $headers = array(), $body = '');

    /**
     * Create and send request asynchronously
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body In JSON format
     */
    abstract protected function sendHttpRequestAsync($method, $url, $headers = array(), $body = '');

    /**
     * Get additional options for request
     *
     * @return array
     */
    protected function getAdditionalOptions()
    {
        // Left blank intentionally so integrations can override this method,
        // in order to return all possible combinations for additional curl options
        return array();
    }

    /**
     * Save additional options for request
     *
     * @param array $options
     */
    protected function setAdditionalOptions($options)
    {
        // Left blank intentionally so integrations can override this method,
        // in order to save combination to some persisted array which `HttpClient` can use it later while creating request
    }

    /**
     * Reset additional options for request to default value
     */
    protected function resetAdditionalOptions()
    {
        // Left blank intentionally so integrations can override this method,
        // in order to reset to its default values persisted array which `HttpClient` uses later while creating request
    }

    /**
     * Verifies the response and returns TRUE if valid, FALSE otherwise
     *
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param string $body
     *
     * @return bool
     */
    private function isRequestSuccessful($method, $url, $headers = array(), $body = '')
    {
        try {
            /** @var HttpResponse $response */
            $response = $this->request($method, $url, $headers, $body);
        } catch (HttpCommunicationException $ex) {
            $response = null;
        }

        if (isset($response) && $response->isSuccessful()) {
            return true;
        }

        return false;
    }
}
