<?php

namespace App\Policies;

use App\Role;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy extends CachedPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view categories list.
     *
     * @param User $user
     * @return bool
     */
    public function index(User $user): bool
    {
        return $this->remember("policy-category-index-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can view the category.
     *
     * @param User $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return $this->remember("policy-category-view-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can create categories.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->remember("policy-category-create-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can update the category.
     *
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $this->remember("policy-category-update-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can delete the category.
     *
     * @param User $user
     * @return bool
     */
    public function destroy(User $user): bool
    {
        return $this->remember("policy-category-destroy-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }
}
