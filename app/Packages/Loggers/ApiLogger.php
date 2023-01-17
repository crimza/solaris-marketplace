<?php

namespace App\Packages\Loggers;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ApiLogger extends Logger
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct('ApiLogger');
        $loggerFormat = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $loggerTimeFormat = "d.m.Y H:i:s";
        $level = config('catalog.api_log_level', 'info');
        $handler = new StreamHandler(storage_path('logs/api.log'), Logger::toMonologLevel($level));
        $handler->setFormatter(new LineFormatter($loggerFormat, $loggerTimeFormat, false, true));
        $this->pushHandler($handler);
    }
}
