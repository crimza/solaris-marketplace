<?php

namespace App\Services\RabbitMQ\Consumers;

use App\AdvStatsCache;
use Exception;
use Illuminate\Support\Facades\Log;
use Kunnu\RabbitMQ\RabbitMQIncomingMessage;

class StatisticConsumer extends Consumer
{
    public function consume(RabbitMQIncomingMessage $message): void
    {
        try {
            $data = json_decode($message->getStream(), true);
            AdvStatsCache::add($data);
            $message->getDelivery()->acknowledge();
        } catch (Exception $e) {
            $message->getDelivery()->reject(true);
            Log::error($e->getMessage());
            Log::error($message->getStream());
        }
    }
}