<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) { return true; }
        return null;
    }

    public function viewAny(User $user): bool { return $user->can('view_dashboard'); }
    public function view(User $user, Product $product): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->can('manage_tasks'); }
    public function update(User $user, Product $product): bool { return $user->can('manage_tasks'); }
    public function delete(User $user, Product $product): bool { return false; }
    public function restore(User $user, Product $product): bool { return false; }
    public function forceDelete(User $user, Product $product): bool { return false; }
}
