<?php

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Spatie\Activitylog\Models\Activity;

it('logs successful login events', function () {
    $user = User::factory()->create();
    
    // Simulate a login event
    event(new Login('web', $user, false));
    
    // Check that activity was logged
    $activity = Activity::where('log_name', 'security')
        ->where('description', 'user_login')
        ->where('causer_id', $user->id)
        ->first();
    
    expect($activity)->not->toBeNull();
    expect($activity->properties->get('ip'))->not->toBeNull();
    expect($activity->properties->get('user_agent'))->not->toBeNull();
    expect($activity->properties->get('remember'))->toBeFalse();
});

it('logs successful logout events', function () {
    $user = User::factory()->create();
    
    // Act as the user first
    $this->actingAs($user);
    
    // Simulate a logout event
    event(new Logout('web', $user));
    
    // Check that activity was logged
    $activity = Activity::where('log_name', 'security')
        ->where('description', 'user_logout')
        ->where('causer_id', $user->id)
        ->first();
    
    expect($activity)->not->toBeNull();
    expect($activity->properties->get('ip'))->not->toBeNull();
    expect($activity->properties->get('user_agent'))->not->toBeNull();
});

it('logs failed login attempts', function () {
    $user = User::factory()->create();
    
    // Simulate a failed login event
    $credentials = ['email' => $user->email, 'password' => 'wrong-password'];
    event(new Failed('web', $user, $credentials));
    
    // Check that activity was logged
    $activity = Activity::where('log_name', 'security')
        ->where('description', 'login_failed')
        ->first();
    
    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($user->id);
    expect($activity->properties->get('email'))->toBe($user->email);
    expect($activity->properties->get('guard'))->toBe('web');
});

it('logs failed login attempts even without user', function () {
    // Simulate a failed login event with no user found
    $credentials = ['email' => 'nonexistent@example.com', 'password' => 'wrong-password'];
    event(new Failed('web', null, $credentials));
    
    // Check that activity was logged
    $activity = Activity::where('log_name', 'security')
        ->where('description', 'login_failed')
        ->whereNull('causer_id')
        ->first();
    
    expect($activity)->not->toBeNull();
    expect($activity->properties->get('email'))->toBe('nonexistent@example.com');
    expect($activity->properties->get('guard'))->toBe('web');
});

it('updates last_login_at timestamp on successful login', function () {
    $user = User::factory()->create(['last_login_at' => null]);
    
    // Simulate a login event
    event(new Login('web', $user, false));
    
    // Refresh the user model
    $user->refresh();
    
    expect($user->last_login_at)->not->toBeNull();
});
