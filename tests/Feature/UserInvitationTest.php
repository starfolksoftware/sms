<?php

use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

it('sends a notification when inviting a user', function () {
    Notification::fake();

    // acting user with permission
    $admin = User::factory()->create();
    Role::findOrCreate('admin');
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->post(route('admin.users.invite'), [
        'name' => 'Invited User',
        'email' => 'invitee@example.com',
        'roles' => [],
    ]);

    $response->assertRedirect();

    $invited = User::where('email', 'invitee@example.com')->first();
    expect($invited)->not->toBeNull();
    expect($invited->status->value)->toBe('pending_invite');

    Notification::assertSentTo($invited, UserInvitationNotification::class);
});

it('sends a notification when creating with invitation checked', function () {
    Notification::fake();

    $admin = User::factory()->create();
    Role::findOrCreate('admin');
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $response = $this->post(route('admin.users.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'send_invitation' => true,
        'roles' => [],
    ]);

    $response->assertRedirect();

    $newUser = User::where('email', 'new@example.com')->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->status->value)->toBe('pending_invite');

    Notification::assertSentTo($newUser, UserInvitationNotification::class);
});
