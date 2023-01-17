<?php

namespace App\Policies;

use App\Models\Tickets\Message;
use App\Role;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TicketPolicy extends CachedPolicy
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
        return $this->remember("policy-ticket-index-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [
                    Role::Admin,
                    Role::SeniorModerator,
                    Role::JuniorModerator,
                    Role::SecurityService]);
            })->count();
        });
    }

    /**
     * Determine whether the user can delete the ticket.
     *
     * @param User $user
     * @return bool
     */
    public function destroy(User $user): bool
    {
        return $this->remember("policy-ticket-destroy-$user->id", function () use ($user) {
            return (bool) $user->roles->filter(function ($role) {
                return in_array($role->id, [Role::Admin, Role::SeniorModerator]);
            })->count();
        });
    }

    /**
     * Determine whether the user can delete the message.
     *
     * @param User $user
     * @param Message $message
     * @return bool
     */
    public function destroyMessage(User $user, Message $message): bool
    {
        return (bool) $user->roles->filter(function ($role) use ($user, $message) {
            return ($user->id === $message->user_id && $role->id === Role::JuniorModerator) || in_array($role->id, [
                Role::Admin, Role::SeniorModerator, Role::SecurityService]);
        })->count();
    }
}
