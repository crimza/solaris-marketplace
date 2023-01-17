<?php

namespace App\Packages\Loggers;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class CryptoPaymentPlatformLogger extends Logger
{
    public function __construct()
    {
        parent::__construct('CryptoPaymentPlatformLogger');
        $handler = new StreamHandler(storage_path() . '/logs/cpp.log', Logger::toMonologLevel(config('app.log_level')));
        $handler->setFormatter(new LineFormatter(null, null, false, true));
        $this->pushHandler($handler);
    }
}