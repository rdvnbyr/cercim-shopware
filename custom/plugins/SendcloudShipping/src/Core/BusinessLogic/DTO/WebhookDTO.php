<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\DTO;

/**
 * Class WebhookDTO
 * @package Sendcloud\Shipping\Core\BusinessLogic\DTO
 */
class WebhookDTO
{
    /**
     * @var string
     */
    private $rawBody;
    /**
     * @var array
     */
    private $body;
    /**
     * @var string
     */
    private $hash;
    /**
     * @var string
     */
    private $context;
    /**
     * @var string
     */
    private $token;

    /**
     * WebHookDTO constructor.
     *
     * @param string $rawBody
     * @param string $hash
     * @param string $token
     * @param string $context
     */
    public function __construct($rawBody, $hash, $token, $context = '')
    {
        $this->rawBody = $rawBody;
        $this->context = $context;
        $this->hash = $hash;
        $this->token = $token;
        $this->body = json_decode($rawBody, true);
    }

    /**
     * @return string
     */
    public function getRawBody()
    {
        return $this->rawBody;
    }

    /**
     * @return array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

}
