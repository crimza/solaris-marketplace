<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;

/**
 * App\Stat
 *
 * @property integer $id
 * @property string $date
 * @property integer $visitors_count
 * @property integer $orders_count
 * @method static Builder|Stat whereId($value)
 * @method static Builder|Stat whereDate($value)
 * @method static Builder|Stat whereVisitorsCount($value)
 * @method static Builder|Stat whereOrdersCount($value)
 * @mixin \Eloquent
 * @property string $visitors_data
 * @method static Builder|Stat whereVisitorsData($value)
 */
class Stat extends Model
{
    public $timestamps = false;
    protected $table = 'stats';
    protected $primaryKey = 'id';
    protected $fillable = [
        'date', 'visitors_count', 'visitors_data', 'orders_count'
    ];

    protected $casts = [
        'visitors_data' => 'array'
    ];

    public static function getVisitorsCacheKey($date = null)
    {
        if ($date == null) {
            $date = Carbon::today();
        }

        return sprintf('%d-%d-%d_visitors', $date->day, $date->month, $date->year);
    }
}