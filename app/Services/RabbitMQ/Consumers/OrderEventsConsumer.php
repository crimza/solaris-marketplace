<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ\Consumers;

use App\Events\OrderFinished;
use App\Good;
use App\Order;
use App\OrderPosition;
use App\OrderReview;
use App\Shop;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Kunnu\RabbitMQ\RabbitMQException;
use Kunnu\RabbitMQ\RabbitMQIncomingMessage;
use Throwable;

class OrderEventsConsumer extends Consumer
{
    /**
     * @param RabbitMQIncomingMessage $message Сообщение из очереди.
     *
     * @throws RabbitMQException
     */
    public function consume(RabbitMQIncomingMessage $message): void
    {
        try {
            $order = json_decode($message->getStream(), true);
            $shopModel = Shop::whereAppId($order['app_id'])->first();
            $user = User::whereUsername($order['user'])->first();

            if (!$user || !$shopModel) {
                $message->getDelivery()->acknowledge();
                return;
            }

            $orderModel = $shopModel->orders()->where('app_order_id', $order['id'])->first();
            if (!$orderModel) {
                $orderModel = new Order();
                $orderModel->app_id = $order['app_id'];
                $orderModel->app_order_id = $order['id'];
                $orderModel->user_id = $user->id;
            }

            $orderModel->city_id = $order['city_id'];
            $orderModel->app_good_id = $order['good_id'];
            $orderModel->good_title = $order['good_title'];

            if (!$orderModel->good_id) {
                /** @var Good $good */
                $good = $shopModel->goods()->where('app_good_id', $order['good_id'])->first();
                if ($good) {
                    $orderModel->good_id = $good->id;
                }
            }

            if ($orderModel->good_image_url !== $order['good_image_url']) {
                $orderModel->good_image_url = $order['good_image_url'];
                $orderModel->good_image_url_local = null;
                $orderModel->good_image_cached = false;
            }

            $orderModel->package_amount = $order['package_amount'];
            $orderModel->package_measure = $order['package_measure'];
            $orderModel->package_price = $order['package_price'];
            $orderModel->package_currency = $order['package_currency'];
            $orderModel->package_preorder = $order['package_preorder'];
            $orderModel->package_preorder_time = $order['package_preorder_time'];
            $orderModel->status = $order['status'];
            $orderModel->comment = $order['comment'];
            $orderModel->app_created_at = Carbon::createFromTimestamp($order['created_at']);
            $orderModel->app_updated_at = Carbon::createFromTimestamp($order['updated_at']);
            $orderModel->last_sync_at = Carbon::createFromTimestamp(time());
            $orderModel->save();

            if ($order['position']) {
                $orderPosition = $orderModel->position;

                if (!$orderPosition) {
                    $orderPosition = new OrderPosition();
                    $orderPosition->order_id = $orderModel->id;
                }

                $orderPosition->quest = $order['position']['quest'];
                $orderPosition->save();
                $orderModel->position_id = $orderPosition->id;
            }

            if ($order['review']) {
                $orderReview = $orderModel->review;
                if (!$orderReview) {
                    $orderReview = new OrderReview();
                    $orderReview->app_id = $order['app_id'];
                    $orderReview->good_id = $orderModel->good_id;
                    $orderReview->user_id = $orderModel->user_id;
                    $orderReview->order_id = $orderModel->id;
                    $orderReview->app_good_id = $orderModel->app_good_id;
                    $orderReview->app_order_id = $orderModel->app_order_id;
                }

                $orderReview->text = $order['review']['text'];
                $orderReview->shop_rating = $order['review']['shop_rating'];
                $orderReview->dropman_rating = $order['review']['dropman_rating'];
                $orderReview->item_rating = $order['review']['item_rating'];
                $orderReview->reply_text = $order['review']['reply_text'];
                $orderReview->created_at = Carbon::createFromTimestamp($order['review']['created_at']);
                $orderReview->save();

                $orderModel->review_id = $orderReview->id;
            }

            $orderModel->save();

            if ($orderModel->status === Order::STATUS_FINISHED) {
                event(new OrderFinished($orderModel));
            }

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
