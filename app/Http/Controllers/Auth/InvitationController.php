<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $token = (string) $request->query('token', '');

        if (! $token) {
            return redirect()->route('login')->withErrors(['token' => 'Invalid invitation link.']);
        }

        $user = User::where('invitation_token', $token)->first();
        if (! $user || ! $user->isPendingInvite()) {
            return redirect()->route('login')->withErrors(['token' => 'Invitation link is invalid or expired.']);
        }

        return Inertia::render('auth/AcceptInvitation', [
            'email' => $user->email,
            'token' => $token,
        ]);
    }

    public function accept(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::where('invitation_token', $validated['token'])->first();
        if (! $user || ! $user->isPendingInvite()) {
            return back()->withErrors(['token' => 'Invitation link is invalid or expired.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        $user->acceptInvitation();

        return redirect()->route('login')->with('status', 'Invitation accepted. You can now sign in.');
    }
}
