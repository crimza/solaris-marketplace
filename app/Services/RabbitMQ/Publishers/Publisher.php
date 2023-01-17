<?php

namespace App\Services\RabbitMQ\Publishers;

use Kunnu\RabbitMQ\RabbitMQ;
use Kunnu\RabbitMQ\RabbitMQExchange;
use Kunnu\RabbitMQ\RabbitMQManager;
use Kunnu\RabbitMQ\RabbitMQMessage;

abstract class Publisher
{
    protected string $exchanger = 'catalog';

    protected string $queue = '';

    protected RabbitMQManager $rabbitMQ;

     public function __construct(RabbitMQManager $rabbitMQ)
     {
         $this->rabbitMQ = $rabbitMQ;
     }

    public function publish(array $message)
    {
        $publisher = $this->rabbitMQ->publisher();
        $message = new RabbitMQMessage(json_encode($message));
        $exchange = new RabbitMQExchange($this->exchanger);
        $message->setExchange($exchange);
        $publisher->publish($message, $this->queue);
    }
}