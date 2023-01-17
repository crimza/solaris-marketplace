<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;

/**
 * App\GoodsCity
 *
 * @property int $id
 * @property string $app_id
 * @property int $app_good_id
 * @property int $city_id
 * @method static \Illuminate\Database\Query\Builder|GoodsCity whereId($value)
 * @method static \Illuminate\Database\Query\Builder|GoodsCity whereAppId($value)
 * @method static \Illuminate\Database\Query\Builder|GoodsCity whereAppGoodId($value)
 * @method static \Illuminate\Database\Query\Builder|GoodsCity whereCityId($value)
 * @mixin \Eloquent
 * @property-read \App\City $city
 * @property int $good_id
 * @method static Builder|GoodsCity whereGoodId($value)
 */
class GoodsCity extends Model
{
    public $timestamps = false;
    protected $table = 'goods_cities';
    protected $primaryKey = 'id';
    protected $fillable = [
        'good_id', 'app_id', 'app_good_id', 'city_id'
    ];

    public function city()
    {
        return $this->hasOne('App\City', 'id', 'city_id');
    }
}
