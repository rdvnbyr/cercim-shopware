<?php


namespace Sendcloud\Shipping\Service\Infrastructure;

use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\Configuration;
use Sendcloud\Shipping\Core\Infrastructure\Interfaces\Required\ShopLoggerAdapter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as MonologLogger;
use Sendcloud\Shipping\Core\Infrastructure\Logger\LogData;
use Sendcloud\Shipping\Core\Infrastructure\Logger\Logger;
use Shopware\Core\Kernel;
/**
 * Class LoggerService
 *
 * @package Sendcloud\Shipping\Service\Infrastructure
 */
class LoggerService implements ShopLoggerAdapter
{
    /**
     * @var Kernel
     */
    private $kernel;
    /**
     * @var Configuration
     */
    private $configService;

    /**
     * LoggerService constructor.
     *
     * @param Kernel $kernel
     * @param Configuration $configService
     */
    public function __construct(Kernel $kernel, Configuration $configService)
    {
        $this->kernel = $kernel;
        $this->configService = $configService;
    }

    /**
     * Log message in the system.
     *
     * @param LogData|null $data Log data object.
     */
    public function logMessage($data): void
    {
        $logLevel = $data->getLogLevel();
        if ($logLevel > $this->configService->getMinLogLevel()) {
            return;
        }

        $logger = $this->getSystemLogger();
        $message = "[{$data->getComponent()}] {$data->getMessage()}";
        switch ($logLevel) {
            case Logger::ERROR:
                $logger->error($message);
                break;
            case Logger::WARNING:
                $logger->warning($message);
                break;
            case Logger::DEBUG:
                $logger->debug($message);
                break;
            default:
                $logger->info($message);
        }

    }

    /**
     * Returns system logger with predefined log directory and log file
     *
     * @return MonologLogger
     */
    private function getSystemLogger(): MonologLogger
    {
        $logger = new MonologLogger('sendcloud');
        $logFile = $this->kernel->getLogDir() . '/sendcloud/sendcloud.log';
        $logger->pushHandler(new RotatingFileHandler($logFile));

        return $logger;
    }
}