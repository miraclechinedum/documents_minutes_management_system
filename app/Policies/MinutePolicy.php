<?php

namespace App\Policies;

use App\Models\Minute;
use App\Models\User;

class MinutePolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Minute $minute): bool
    {
        if (!$user->hasPermissionTo('minutes.view')) {
            return false;
        }

        return $minute->canViewBy($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('minutes.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Minute $minute): bool
    {
        // Only creator or admin can edit minutes
        return $minute->created_by === $user->id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Minute $minute): bool
    {
        // Only creator or admin can delete minutes
        return $minute->created_by === $user->id || $user->hasRole('admin');
    }
}