<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return null;
    }

    public function view(User $user): bool
    {
        return $user->can('view_dashboard');
    }
}
