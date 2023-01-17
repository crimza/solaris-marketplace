<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ\Consumers;

use App\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Kunnu\RabbitMQ\RabbitMQException;
use Kunnu\RabbitMQ\RabbitMQIncomingMessage;
use Throwable;

class ShopEventsConsumer extends Consumer
{
    /**
     * @param RabbitMQIncomingMessage $message Сообщение из очереди.
     *
     * @throws RabbitMQException
     */
    public function consume(RabbitMQIncomingMessage $message): void
    {
        try {
            $shop = json_decode($message->getStream(), true);
            $shopModel = Shop::whereAppId($shop['app_id'])->first();

            if (!$shopModel) {
                $shopModel = new Shop();
                $shopModel->app_id = $shop['app_id'];
                $shopModel->app_key = $shop['app_key'];
            }

            if ($shopModel->app_key !== $shop['app_key']) {
                $message->getDelivery()->acknowledge();
                return;
            }

            $shopModel->url = $shop['url'];
            $shopModel->title = $shop['title'];
            $appPort = array_key_exists('app_port', $shop) ? intval($shop['app_port']) : 0;

            if (array_key_exists('local_ip', $shop)
                && filter_var($shop['local_ip'], FILTER_VALIDATE_IP)
                && $appPort > 0
            ) {
                $shopModel->gate_lan_ip = $shop['local_ip'];
                $shopModel->gate_lan_port = intval($shop['app_port']);
                $shopModel->gate_enabled = $shop['gate_enabled'];
            }

            if ($shopModel->image_url !== $shop['image_url']) {
                $shopModel->image_url = $shop['image_url'];
                $shopModel->image_url_local = null;
                $shopModel->image_cached = false;
            }

            $shopModel->plan = (isset($shop['plan'])) ? $shop['plan'] : null;
            $shopModel->users_count = $shop['users_count'];
            $shopModel->orders_count = $shop['orders_count'];
            $shopModel->rating = $shop['rating'];
            $shopModel->bitcoin_connections = $shop['bitcoin_connections'];
            $shopModel->bitcoin_block_count = $shop['bitcoin_block_count'];
            $shopModel->expires_at = Carbon::createFromTimestamp($shop['expires_at']);
            $shopModel->last_sync_at = Carbon::createFromTimestamp(time());
            $shopModel->save();

            $message->getDelivery()->acknowledge();
        } catch (Throwable $exception) {
            $message->getDelivery()->reject(true);

            Log::error($exception->getMessage());
            Log::error($message->getStream());
        }
    }
}
