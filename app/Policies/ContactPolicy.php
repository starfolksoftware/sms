<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_clients') || $user->can('view_contacts');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contact $contact): bool
    {
        return $user->can('view_clients') || $user->can('view_contacts');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage_clients') || $user->can('create_contacts');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contact $contact): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        // manage_clients is broad (can update any)
        if ($user->can('manage_clients')) {
            return true;
        }

        // manage_contacts or edit_contacts can update only own contacts
        if ($user->can('manage_contacts') || $user->can('edit_contacts')) {
            return $contact->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contact $contact): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }
        if ($user->can('manage_clients')) {
            return true;
        }

        if ($user->can('manage_contacts') || $user->can('delete_contacts')) {
            return $contact->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Contact $contact): bool
    {
        return $user->can('manage_clients');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Contact $contact): bool
    {
        return false;
    }
}
