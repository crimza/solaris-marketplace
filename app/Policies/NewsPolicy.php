<?php

namespace App\Policies;

use App\Role;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NewsPolicy extends CachedPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view news list.
     *
     * @param User $user
     * @return bool
     */
    public function index(User $user): bool
    {
        return $this->remember("policy-news-index-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can view the news.
     *
     * @param User $user
     * @return bool
     */
    public function view(User $user): bool
    {
        return $this->remember("policy-news-view-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can create news.
     *
     * @param  User  $user
     * @return bool
     */
    public function create(User $user): bool
    {
        return $this->remember("policy-news-create-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can update the news.
     *
     * @param User $user
     * @return bool
     */
    public function update(User $user): bool
    {
        return $this->remember("policy-news-update-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can delete the news.
     *
     * @param User $user
     * @return bool
     */
    public function destroy(User $user): bool
    {
        return $this->remember("policy-news-destroy-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }
}
