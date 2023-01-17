<?php
/**
 * File: OrderReview.php
 * This file is part of MM2-catalog project.
 * Do not modify if you do not know what to do.
 */

namespace App;

use Illuminate\Database\Eloquent\Builder;

/**
 * App\OrderReview
 *
 * @property int $id
 * @property int $good_id
 * @property int $user_id
 * @property int $order_id
 * @property string $app_id
 * @property int $app_good_id
 * @property int $app_order_id
 * @property string $text
 * @property int $shop_rating
 * @property int $dropman_rating
 * @property int $item_rating
 * @property string $reply_text
 * @property string|null $created_at
 * @method static Builder|OrderReview whereAppGoodId($value)
 * @method static Builder|OrderReview whereAppId($value)
 * @method static Builder|OrderReview whereAppOrderId($value)
 * @method static Builder|OrderReview whereCreatedAt($value)
 * @method static Builder|OrderReview whereDropmanRating($value)
 * @method static Builder|OrderReview whereGoodId($value)
 * @method static Builder|OrderReview whereId($value)
 * @method static Builder|OrderReview whereItemRating($value)
 * @method static Builder|OrderReview whereOrderId($value)
 * @method static Builder|OrderReview whereReplyText($value)
 * @method static Builder|OrderReview whereShopRating($value)
 * @method static Builder|OrderReview whereText($value)
 * @method static Builder|OrderReview whereUserId($value)
 * @mixin \Eloquent
 */
class OrderReview extends Model
{
    public $timestamps = false;
    protected $table = 'orders_reviews';
    protected $primaryKey = 'id';

    public function getAverageRating()
    {
        return ($this->shop_rating + $this->dropman_rating + $this->item_rating) / 3;
    }
}