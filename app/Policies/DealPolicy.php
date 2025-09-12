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

    public function viewAny(User $user): bool { return $user->can('view_deals'); }
    public function view(User $user, Deal $deal): bool { return $user->can('view_deals'); }
    public function create(User $user): bool { return $user->can('manage_deals'); }
    public function update(User $user, Deal $deal): bool { return $user->can('manage_deals'); }
    public function delete(User $user, Deal $deal): bool { return $user->can('manage_deals'); }
    public function restore(User $user, Deal $deal): bool { return $user->can('manage_deals'); }
    public function forceDelete(User $user, Deal $deal): bool { return false; }

    public function changeStage(User $user, Deal $deal): bool { return $user->can('manage_deals') && ! in_array($deal->status, ['won','lost']); }
    public function win(User $user, Deal $deal): bool { return $user->can('manage_deals') && $deal->status === 'open'; }
    public function lose(User $user, Deal $deal): bool { return $user->can('manage_deals') && $deal->status === 'open'; }
}
