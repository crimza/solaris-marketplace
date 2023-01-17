<?php

namespace App\Services\RabbitMQ\Consumers;

use App\Events\OrderFinished;
use App\Good;
use App\GoodsCity;
use App\GoodsPackage;
use App\GoodsPosition;
use App\Order;
use App\OrderPosition;
use App\OrderReview;
use App\Shop;
use App\User;
use Carbon\Carbon;
use Exception;
use Kunnu\RabbitMQ\RabbitMQIncomingMessage;

class ShopSyncConsumer extends Consumer
{
    public function consume(RabbitMQIncomingMessage $message): void
    {
        $data = json_decode($message->getStream(), true);

        try {
            $shop = $data['shop'];

            $shopModel = Shop::whereAppId($shop['app_id'])->first();
            if (!$shopModel) {
                $shopModel = new Shop();
                $shopModel->app_id = $shop['app_id'];
                $shopModel->app_key = $shop['app_key'];
            }

            if ($shopModel->app_key !== $shop['app_key']) {
                return;
            }

            $shopModel->url = $shop['url'];
            $shopModel->title = $shop['title'];
            $appPort = array_key_exists('app_port', $shop) ? intval($shop['app_port']) : 0; // var_dump says it's string

            // enable gate if lan ip and port are valid
            if (array_key_exists('local_ip', $shop) && filter_var($shop['local_ip'], FILTER_VALIDATE_IP) && $appPort > 0) {
                $shopModel->gate_lan_ip = $shop['local_ip'];
                $shopModel->gate_lan_port = intval($shop['app_port']);
                $shopModel->gate_enabled = $shop['gate_enabled'];
            }

            if ($shopModel->image_url !== $shop['image_url']) {
                $shopModel->image_url = $shop['image_url'];
                $shopModel->image_url_local = null;
                $shopModel->image_cached = false;
            }

            $shopModel->plan = (isset($shop['plan'])) ? $shop['plan'] : NULL;
            $shopModel->users_count = $shop['users_count'];
            $shopModel->orders_count = $shop['orders_count'];
            $shopModel->rating = $shop['rating'];
            $shopModel->bitcoin_connections = $shop['bitcoin_connections'];
            $shopModel->bitcoin_block_count = $shop['bitcoin_block_count'];
            $shopModel->expires_at = Carbon::createFromTimestamp($shop['expires_at']);
            $shopModel->last_sync_at = Carbon::now();
            $shopModel->save();

            $availableGoodsIds = [];

            foreach ($data['goods'] as $good) {
                $availableGoodsIds[] = $good['id'];

                $goodModel = Good::whereAppId($shop['app_id'])
                    ->whereAppGoodId($good['id'])
                    ->first();

                if (!$goodModel) {
                    $goodModel = new Good();
                    $goodModel->app_id = $shop['app_id'];
                    $goodModel->app_good_id = $good['id'];
                }

                $goodModel->category_id = $good['category_id'];
                $goodModel->title = $good['title'];

                if ($goodModel->image_url !== $good['image_url']) {
                    $goodModel->image_url = $good['image_url'];
                    $goodModel->image_url_local = null;
                    $goodModel->image_cached = false;
                }

                $goodModel->description = $good['description'];
                $goodModel->has_quests = $good['has_quests'];
                $goodModel->has_ready_quests = $good['has_ready_quests'];
                $goodModel->buy_count = $good['buy_count'];
                $goodModel->reviews_count = $good['reviews_count'];
                $goodModel->rating = $good['rating'];
                $goodModel->save();

                $goodCitiesData = [];
                foreach ($good['cities'] as $cityId) {
                    $goodsCityModel = GoodsCity::firstOrCreate([
                        'good_id' => $goodModel->id,
                        'app_id' => $shop['app_id'],
                        'app_good_id' => $good['id'],
                        'city_id' => $cityId
                    ]);
                    $goodCitiesData[] = $goodsCityModel->id;
                }

                GoodsCity::whereAppId($shop['app_id'])
                    ->whereAppGoodId($good['id'])
                    ->whereGoodId($goodModel->id)
                    ->whereNotIn('id', $goodCitiesData)
                    ->delete();

                $availablePositionsIds = [];
                $availablePackagesIds = [];
                foreach ($good['packages'] as $goodPackage) {
                    $goodPackageModel = $goodModel->packages()
                        ->where('app_package_id', '=', $goodPackage['id'])->first();

                    if (!$goodPackageModel) {
                        $goodPackageModel = new GoodsPackage();
                        $goodPackageModel->good_id = $goodModel->id;
                        $goodPackageModel->app_id = $shop['app_id'];
                        $goodPackageModel->app_good_id = $good['id'];
                        $goodPackageModel->app_package_id = $goodPackage['id'];
                    }

                    $goodPackageModel->amount = $goodPackage['amount'];
                    $goodPackageModel->measure = $goodPackage['measure'];
                    $goodPackageModel->price = $goodPackage['price'];
                    $goodPackageModel->currency = $goodPackage['currency'];
                    $goodPackageModel->preorder = $goodPackage['preorder'];
                    $goodPackageModel->city_id = $goodPackage['city_id'];
                    $goodPackageModel->has_quests = $goodPackage['has_quests'] ?? false;
                    $goodPackageModel->has_ready_quests = $goodPackage['has_ready_quests'] ?? false;
                    $goodPackageModel->save();
                    $availablePackagesIds[] = $goodPackageModel->id;

                    foreach ($goodPackage['positions'] as $goodPosition) {
                        $goodPositionModel = $goodPackageModel->positions()
                            ->where('region_id', $goodPosition['region_id'])
                            ->where('app_custom_place_id', $goodPosition['custom_place_id'])
                            ->where('app_custom_place_title', $goodPosition['custom_place_title'])
                            ->first();

                        if (!$goodPositionModel) {
                            $goodPositionModel = new GoodsPosition();
                            $goodPositionModel->good_id = $goodModel->id;
                            $goodPositionModel->package_id = $goodPackageModel->id;
                            $goodPositionModel->app_id = $shop['app_id'];
                            $goodPositionModel->app_package_id = $goodPackageModel->app_package_id;
                            $goodPositionModel->region_id = $goodPosition['region_id'];
                            $goodPositionModel->app_custom_place_id = $goodPosition['custom_place_id'];
                            $goodPositionModel->app_custom_place_title = $goodPosition['custom_place_title'];
                            $goodPositionModel->save();
                        }

                        $availablePositionsIds[] = $goodPositionModel->id;
                    }
                }

                $goodModel->packages()->whereNotIn('id', $availablePackagesIds)->delete();
                $goodModel->positions()->whereNotIn('id', $availablePositionsIds)->delete();
            }

            foreach ($data['orders'] as $order) {
                /** @var User $user */
                $user = User::whereUsername($order['user'])->first();
                if (!$user) {
                    continue;
                }

                $orderModel = $shopModel->orders()
                    ->where('app_order_id', $order['id'])
                    ->first();

                if (!$orderModel) {
                    $orderModel = new Order();
                    $orderModel->app_id = $shop['app_id'];
                    $orderModel->app_order_id = $order['id'];
                    $orderModel->user_id = $user->id;
                }

                $orderModel->city_id = $order['city_id'];
                $orderModel->app_good_id = $order['good_id'];
                $orderModel->good_title = $order['good_title'];

                if (!$orderModel->good_id) {
                    $good = $shopModel->goods()
                        ->where('app_good_id', $order['good_id'])
                        ->first();
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
                        $orderReview->app_id = $shop['app_id'];
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
            }

            $shopModel->goods()->whereNotIn('app_good_id', $availableGoodsIds)->update([
                'has_quests' => false,
                'has_ready_quests' => false
            ]);
            $shopModel->save();
            $message->getDelivery()->acknowledge();
        } catch (Exception $e) {
            $message->getDelivery()->reject(true);
        }
    }
}