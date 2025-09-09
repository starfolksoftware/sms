<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $query = User::with('roles');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->get('role')) {
            $query->role($request->get('role'));
        }

        // Filter by status
        if ($request->has('status') && $request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        // Sort
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');

        $allowedSorts = ['name', 'email', 'created_at', 'last_login_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::all();

        return Inertia::render('admin/Users', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search', 'role', 'status', 'sort', 'direction']),
        ]);
    }

    /**
     * Store a newly created user (direct create).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
            'send_invitation' => 'boolean',
        ]);

        if ($validated['send_invitation'] ?? false) {
            // Create user with pending invite status
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(32)), // Temporary password
            ]);

            $user->markAsPendingInvite();
        } else {
            // Create user directly with temporary password (force reset)
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(32)), // Temporary password
                'status' => UserStatus::Active,
            ]);
        }

        // Assign roles
        if (! empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        return back()->with('message', 'User created successfully.');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'status' => ['in:'.implode(',', UserStatus::values())],
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        // Prevent admin from deactivating themselves
        if ($user->id === Auth::id() && isset($validated['status']) && $validated['status'] === UserStatus::Deactivated->value) {
            return back()->withErrors(['status' => 'Cannot deactivate your own account.']);
        }

        $user->update($validated);

        // Update roles if provided
        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return back()->with('message', 'User updated successfully.');
    }

    /**
     * Invite a user by email.
     */
    public function invite(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'roles' => 'array',
            'roles.*' => 'exists:roles,name',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)), // Temporary password
        ]);

        $user->markAsPendingInvite();

        // Assign roles
        if (! empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }

        return back()->with('message', 'User invitation sent successfully.');
    }

    /**
     * Resend invitation.
     */
    public function resendInvite(User $user): RedirectResponse
    {
        if (! $user->isPendingInvite()) {
            return back()->withErrors(['user' => 'User is not pending invitation.']);
        }

        $user->markAsPendingInvite(); // Updates invitation_sent_at

        return back()->with('message', 'Invitation resent successfully.');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        // Prevent admin from deleting themselves
        if ($user->id === Auth::id()) {
            return back()->withErrors(['user' => 'Cannot delete your own account.']);
        }

        // Prevent deletion of the last admin user
        $adminRole = Role::where('name', 'admin')->first();
        if ($user->hasRole('admin') && $adminRole && $adminRole->users()->count() <= 1) {
            return back()->withErrors(['user' => 'Cannot delete the last admin user.']);
        }

        $user->delete();

        return back()->with('message', 'User deleted successfully.');
    }
}
