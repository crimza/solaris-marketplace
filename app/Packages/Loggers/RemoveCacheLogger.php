<?php

namespace App\Packages\Loggers;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class RemoveCacheLogger extends Logger
{
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct('RemoveCacheLogger');
        $loggerFormat = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $loggerTimeFormat = "d.m.Y H:i:s";
        $level = config('catalog.reset_cache_log_level', 'info');
        $handler = new StreamHandler(storage_path() . '/logs/cache_remove.log', Logger::toMonologLevel($level));
        $handler->setFormatter(new LineFormatter($loggerFormat, $loggerTimeFormat, false, true));
        $this->pushHandler($handler);
    }
}