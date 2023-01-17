<?php

namespace App;

use App\Packages\FetchingImagesTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;

/**
 * Магазин.
 *
 * @property int         $id                  Идентификатор магазина.
 * @property string      $app_id
 * @property string      $app_key
 * @property string      $url                 Ссылка на магазин.
 * @property string      $title               Название магазина.
 * @property string      $image_url
 * @property int         $users_count         Количество пользователей.
 * @property int         $orders_count        Количество заказов.
 * @property int         $bitcoin_connections
 * @property int         $bitcoin_block_count
 * @property Carbon|null $last_sync_at        Дата последней синхронизации каталога с магазином.
 * @property Carbon|null $created_at          Дата создания записи.
 * @property Carbon|null $updated_at          Дата последнего обновления.
 * @property int         $enabled             Активен(1) или нет(0).
 * @property string|null $image_url_local
 * @property int         $image_cached
 * @property string|null $expires_at
 * @property float       $rating
 * @property string|null $plan
 * @property int         $gate_enabled
 * @property string|null $gate_lan_ip
 * @property string|null $gate_lan_port
 *
 * @property-read Collection|Good[] $goods
 * @property-read Collection|Order[] $orders
 *
 * @method static Builder|Shop orderBySmart()
 * @method static Builder|Shop whereAppId($value)
 * @method static Builder|Shop whereAppKey($value)
 * @method static Builder|Shop whereBitcoinBlockCount($value)
 * @method static Builder|Shop whereBitcoinConnections($value)
 * @method static Builder|Shop whereCreatedAt($value)
 * @method static Builder|Shop whereId($value)
 * @method static Builder|Shop whereImageUrl($value)
 * @method static Builder|Shop whereLastSyncAt($value)
 * @method static Builder|Shop whereOrdersCount($value)
 * @method static Builder|Shop whereTitle($value)
 * @method static Builder|Shop whereUpdatedAt($value)
 * @method static Builder|Shop whereUrl($value)
 * @method static Builder|Shop whereUsersCount($value)
 * @method static Builder|Shop whereEnabled($value)
 * @method static Builder|Shop whereImageCached($value)
 * @method static Builder|Shop whereImageUrlLocal($value)
 * @method static Builder|Shop whereEosEnabled($value)
 * @method static Builder|Shop whereExpiresAt($value)
 * @method static Builder|Shop whereRating($value)
 * @method static Builder|Shop wherePlan($value)
 * @method static Builder|Shop whereGateEnabled($value)
 * @method static Builder|Shop whereGateLanIp($value)
 * @method static Builder|Shop whereGateLanPort($value)
 */
class Shop extends Model
{
    use FetchingImagesTrait;

    public const PLAN_BASIC = 'basic';
    public const PLAN_ADVANCED = 'advanced';
    public const PLAN_INDIVIDUAL = 'individual';
    public const PLAN_FEE = 'fee';
    public const PLAN_INDIVIDUAL_FEE = 'individual_fee';

    /** @inheritdoc */
    protected $table = 'shops';

    /** @inheritdoc */
    protected $primaryKey = 'id';

    protected $remoteImageURLColumn = 'image_url';
    protected $localImageURLColumn = 'image_url_local';
    protected $localImageCachedColumn = 'image_cached';

    /**
     * @return mixed
     */
    public static function enabled()
    {
        return Shop::where('enabled', true);
    }

    /**
     * @return mixed
     */
    public static function available()
    {
        $minLastSync = Carbon::now()->addMinutes(-config('catalog.shop_expires_at'));
        return Shop::where('enabled', true) // is applied to catalog
            ->where('last_sync_at', '>=', $minLastSync) // alive
            ->where(function ($q) {
                return $q->where('expires_at', '>=', Carbon::now()); // not expired
            });
    }

    /**
     * @return HasMany
     */
    public function goods(): HasMany
    {
        return $this->hasMany(Good::class, 'app_id', 'app_id');
    }

    /**
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'app_id', 'app_id');
    }

    /**
     * @param $query
     *
     * @return mixed
     */
    public function scopeOrderBySmart($query)
    {
        return $query->select('*')
            ->addSelect(self::getSQLSelectForSmartSort())
            ->orderByRaw(self::getSQLOrderByForSmartSort());
    }

    /**
     * @return Expression
     */
    public static function getSQLSelectForSmartSort()
    {
        return DB::raw(
            '(' .
            '   1.00 * LEAST(`orders_count`, 100) + ' . // for 0-100 orders, + 1 points for each
            '   0.50 * GREATEST(0, LEAST(`orders_count` - 100, 400)) + ' . // for 101-500 orders, + 0.5 for each
            '   0.25 * GREATEST(0, LEAST(`orders_count` - 500, 500)) + ' . // for 501-1000 orders, +0.25 for each
            '   0.10 * GREATEST(0, `orders_count` - 1000) ' . // for 1001+ orders, +0.1 for each
            ') * `rating`' . // multiply by avg good rating
            '   as rating_alg,' .
            '(' .
            '   case when UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`last_sync_at`) < 1200' .
            '        then 1' .
            '        else 0' .
            '   END' .
            ')' .
            '   as alive, ' .
            '(' .
            "    (`plan` = 'fee') ||" .
            '    (case when UNIX_TIMESTAMP(`expires_at`) - UNIX_TIMESTAMP(NOW()) > 0' .
            '        then 1' .
            '        else 0' .
            '    END)' .
            ')' .
            '   as not_expired');
    }

    /**
     * @return Expression
     */
    public static function getSQLOrderByForSmartSort()
    {
        return DB::raw('alive desc, not_expired desc, rating_alg desc');
    }

    /**
     * @param Builder $shops
     * @param Request $request
     *
     * @return Builder
     */
    public function scopeApplySearchFilters(Builder $shops, Request $request)
    {
        if (!empty($query = $request->get('query'))) {
            $shops = $shops->filterTitle($query);
        }

        return $shops;
    }

    /**
     * @param $query
     * @param $title
     *
     * @return mixed
     */
    public function scopeFilterTitle($query, $title)
    {
        $transliterated = transliterate($title);
        return $query->where('shops.title', 'ILIKE', '%' . $title . '%')->orWhere('shops.title', 'ILIKE', '%' . $transliterated . '%');
    }

    /**
     * TODO: какой вообще принципиальный смысл этого метода?
     * TODO: Это не DTO и не сущность, чтобы был геттер
     *
     * @return int
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return string
     */
    public function getUsersCountRange(): string
    {
        $count = $this->getUsersCount();
        if ($count < 300) {
            return 'менее 300';
        } elseif ($count >= 300 && $count <= 600) {
            return '300 - 600';
        } elseif ($count > 600 && $count <= 1000) {
            return '600 - 1000';
        } elseif ($count > 1000 && $count <= 1500) {
            return '1000 - 1500';
        } elseif ($count > 1500 && $count <= 2500) {
            return '1500 - 2500';
        } elseif ($count > 2500 && $count <= 5000) {
            return '2500 - 5000';
        } elseif ($count > 5000) {
            return 'более 5000';
        }

        return '';
    }

    /**
     * TODO: какой вообще принципиальный смысл этого метода?
     * TODO: Это не DTO и не сущность, чтобы был геттер
     *
     * @return int
     */
    public function getUsersCount()
    {
        return $this->users_count;
    }

    /**
     * @return string
     */
    public function getOrdersCountRange()
    {
        $count = $this->getOrdersCount();

        return create_range($count, [
            300, 600, 1000, 1500, 2500, 5000, 10000, 25000, 30000, 35000, 40000, 45000, 50000, 100000, 150000, 200000,
            250000, 300000, 350000, 400000, 450000, 500000, 750000, 1000000, 2000000, 3000000, 5000000
        ]);
    }

    /**
     * TODO: какой вообще принципиальный смысл этого метода?
     * TODO: Это не DTO и не сущность, чтобы был геттер
     *
     * @return int
     */
    public function getOrdersCount()
    {
        return $this->orders_count;
    }

    /**
     * TODO: какой вообще принципиальный смысл этого метода?
     * TODO: Это не DTO и не сущность, чтобы был геттер
     *
     * @return float
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * @return mixed
     */
    public function avatar()
    {
        if ($this->localImageCached()) {
            return url($this->localImageURL());
        }

        return noavatar();
    }
}
