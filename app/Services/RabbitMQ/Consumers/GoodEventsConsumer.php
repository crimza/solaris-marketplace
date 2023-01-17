<?php

declare(strict_types=1);

namespace App\Services\RabbitMQ\Consumers;

use App\Good;
use App\GoodsCity;
use App\GoodsPackage;
use App\GoodsPosition;
use App\Shop;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Kunnu\RabbitMQ\RabbitMQException;
use Kunnu\RabbitMQ\RabbitMQIncomingMessage;
use Throwable;

class GoodEventsConsumer extends Consumer
{
    /**
     * @param RabbitMQIncomingMessage $message Сообщение из очереди.
     *
     * @throws RabbitMQException
     */
    public function consume(RabbitMQIncomingMessage $message): void
    {
        try {
            $good = json_decode($message->getStream(), true);
            $goodModel = Good::whereAppId($good['app_id'])
                ->whereAppGoodId($good['id'])
                ->first();

            if (!$goodModel) {
                $goodModel = new Good();
                $goodModel->app_id = $good['app_id'];
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
            $goodModel->buy_count = $good['buy_count'] ?? 0;
            $goodModel->reviews_count = $good['reviews_count'];
            $goodModel->rating = $good['rating'];
            $goodModel->last_sync_at = Carbon::createFromTimestamp(time());
            $goodModel->save();

            $goodCitiesData = [];
            foreach ($good['cities'] as $cityId) {
                $goodsCityModel = GoodsCity::firstOrCreate([
                    'good_id' => $goodModel->id,
                    'app_id' => $good['app_id'],
                    'app_good_id' => $good['id'],
                    'city_id' => $cityId
                ]);
                $goodCitiesData[] = $goodsCityModel->id;
            }

            GoodsCity::whereAppId($good['app_id'])
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
                    $goodPackageModel->app_id = $good['app_id'];
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
                        $goodPositionModel->app_id = $good['app_id'];
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

            $shopModel = Shop::whereAppId($goodModel->app_id)->first();
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
