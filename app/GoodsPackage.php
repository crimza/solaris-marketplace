<?php
/**
 * File: GoodsPackage.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App;

use App\Packages\Utils\BitcoinUtils;
use App\Packages\Utils\Formatters;
use Illuminate\Database\Eloquent\Builder;


/**
 * App\GoodsPackage
 *
 * @property int $id
 * @property string $app_id
 * @property int $app_good_id
 * @property int $app_package_id
 * @property int|null $app_custom_place_id
 * @property string|null $app_custom_place_title
 * @property int|null $region_id
 * @property float $amount
 * @property string $measure
 * @property float $price
 * @property string $currency
 * @property int $preorder
 * @property string $hash
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @method static Builder|GoodsPackage whereAmount($value)
 * @method static Builder|GoodsPackage whereAppCustomPlaceId($value)
 * @method static Builder|GoodsPackage whereAppCustomPlaceTitle($value)
 * @method static Builder|GoodsPackage whereAppGoodId($value)
 * @method static Builder|GoodsPackage whereAppId($value)
 * @method static Builder|GoodsPackage whereAppPackageId($value)
 * @method static Builder|GoodsPackage whereCreatedAt($value)
 * @method static Builder|GoodsPackage whereCurrency($value)
 * @method static Builder|GoodsPackage whereHash($value)
 * @method static Builder|GoodsPackage whereId($value)
 * @method static Builder|GoodsPackage whereMeasure($value)
 * @method static Builder|GoodsPackage wherePreorder($value)
 * @method static Builder|GoodsPackage wherePrice($value)
 * @method static Builder|GoodsPackage whereRegionId($value)
 * @method static Builder|GoodsPackage whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property int $good_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\GoodsPosition[] $positions
 * @method static Builder|GoodsPackage whereGoodId($value)
 * @property int $city_id
 * @property-read \App\City $city
 * @method static Builder|GoodsPackage whereCityId($value)
 * @property int $has_quests
 * @property int $has_ready_quests
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoodsPackage whereHasQuests($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\GoodsPackage whereHasReadyQuests($value)
 * @method static where(string $string, string $string1, int|mixed $id)
 * @method orderBy(string $string)
 */
class GoodsPackage extends Model
{
    const MEASURE_GRAM = 'gr';
    const MEASURE_PIECE = 'piece';
    const MEASURE_ML = 'ml';

    protected $table = 'goods_packages';
    protected $primaryKey = 'id';

    protected $fillable = [
        'app_id', 'good_id',
        'amount', 'measure',
        'price', 'currency',
        'preorder',
        'has_quests', 'has_ready_quests'
    ];

    public function positions()
    {
        return $this->hasMany('App\GoodsPosition', 'package_id', 'id');
    }

    public function city()
    {
        return $this->belongsTo('App\City', 'city_id', 'id');
    }

    /**
     * @return string
     */
    public function getHumanWeight()
    {
        return Formatters::getHumanWeight($this->amount, $this->measure);
    }

    /**
     * @param string|null $currency
     * @return string
     */
    public function getHumanPrice($currency = null)
    {
        $currency = $currency ?: $this->currency;
        return human_price($this->getPrice($currency), $currency);
    }

    /**
     * @param string|null $currency
     * @return float
     */
    public function getPrice($currency = null)
    {
        return BitcoinUtils::convert($this->price, $this->currency, $currency ?: $this->currency);
    }

    public function scopeFilterRegion($query, $region)
    {
        return $query->whereHas('positions', function ($query) use ($region) {
            return $query->where('region_id', $region);
        });
    }
}