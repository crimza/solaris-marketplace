<?php

namespace App\Packages\Loggers;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class UpdateRatesLogger extends Logger
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct('UpdateRatesLogger');
        $loggerFormat = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $loggerTimeFormat = "d.m.Y H:i:s";
        $level = config('catalog.update_rates_log_level', 'info');
        $handler = new StreamHandler(storage_path('logs/rates.log'), Logger::toMonologLevel($level));
        $handler->setFormatter(new LineFormatter($loggerFormat, $loggerTimeFormat, true, true));
        $this->pushHandler($handler);
    }
}
