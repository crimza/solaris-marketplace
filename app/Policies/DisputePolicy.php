<?php

namespace App\Policies;

use App\Role;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DisputePolicy extends CachedPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view tickets list.
     *
     * @param User $user
     * @return bool
     */
    public function index(User $user): bool
    {
        return $this->remember("policy-dispute-index-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [
                    Role::Admin,
                    Role::SeniorModerator,
                    Role::JuniorModerator,
                    Role::SecurityService]);
            })->count();
        });
    }
}