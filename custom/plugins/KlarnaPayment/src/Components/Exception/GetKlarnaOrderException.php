<?php

declare(strict_types=1);

namespace KlarnaPayment\Components\Exception;

class GetKlarnaOrderException extends \Exception
{
    /** @var string */
    private $errorCode;

    /** @var array<string,mixed> */
    private $response;

    /**
     * @param array<string,mixed> $response
     */
    public function __construct(string $errorCode, array $response)
    {
        $this->errorCode = $errorCode;
        $this->response  = $response;

        parent::__construct('An error occurred while requesting the order from Klarna');
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string,mixed>
     */
    public function getResponse(): array
    {
        return $this->response;
    }
}
