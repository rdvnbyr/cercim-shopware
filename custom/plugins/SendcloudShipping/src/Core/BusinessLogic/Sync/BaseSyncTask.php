<?php

namespace Sendcloud\Shipping\Core\BusinessLogic\Sync;

use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Configuration;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\OrderService;
use Sendcloud\Shipping\Core\BusinessLogic\Interfaces\Proxy;
use Sendcloud\Shipping\Core\BusinessLogic\Services\ConnectService;
use Sendcloud\Shipping\Core\Infrastructure\ServiceRegister;
use Sendcloud\Shipping\Core\Infrastructure\TaskExecution\Task;

/**
 * Class BaseSyncTask
 * @package Sendcloud\Shipping\Core\BusinessLogic\Sync
 */
abstract class BaseSyncTask extends Task
{
    /**
     * @var Proxy
     */
    private $proxy;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var ConnectService
     */
    private $connectService;

    /**
     * Gets proxy class instance.
     *
     * @return \Sendcloud\Shipping\Core\BusinessLogic\Proxy
     */
    protected function getProxy()
    {
        if ($this->proxy === null) {
            $this->proxy = ServiceRegister::getService(Proxy::CLASS_NAME);
        }

        return $this->proxy;
    }

    /**
     * Gets order service class instance.
     *
     * @return \Sendcloud\Shipping\Core\BusinessLogic\Interfaces\OrderService
     */
    protected function getOrderService()
    {
        if ($this->orderService === null) {
            $this->orderService = ServiceRegister::getService(OrderService::CLASS_NAME);
        }

        return $this->orderService;
    }

    /**
     * Gets connect service class instance.
     *
     * @return \Sendcloud\Shipping\Core\BusinessLogic\Interfaces\ConnectService
     */
    protected function getConnectService()
    {
        if ($this->connectService === null) {
            $this->connectService = ServiceRegister::getService(ConnectService::CLASS_NAME);
        }

        return $this->connectService;
    }

    /**
     * Gets Configuration service.
     *
     * @return Configuration
     */
    protected function getConfigService()
    {
        /** @var Configuration $configService */
        $configService = parent::getConfigService();
        return $configService;
    }
}
