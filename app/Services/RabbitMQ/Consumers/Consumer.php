<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ\Consumers;

use Kunnu\RabbitMQ\RabbitMQIncomingMessage;

abstract class Consumer
{
    public abstract function consume(RabbitMQIncomingMessage $message): void;
}