<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Logger;

use Sendcloud\Shipping\Core\BusinessLogic\Proxy;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\DefaultLoggerAdapter;
use Sendcloud\Shipping\Core\Infrastructure\Logger\LogData;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;

/**
 * Class DefaultLogger
 * @package Sendcloud\Shipping\Core\BusinessLogic\Logger
 */
class DefaultLogger implements DefaultLoggerAdapter
{
    /**
     * @var Proxy
     */
    private $proxy;

    /**
     * Sending log data to SendCloud API
     *
     * @param LogData $data
     */
    public function logMessage(LogData $data)
    {
        // Waiting on SendCloud to define API endpoint
        $this->getProxy()->createLog($data);
    }

    /**
     * Returns proxy instance
     *
     * @return Proxy
     */
    private function getProxy()
    {
        if (!$this->proxy) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }
}
