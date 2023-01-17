<?php

namespace App;

use Illuminate\Database\Query\Builder;

/**
 * App\Region
 *
 * @property integer $id
 * @property integer $city_id
 * @property integer $parent_id
 * @property string $title
 * @property integer $priority
 * @method static Builder|Region whereId($value)
 * @method static Builder|Region whereCityId($value)
 * @method static Builder|Region whereParentId($value)
 * @method static Builder|Region whereTitle($value)
 * @method static Builder|Region wherePriority($value)
 * @mixin \Eloquent
 */
class Region extends Model
{
    public $timestamps = false;
    protected $table = 'regions';
    protected $primaryKey = 'id';
    protected $fillable = [
        'city_id', 'parent_id', 'title', 'priority'
    ];
}
