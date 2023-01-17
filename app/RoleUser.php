<?php

namespace App;

class RoleUser extends Model
{
    public $table = 'role_user';
    public $timestamps = false;
    public $fillable = ['user_id', 'role_id'];
}
