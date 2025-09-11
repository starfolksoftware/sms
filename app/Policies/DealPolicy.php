<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;

class DealPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) { return true; }
        return null;
    }

    public function viewAny(User $user): bool { return $user->can('manage_clients') || $user->can('view_dashboard'); }
    public function view(User $user, Deal $deal): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->can('manage_clients'); }
    public function update(User $user, Deal $deal): bool { return $user->can('manage_clients') && ($deal->owner_id === null || $deal->owner_id === $user->id); }
    public function delete(User $user, Deal $deal): bool { return $user->can('manage_clients'); }
    public function restore(User $user, Deal $deal): bool { return false; }
    public function forceDelete(User $user, Deal $deal): bool { return false; }
    public function markWon(User $user, Deal $deal): bool { return $this->update($user, $deal); }
}
