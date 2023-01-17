<?php

namespace App\Policies;

use App\Role;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GoodPolicy extends CachedPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the goods list.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function index(User $user): bool
    {
        return $this->remember("policy-good-index-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can view the good.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return $this->remember("policy-good-view-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can create goods.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->remember("policy-good-create-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can update the good.
     *
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $this->remember("policy-good-update-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can delete the good.
     *
     * @param User $user
     * @return bool
     */
    public function destroy(User $user): bool
    {
        return $this->remember("policy-good-destroy-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }
}
