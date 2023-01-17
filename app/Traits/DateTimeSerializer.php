<?php

namespace App\Traits;

use DateTimeInterface;

trait DateTimeSerializer
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}