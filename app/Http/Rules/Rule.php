<?php

namespace App\Http\Rules;

abstract class Rule
{
    /**
    * Returns true if given value of attribute is valid according current rule.
    *
    * @param  string  $field
    * @param  mixed  $value
    * @param  array  $params
    * @return bool
    */
    abstract public function validate(string $field, $value, array $params);

    /**
    * Prepares failed validation message to be displayed for user.
    *
    * @param  array|string  $message
    * @param  string  $field
    * @param  array  $params
    * @return array|string
    */
    public function message($message, string $field, array $params)
    {
        return $message;
    }
}