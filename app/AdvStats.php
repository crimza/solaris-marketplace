<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

class AdvStats extends Model
{
    use SoftDeletes;

    protected $fillable = ['title'];
}