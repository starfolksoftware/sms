<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_deals');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Deal $deal): bool
    {
        return $user->can('view_deals');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_deals');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Deal $deal): bool
    {
        if (! $user->can('edit_deals')) {
            return false;
        }

        // Allow if user is admin or creator
        return $user->hasRole('admin') || $deal->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Deal $deal): bool
    {
        if (! $user->can('delete_deals')) {
            return false;
        }

        // Allow if user is admin or creator
        return $user->hasRole('admin') || $deal->created_by === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Deal $deal): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Deal $deal): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can change the deal stage.
     */
    public function changeStage(User $user, Deal $deal): bool
    {
        if (! $user->can('edit_deals')) {
            return false;
        }

        // Allow if user is admin or creator
        return $user->hasRole('admin') || $deal->created_by === $user->id;
    }

    /**
     * Determine whether the user can mark the deal as won.
     */
    public function win(User $user, Deal $deal): bool
    {
        if (! $user->can('edit_deals')) {
            return false;
        }

        // Allow if user is admin or creator
        return $user->hasRole('admin') || $deal->created_by === $user->id;
    }

    /**
     * Determine whether the user can mark the deal as lost.
     */
    public function lose(User $user, Deal $deal): bool
    {
        if (! $user->can('edit_deals')) {
            return false;
        }

        // Allow if user is admin or creator
        return $user->hasRole('admin') || $deal->created_by === $user->id;
    }
}
