<?php
namespace App\Packages\Loggers;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class ImageFetcherLogger extends Logger
{
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct('ImageFetcherLogger');
        $loggerFormat = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $loggerTimeFormat = "d.m.Y H:i:s";
        $level = config('catalog.img_fetcher_log_level', 'info');
        $handler = new StreamHandler(storage_path() . '/logs/image_fetch.log', Logger::toMonologLevel($level));
        $handler->setFormatter(new LineFormatter($loggerFormat, $loggerTimeFormat, true, true));
        $this->pushHandler($handler);
    }
}