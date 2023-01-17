<?php

declare(strict_types=1);

namespace App;

use App\Packages\FetchingImagesTrait;
use App\Packages\Stub;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;

/**
 * Заказ.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int         $city_id
 * @property int|null    $position_id
 * @property int|null    $review_id
 * @property int|null    $good_id
 * @property string      $app_id
 * @property int         $app_order_id
 * @property int         $app_good_id
 * @property string      $good_title
 * @property string      $good_image_url
 * @property string|null $good_image_url_local
 * @property int         $good_image_cached
 * @property float       $package_amount
 * @property string      $package_measure
 * @property float       $package_price
 * @property string      $package_currency
 * @property int         $package_preorder
 * @property string|null $package_preorder_time
 * @property string      $status
 * @property string|null $comment
 * @property string|null $app_created_at
 * @property string|null $app_updated_at
 * @property Carbon|null $created_at            Дата создания.
 * @property Carbon|null $updated_at            Дата последнего обновления.
 * @property Carbon|null $last_sync_at          Дата последней синхронизации с магазином.
 *
 * @property-read OrderPosition $position
 * @property-read Good|null $good
 * @property-read OrderReview $review
 * @property-read Shop $shop
 * @property-read User $user
 *
 * @method static Builder|Order whereAppCreatedAt($value)
 * @method static Builder|Order whereAppGoodId($value)
 * @method static Builder|Order whereAppId($value)
 * @method static Builder|Order whereAppOrderId($value)
 * @method static Builder|Order whereAppUpdatedAt($value)
 * @method static Builder|Order whereCityId($value)
 * @method static Builder|Order whereComment($value)
 * @method static Builder|Order whereCreatedAt($value)
 * @method static Builder|Order whereGoodId($value)
 * @method static Builder|Order whereGoodImageCached($value)
 * @method static Builder|Order whereGoodImageUrl($value)
 * @method static Builder|Order whereGoodImageUrlLocal($value)
 * @method static Builder|Order whereGoodTitle($value)
 * @method static Builder|Order whereId($value)
 * @method static Builder|Order wherePackageAmount($value)
 * @method static Builder|Order wherePackageCurrency($value)
 * @method static Builder|Order wherePackageMeasure($value)
 * @method static Builder|Order wherePackagePreorder($value)
 * @method static Builder|Order wherePackagePreorderTime($value)
 * @method static Builder|Order wherePackagePrice($value)
 * @method static Builder|Order wherePositionId($value)
 * @method static Builder|Order whereReviewId($value)
 * @method static Builder|Order whereStatus($value)
 * @method static Builder|Order whereUpdatedAt($value)
 * @method static Builder|Order whereUserId($value)
 * @method static Builder|Order applySearchFilters(Request $request)
 * @method static Builder|Order filterStatus($status)
 * @method static where(string $string, string $string1, int|mixed $id)
 * @method static with(string[] $array)
 */
class Order extends Model
{
    use FetchingImagesTrait;

    const STATUS_PREORDER_PAID = 'preorder_paid';
    const STATUS_PAID = 'paid';
    const STATUS_PROBLEM = 'problem';
    const STATUS_FINISHED_AFTER_DISPUTE = 'finished_after_dispute';
    const STATUS_CANCELLED_AFTER_DISPUTE = 'cancelled_after_dispute';
    const STATUS_FINISHED = 'finished';
    const STATUS_CANCELLED = 'cancelled';

    /** @inheritdoc */
    protected $table = 'orders';

    /** @inheritdoc */
    protected $primaryKey = 'id';

    protected $remoteImageURLColumn = 'good_image_url';
    protected $localImageURLColumn = 'good_image_url_local';
    protected $localImageCachedColumn = 'good_image_cached';

    /**
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'app_id', 'app_id');
    }

    /**
     * @return BelongsTo
     */
    public function good(): BelongsTo
    {
        return $this->belongsTo(Good::class, 'good_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function position(): HasOne
    {
        return $this->hasOne(OrderPosition::class, 'order_id', 'id');
    }

    /**
     * @return HasOne
     */
    public function review(): HasOne
    {
        return $this->hasOne(OrderReview::class, 'order_id', 'id');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * @param Builder $orders
     * @param Request $request
     *
     * @return Builder
     */
    public function scopeApplySearchFilters(Builder $orders, Request $request)
    {
        if (!empty($status = $request->get('status'))) {
            $orders = $orders->filterStatus($status);
        }

        return $orders;
    }

    /**
     * @param $query
     * @param $status
     *
     * @return mixed
     */
    public function scopeFilterStatus($query, $status)
    {
        if ($status === 'active') {
            return $query->whereNotIn('status', [Order::STATUS_FINISHED, Order::STATUS_CANCELLED]);
        }

        return $query->where('status', $status);
    }

    /**
     * @return Stub|Good
     */
    public function _stub_good()
    {
        return stub('Good', [
            'id' => $this->good_id,
            'app_id' => $this->app_id,
            'city_id' => $this->city_id,
            'title' => $this->good_title,
            'image_url' => $this->good_image_url,
        ]);
    }

    /**
     * @return Stub|GoodsPackage
     */
    public function _stub_package()
    {
        return stub('GoodsPackage', [
            'amount' => $this->package_amount,
            'measure' => $this->package_measure,
            'price' => $this->package_price,
            'currency' => $this->package_currency,
            'preorder' => $this->package_preorder,
            'preorder_time' => $this->package_preorder_time
        ]);
    }

    /**
     * Returns time before address should be hidden in human-readable format.
     *
     * @return string
     */
    public function getHumanQuestRemainingTime(): string
    {
        $diff = $this->getQuestRemainingTime() / 60;
        $hours = floor($diff / 60);
        $minutes = $diff % 60;

        return sprintf('%d %s %d %s',
            $hours, plural($hours, ['час', 'часа', 'часов']),
            $minutes, plural($minutes, ['минуту', 'минуты', 'минут'])
        );
    }

    /**
     * Returns time in seconds before address should be hidden.
     *
     * @return int
     */
    public function getQuestRemainingTime()
    {
        return config('catalog.order_quest_time') * 3600 - Carbon::now()->diffInSeconds($this->app_created_at, TRUE);
    }

    /**
     * @return string
     */
    public function getHumanPreorderRemainingTime(): string
    {
        $diff = $this->getPreorderRemainingTime() / 60;
        $hours = floor($diff / 60);
        $minutes = $diff % 60;

        return sprintf('%d %s %d %s',
            $hours, plural($hours, ['час', 'часа', 'часов']),
            $minutes, plural($minutes, ['минуту', 'минуты', 'минут'])
        );
    }

    /**
     * @return int
     */
    public function getPreorderRemainingTime()
    {
        $preorderTime = config('catalog.preorder_close_time') * 3600;

        return $preorderTime - Carbon::now()->diffInSeconds($this->app_updated_at, TRUE);
    }

    /**
     * @return string
     */
    public function getHumanStatus(): string
    {
        switch ($this->status) {
            case Order::STATUS_PAID:
                return 'Ожидает отзыва';

            case Order::STATUS_PREORDER_PAID:
                return 'Ожидает доставки';

            case Order::STATUS_FINISHED:
            case Order::STATUS_FINISHED_AFTER_DISPUTE:
                return 'Завершен';

            case Order::STATUS_PROBLEM:
                return 'Проблема';

            case Order::STATUS_CANCELLED_AFTER_DISPUTE:
            case Order::STATUS_CANCELLED:
                return 'Отменен';

            default:
                return 'Неизвестно';
        }
    }
}
