<?php

declare(strict_types=1);

namespace App;

use App\Packages\FetchingImagesTrait;
use App\Packages\Utils\BitcoinUtils;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Товар.
 *
 * @property int         $id
 * @property string      $app_id
 * @property int         $app_good_id
 * @property int         $category_id
 * @property string      $title
 * @property string      $image_url
 * @property string|null $image_url_local
 * @property int         $image_cached
 * @property string      $description
 * @property int         $has_quests
 * @property int         $has_ready_quests
 * @property int         $buy_count
 * @property int         $reviews_count
 * @property float       $rating
 * @property Carbon|null $created_at       Дата создания.
 * @property Carbon|null $updated_at       Дата последнего обновления.
 * @property Carbon|null $last_sync_at     Дата последней синхронизации с магазином.
 *
 * @property-read GoodsPackage[] $packages
 * @property-read GoodsPosition[] $positions
 * @property-read City $city
 * @property-read Shop $shop
 * @property-read City[] $cities
 *
 * @method static Builder|Good whereAppGoodId($value)
 * @method static Builder|Good whereAppId($value)
 * @method static Builder|Good whereBuyCount($value)
 * @method static Builder|Good whereCategoryId($value)
 * @method static Builder|Good whereCityId($value)
 * @method static Builder|Good whereCreatedAt($value)
 * @method static Builder|Good whereDescription($value)
 * @method static Builder|Good whereHasQuests($value)
 * @method static Builder|Good whereHasReadyQuests($value)
 * @method static Builder|Good whereId($value)
 * @method static Builder|Good whereImageCached($value)
 * @method static Builder|Good whereImageUrl($value)
 * @method static Builder|Good whereImageUrlLocal($value)
 * @method static Builder|Good whereRating($value)
 * @method static Builder|Good whereReviewsCount($value)
 * @method static Builder|Good whereTitle($value)
 * @method static Builder|Good whereUpdatedAt($value)
 * @method static Builder|Good applySearchFilters(Request $request)
 * @method static Builder|Good filterCategory($categoryId)
 * @method static Builder|Good filterTitle($title)
 * @method static Builder|Good orderBySmart()
 * @method static with(string[] $array)
 */
class Good extends Model
{
    use FetchingImagesTrait;

    /** @inheritdoc */
    protected $table = 'goods';

    /** @inheritdoc */
    protected $primaryKey = 'id';

    /** @inheritdoc */
    protected $fillable = [
        'shop_id', 'category_id', 'title', 'description', 'image_url', 'has_quests', 'has_ready_quests', 'priority',
    ];

    protected $remoteImageURLColumn = 'image_url';
    protected $localImageURLColumn = 'image_url_local';
    protected $localImageCachedColumn = 'image_cached';

    // TODO: названия переменных не соответствует нотации. Скорее всего логике с этими переменными не место в модели
    private $_cheapestAvailablePackage = null;
    private $_mostExpensiveAvailablePackage = null;
    private $_cheapestPackage = null;
    private $_mostExpensivePackage = null;

    /**
     * @return mixed
     */
    public static function available()
    {
        $minLastSync = Carbon::now()->addMinutes(-config('catalog.shop_expires_at'));
        return Good::where('image_cached', true)
            ->where('has_quests', true)
            ->whereHas('shop', function ($query) use ($minLastSync) {
                $query
                    ->where('enabled', true) // is applied to catalog
                    ->where('last_sync_at', '>=', $minLastSync) // alive
                    ->where(function ($q) {
                        return $q->where('expires_at', '>=', Carbon::now())
                            ->orWhere('plan', Shop::PLAN_FEE); // not expired
                    });
            });
    }

    /**
     * @return mixed
     */
    public function category()
    {
        return Category::getById($this->category_id)->first();
    }

    /**
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'app_id', 'app_id');
    }

    /**
     * @return BelongsToMany
     */
    public function cities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'goods_cities');
    }

    /**
     * @return Builder|GoodsPackage[]
     */
    public function availablePackages()
    {
        return $this->packages()->where(function ($query) {
            $query->whereHas('positions')->orWhere('preorder', true);
        });
    }

    /**
     * @return HasMany
     */
    public function packages(): HasMany
    {
        return $this->hasMany(GoodsPackage::class, 'good_id', 'id');
    }

    /**
     * @return HasMany
     */
    public function positions(): HasMany
    {
        return $this->hasMany('App\GoodsPosition', 'good_id', 'id');
    }

    /**
     * @param Builder $goods
     * @param Request $request
     *
     * @return Builder
     */
    public function scopeApplySearchFilters(Builder $goods, Request $request): Builder
    {
        if ($request->has('region') ||
            $request->get('availability') === 'ready' ||
            $request->has('city')
        ) {
            /** @var Builder $packages */
            $packages = GoodsPackage::select('good_id');
            $city = $request->get('city');

            if (in_array($city, City::citiesWithRegions()) && is_numeric($region = $request->get('region'))) {
                $packages = $packages->filterRegion($region);
            }

            if ($request->get('availability', 'all') === 'ready') {
                $packages = $packages->where('has_ready_quests', true);
            }

            if (is_numeric($city)) {
                $packages = $packages->where('city_id', $city);
            }

            $goods->whereIn('id', $packages);
        }

        if (is_numeric($category = $request->get('category'))) {
            $goods = $goods->filterCategory($category);
        }

        if (!empty($query = $request->get('query'))) {
            $goods = $goods->filterTitle($query);
        }

        return $goods;
    }

    /**
     * @param $query
     * @param $title
     *
     * @return mixed
     */
    public function scopeFilterTitle($query, $title)
    {
        if (empty($title)) {
            return $query;
        }

        return $query->where('title', 'ILIKE', '%' . $title . '%');
    }

    /**
     * @param $query
     * @param $categoryId
     *
     * @return mixed
     */
    public function scopeFilterCategory($query, $categoryId)
    {
        $category = Category::find($categoryId);
        if (!$category) {
            return $query;
        }
        if ($category->isMain()) {
            return $query->whereIn('category_id', $category->children()->pluck('id')->toArray());
        } else {
            return $query->where('category_id', $categoryId);
        }
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
            ->orderBy(self::getSQLOrderByForSmartSort(), 'desc');
    }

    /**
     * @return Expression
     */
    public static function getSQLSelectForSmartSort()
    {
        return DB::raw('(' .
            '1.00 * LEAST(`buy_count`, 100) + ' . // for 0-100 orders, + 1 points for each
            '0.50 * GREATEST(0, LEAST(`buy_count` - 100, 400)) + ' . // for 101-500 orders, + 0.5 for each
            '0.25 * GREATEST(0, LEAST(`buy_count` - 500, 500)) + ' . // for 501-1000 orders, +0.25 for each
            '0.10 * GREATEST(0, `buy_count` - 1000) ' . // for 1001+ orders, +0.1 for each
            ') * `rating`' . // multiply by avg good rating
            ' as sort_value');
    }

    /**
     * @return Expression
     */
    public static function getSQLOrderByForSmartSort()
    {
        return DB::raw('sort_value');
    }

    /**
     * @return GoodsPackage|null
     */
    public function getCheapestAvailablePackage()
    {
        $this->_findCheapestAndMostExpensiveAvailablePackages();

        return $this->_cheapestAvailablePackage;
    }

    /**
     * @return void
     */
    private function _findCheapestAndMostExpensiveAvailablePackages(): void
    {
        if (!$this->_cheapestAvailablePackage || !$this->_mostExpensiveAvailablePackage) {
            $availablePackages = $this->availablePackages;
            $availablePackages = $availablePackages->each(function (&$item, $key) {
                /** @var GoodsPackage $item */
                $item->price_btc = $item->getPrice(BitcoinUtils::CURRENCY_BTC);
            })->sortBy('price_btc');

            $this->_cheapestAvailablePackage = $availablePackages->first();
            $this->_mostExpensiveAvailablePackage = $availablePackages->last();
        }
    }

    /**
     * @return GoodsPackage|null
     */
    public function getMostExpensiveAvailablePackage()
    {
        $this->_findCheapestAndMostExpensiveAvailablePackages();

        return $this->_mostExpensiveAvailablePackage;
    }

    /**
     * @return GoodsPackage|null
     */
    public function getCheapestPackage()
    {
        $this->_findCheapestAndMostExpensivePackages();

        return $this->_cheapestPackage;
    }

    /**
     * @return void
     */
    private function _findCheapestAndMostExpensivePackages(): void
    {
        if (!$this->_cheapestPackage || !$this->_mostExpensivePackage) {
            $packages = $this->packages;
            $packages = $packages->each(function (&$item, $key) {
                /** @var GoodsPackage $item */
                $item->price_btc = $item->getPrice(BitcoinUtils::CURRENCY_BTC);
            })->sortBy('price_btc');

            $this->_cheapestPackage = $packages->first();
            $this->_mostExpensivePackage = $packages->last();
        }
    }

    /**
     * @return GoodsPackage|null
     */
    public function getMostExpensivePackage()
    {
        $this->_findCheapestAndMostExpensivePackages();

        return $this->_mostExpensivePackage;
    }

    /**
     * @return string
     */
    public function getHumanRating(): string
    {
        return number_format($this->rating, 2);
    }

    /**
     * TODO: зачем геттер в модели?!
     *
     * @return int
     */
    public function getBuyCount()
    {
        return $this->buy_count;
    }

    /**
     * @return string
     */
    public function getBuyCountRange(): string
    {
        $count = $this->getBuyCount();
        if ($count < 100) {
            return '<100';
        } elseif ($count < 1000) {
            return (floor($count / 100) * 100) . '+';
        } else {
            return (floor($count / 1000) * 1000) . '+';
        }
    }
}
