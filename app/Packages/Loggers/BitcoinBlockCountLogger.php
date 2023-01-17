<?php

namespace App\Packages\Loggers;

use Exception;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class BitcoinBlockCountLogger extends Logger
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        parent::__construct('BitcoinBlockCountLogger');
        $loggerFormat = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $loggerTimeFormat = "d.m.Y H:i:s";
        $level = config('catalog.bitcoin_block_count_log_level', 'info');
        $handler = new StreamHandler(storage_path('logs/blocks.log'), Logger::toMonologLevel($level));
        $handler->setFormatter(new LineFormatter($loggerFormat, $loggerTimeFormat, true, true));
        $this->pushHandler($handler);
    }
}
