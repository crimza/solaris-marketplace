<?php

namespace App\Policies;

use App\Role;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy extends CachedPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view users list.
     *
     * @param User $self
     * @return bool
     */
    public function index(User $self): bool
    {
        return $this->remember("policy-user-index-$self->id", function () use ($self) {
            return (bool) $self->roles->filter(function ($role) {
                return in_array($role->id, [
                    Role::Admin,
                    Role::SeniorModerator,
                    Role::JuniorModerator,
                    Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can view the user.
     *
     * @param User $self
     * @return mixed
     */
    public function view(User $self): bool
    {
        return $this->remember("policy-user-view-$self->id", function () use ($self) {
            return (bool) $self->roles->filter(function ($role) {
                return in_array($role->id, [
                    Role::Admin,
                    Role::SeniorModerator,
                    Role::JuniorModerator,
                    Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can create users.
     *
     * @param User $self
     * @return mixed
     */
    public function create(User $self): bool
    {
        return $this->remember("policy-user-create-$self->id", function () use ($self) {
            return (bool) $self->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can update the user.
     *
     * @param User $self
     * @return mixed
     */
    public function update(User $self): bool
    {
        return $this->remember("policy-user-update-$self->id", function () use ($self) {
            return (bool) $self->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can delete the user.
     *
     * @param User $self
     * @return mixed
     */
    public function destroy(User $self): bool
    {
        return $this->remember("policy-user-destroy-$self->id", function () use ($self) {
            return (bool) $self->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SecurityService]);
            })->count();
        });
    }
}
