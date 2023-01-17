<?php

namespace App\Providers\Extensions;

interface Extender
{
    /**
     * Extends out-of-box laravel's functionality.
     *
     * @return void
     */
    public function extend();
}