<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool { return $user->can('manage_clients') || $user->can('view_dashboard'); }
    public function view(User $user, Contact $contact): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->can('manage_clients'); }
    public function update(User $user, Contact $contact): bool { return $user->can('manage_clients') && ($contact->owner_id === null || $contact->owner_id === $user->id); }
    public function delete(User $user, Contact $contact): bool { return $user->can('manage_clients'); }
    public function restore(User $user, Contact $contact): bool { return false; }
    public function forceDelete(User $user, Contact $contact): bool { return false; }
    public function convert(User $user, Contact $contact): bool { return $this->update($user, $contact); }
}
