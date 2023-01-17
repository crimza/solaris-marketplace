<?php
/**
 * File: GoodsPosition.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App;

use Illuminate\Database\Eloquent\Builder;

/**
 * App\GoodsPosition
 *
 * @property int $id
 * @property int $package_id
 * @property string $app_id
 * @property int $app_package_id
 * @property int|null $region_id
 * @property int|null $app_custom_place_id
 * @property string $app_custom_place_title
 * @method static Builder|GoodsPosition whereAppCustomPlaceId($value)
 * @method static Builder|GoodsPosition whereAppCustomPlaceTitle($value)
 * @method static Builder|GoodsPosition whereAppId($value)
 * @method static Builder|GoodsPosition whereAppPackageId($value)
 * @method static Builder|GoodsPosition whereId($value)
 * @method static Builder|GoodsPosition wherePackageId($value)
 * @method static Builder|GoodsPosition whereRegionId($value)
 * @mixin \Eloquent
 * @property int $good_id
 * @method static Builder|GoodsPosition whereGoodId($value)
 * @property-read \App\Region|null $region
 */
class GoodsPosition extends Model
{
    public $timestamps = false;
    protected $table = 'goods_positions';
    protected $primaryKey = 'id';

    public function region()
    {
        return $this->belongsTo('App\Region', 'region_id', 'id');
    }
}