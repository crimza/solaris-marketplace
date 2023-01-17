<?php

namespace App\Providers\Extensions;

use Illuminate\Support\Str;

class ValidationExtender implements Extender
{
    /**
    * Custom rule list.
    *
    * @var array
     */
    protected $rules = ['pgusers'];

    /**
     * Extends out-of-box laravel's functionality.
     *
     * @return void
     */
    public function extend()
    {
        foreach ($this->rules as $rule) {
            /** @var  \App\Http\Rules\Rule  $handler */
            $handler = app('\App\Http\Rules\\' . Str::studly($rule));

            \Illuminate\Support\Facades\Validator::extend(
                $rule,
                function ($field, $value, $params) use ($handler) {
                    return $handler->validate($field, $value, $params);
                }
            );

            \Illuminate\Support\Facades\Validator::replacer(
                $rule,
                function ($message, $field, $rulename, $params) use ($handler) {
                    return $handler->message($message, $field, $params);
                }
            );
        }
    }
}