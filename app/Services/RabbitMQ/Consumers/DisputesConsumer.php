<?php

namespace App\Services\RabbitMQ\Consumers;

use App\Dispute;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Kunnu\RabbitMQ\RabbitMQIncomingMessage;

class DisputesConsumer extends Consumer
{
    public function consume(RabbitMQIncomingMessage $message): void
    {
        $data = json_decode($message->getStream(), true);

        try {
            $dispute = Dispute::where('dispute_id', $data['id'])->where('shop_url', $data['url'])->get();

            if ($dispute->count() > 0) {
                Dispute::where('dispute_id', $data['id'])
                    ->where('app_id', $data['app_id'])
                    ->update([
                        'dispute_id' => $data['id'],
                        'shop_url' => $data['url'],
                        'app_id' => $data['app_id'],
                        'creator' => $data['creator'],
                        'status' => $data['status'],
                        'decision' => $data['decision'],
                        'moderator' => $data['moderator'],
                        'dispute_updated_at' => Carbon::createFromTimestamp($data['updated_at']),
                    ]);
            } else {
                Dispute::create([
                    'dispute_id' => $data['id'],
                    'app_id' => $data['app_id'],
                    'shop_url' => $data['url'],
                    'creator' => $data['creator'],
                    'status' => $data['status'],
                    'decision' => $data['decision'],
                    'moderator' => $data['moderator'],
                    'dispute_updated_at' => Carbon::createFromTimestamp($data['updated_at']),
                ]);
            }
            $message->getDelivery()->acknowledge();
        } catch (Exception $e) {
            $message->getDelivery()->reject(true);
            Log::error($e->getMessage());
            Log::error($message->getStream());
        }
    }
}