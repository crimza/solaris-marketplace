<?php

namespace App\Policies;

use App\Role;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CityPolicy extends CachedPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view cities list.
     *
     * @param User $user
     * @return bool
     */
    public function index(User $user): bool
    {
        return $this->remember("policy-city-index-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can view the city.
     *
     * @param User $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return $this->remember("policy-city-view-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can create cities.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->remember("policy-city-create-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can update the city.
     *
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $this->remember("policy-city-update-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can delete the city.
     *
     * @param User $user
     * @return bool
     */
    public function destroy(User $user): bool
    {
        return $this->remember("policy-city-destroy-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }
}
