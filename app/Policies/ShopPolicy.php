<?php

namespace App\Policies;

use App\Role;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ShopPolicy extends CachedPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view shops list.
     *
     * @param User $user
     * @return bool
     */
    public function index(User $user): bool
    {
        return $this->remember("policy-shop-index-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SeniorModerator]);
            })->count();
        });
    }

    /**
     * Determine whether the user can view the shop.
     *
     * @param User $user
     * @return mixed
     */
    public function view(User $user): bool
    {
        return $this->remember("policy-shop-view-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SeniorModerator]);
            })->count();
        });
    }

    /**
     * Determine whether the user can create shops.
     *
     * @param User $user
     * @return mixed
     */
    public function create(User $user): bool
    {
        return $this->remember("policy-shop-view-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can update the shop.
     *
     * @param User $user
     * @return mixed
     */
    public function update(User $user): bool
    {
        return $this->remember("policy-shop-update-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can delete the shop.
     *
     * @param User $user
     * @return mixed
     */
    public function destroy(User $user): bool
    {
        return $this->remember("policy-shop-destroy-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }
}
