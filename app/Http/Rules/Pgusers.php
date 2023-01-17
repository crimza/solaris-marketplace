<?php

namespace App\Http\Rules;

use App\User;

class Pgusers extends Rule
{
    public function validate(string $field, $value, array $params)
    {
        $user = User::where('username', 'ilike', $value)->first();

        return !$user;
    }
}