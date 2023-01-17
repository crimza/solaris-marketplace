<?php

namespace App\Console\Commands;

use App\Services\RabbitMQ\Consumers\DisputesConsumer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Kunnu\RabbitMQ\RabbitMQExchange;
use Kunnu\RabbitMQ\RabbitMQGenericMessageConsumer;
use Kunnu\RabbitMQ\RabbitMQIncomingMessage;
use Kunnu\RabbitMQ\RabbitMQQueue;

class RabbitConsumer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rabbitmq:consumer {--queue=} {--exchange=} {--routingKey=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consumer command';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $routingKey = $this->option('routingKey') ?? '';
        $queue = new RabbitMQQueue($this->option('queue') ?? '', ['declare' => true]);
        $exchange = new RabbitMQExchange($this->option('exchange') ?? '', ['declare' => true]);

        $rabbitMQ = app('rabbitmq');
        $messageConsumer = new RabbitMQGenericMessageConsumer(
            function (RabbitMQIncomingMessage $message) use ($routingKey) {
                $class = 'App\Services\RabbitMQ\Consumers\\' . Str::studly($routingKey) . 'Consumer';
                if(class_exists($class)) {
                    $consumer = new $class();
                    $consumer->consume($message);
                }
            },
            $this,
        );

        $messageConsumer
            ->setExchange($exchange)
            ->setQueue($queue);

        $rabbitMQ->consumer()->consume($messageConsumer, $routingKey);
    }
}
