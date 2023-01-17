<?php
/**
 * File: OrderPosition.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App;

use Illuminate\Database\Eloquent\Builder;

/**
 * App\OrderPosition
 *
 * @property int $id
 * @property int $order_id
 * @property string $quest
 * @method static Builder|OrderPosition whereId($value)
 * @method static Builder|OrderPosition whereOrderId($value)
 * @method static Builder|OrderPosition whereQuest($value)
 * @mixin \Eloquent
 */
class OrderPosition extends Model
{
    public $timestamps = false;
    protected $table = 'orders_positions';
    protected $primaryKey = 'id';
}