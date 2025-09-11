<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('Admin')) { return true; }
        return null;
    }

    public function viewAny(User $user): bool { return $user->can('manage_tasks') || $user->can('view_dashboard'); }
    public function view(User $user, Task $task): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->can('manage_tasks'); }
    public function update(User $user, Task $task): bool { return $user->can('manage_tasks') && ($task->creator_id === $user->id || $task->assignee_id === $user->id); }
    public function delete(User $user, Task $task): bool { return $user->can('manage_tasks'); }
    public function restore(User $user, Task $task): bool { return false; }
    public function forceDelete(User $user, Task $task): bool { return false; }
}
