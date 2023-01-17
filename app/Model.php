<?php

namespace App;

use App\Traits\DateTimeSerializer;
use \Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel
{
    protected $dateFormat = 'Y-m-d H:i:sO';

    use DateTimeSerializer;
}