<?php
namespace App\Packages\Loggers;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class TicketImagesCleanLogger extends Logger
{
    /**
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct('TicketImagesCleanLogger');
        $loggerFormat = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $loggerTimeFormat = "d.m.Y H:i:s";
        $level = config('catalog.ticket_images_log_level', 'info');
        $handler = new StreamHandler(storage_path() . '/logs/ticket_images_cleanup.log', Logger::toMonologLevel($level));
        $handler->setFormatter(new LineFormatter($loggerFormat, $loggerTimeFormat, true, true));
        $this->pushHandler($handler);
    }
}