<?php

use App\Models\Contact;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('allows admin users to view audit logs', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    // Create some audit log entries
    $user = User::factory()->create();
    $contact = Contact::factory()->create(['created_by' => $user->id]);
    
    activity('data_ops')
        ->causedBy($user)
        ->performedOn($contact)
        ->log('contact_created');
    
    // Admin should be able to access audit logs
    $response = $this->actingAs($admin)
        ->getJson('/admin/audit-logs');
    
    $response->assertOk();
    $response->assertJsonStructure([
        'message',
        'data' => [
            'data' => [
                '*' => [
                    'id',
                    'log_name',
                    'description',
                    'subject_type',
                    'event',
                    'subject_id',
                    'causer_type',
                    'causer_id',
                    'properties',
                    'created_at',
                ],
            ],
            'total',
            'per_page',
            'current_page',
        ],
        'filters',
    ]);
});

it('denies non-admin users access to audit logs', function () {
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');
    
    $response = $this->actingAs($salesUser)
        ->getJson('/admin/audit-logs');
    
    $response->assertForbidden();
});

it('denies unauthenticated users access to audit logs', function () {
    $response = $this->getJson('/admin/audit-logs');
    
    $response->assertUnauthorized();
});

it('filters audit logs by log_name', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    // Create activities with different log names
    activity('security')->log('user_login');
    activity('data_ops')->log('contact_deleted');
    
    $response = $this->actingAs($admin)
        ->getJson('/admin/audit-logs?log_name=security');
    
    $response->assertOk();
    
    $activities = $response->json('data.data');
    expect($activities)->toHaveCount(1);
    expect($activities[0]['log_name'])->toBe('security');
});

it('filters audit logs by event', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    // Create activities with different events
    activity('data_ops')->log('contact_created');
    activity('data_ops')->log('contact_deleted');
    
    $response = $this->actingAs($admin)
        ->getJson('/admin/audit-logs?event=contact_created');
    
    $response->assertOk();
    
    $activities = $response->json('data.data');
    expect($activities)->toHaveCount(1);
    expect($activities[0]['description'])->toBe('contact_created');
});

it('filters audit logs by causer', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    // Create activities by different users
    activity('data_ops')->causedBy($user1)->log('action_by_user1');
    activity('data_ops')->causedBy($user2)->log('action_by_user2');
    
    $response = $this->actingAs($admin)
        ->getJson("/admin/audit-logs?causer_type=App\\Models\\User&causer_id={$user1->id}");
    
    $response->assertOk();
    
    $activities = $response->json('data.data');
    expect($activities)->toHaveCount(1);
    expect($activities[0]['causer_id'])->toBe($user1->id);
});

it('shows individual audit log entry details', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    
    $activity = activity('data_ops')
        ->causedBy($user)
        ->performedOn($contact)
        ->withProperties(['key' => 'value'])
        ->log('test_action');
    
    $response = $this->actingAs($admin)
        ->getJson("/admin/audit-logs/{$activity->id}");
    
    $response->assertOk();
    $response->assertJsonStructure([
        'message',
        'data' => [
            'id',
            'log_name',
            'description',
            'subject_type',
            'subject_id',
            'causer_type',
            'causer_id',
            'properties',
            'created_at',
            'causer',
            'subject',
        ],
    ]);
    
    $activityData = $response->json('data');
    expect($activityData['id'])->toBe($activity->id);
    expect($activityData['description'])->toBe('test_action');
    expect($activityData['properties'])->toMatchArray(['key' => 'value']);
});

it('returns 404 for non-existent audit log entry', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $response = $this->actingAs($admin)
        ->getJson('/admin/audit-logs/99999');
    
    $response->assertNotFound();
});

it('includes filter options in audit log index response', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    // Create activities with different log names and events
    activity('security')->log('user_login');
    activity('data_ops')->log('contact_created');
    activity('data_ops')->log('contact_deleted');
    
    $response = $this->actingAs($admin)
        ->getJson('/admin/audit-logs');
    
    $response->assertOk();
    
    $filters = $response->json('filters');
    expect($filters['log_names'])->toContain('security', 'data_ops');
    expect($filters['events'])->toContain('user_login', 'contact_created', 'contact_deleted');
});
