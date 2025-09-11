<?php

namespace App\Policies;

use App\Models\User;

class DashboardPolicy
{
    public function view(User $user): bool
    {
        return $user->can('view_dashboard');
    }
}
