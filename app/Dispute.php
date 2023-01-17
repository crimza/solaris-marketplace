<?php

namespace App;

class Dispute extends Model
{
    protected $fillable = [
        'dispute_id',
        'shop_url',
        'app_id',
        'creator',
        'status',
        'decision',
        'moderator',
        'dispute_updated_at',
    ];

    public function shop()
    {
        return $this->belongsTo('App\Shop', 'app_id', 'app_id');
    }
}
