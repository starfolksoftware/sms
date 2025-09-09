<?php

use App\Events\DataExported;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('logs data export events', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Simulate a data export
    event(new DataExported(
        module: 'contacts',
        filters: ['status' => 'active', 'created_at' => '2024-01-01'],
        recordCount: 150,
        format: 'csv',
        exportPath: 'exports/contacts-2024-01-01.csv'
    ));
    
    // Check that export activity was logged
    $activity = Activity::where('log_name', 'data_ops')
        ->where('description', 'data_exported')
        ->where('causer_id', $user->id)
        ->first();
    
    expect($activity)->not->toBeNull();
    expect($activity->properties->get('module'))->toBe('contacts');
    expect($activity->properties->get('record_count'))->toBe(150);
    expect($activity->properties->get('format'))->toBe('csv');
    expect($activity->properties->get('export_path'))->toBe('exports/contacts-2024-01-01.csv');
    expect($activity->properties->get('filters'))->toMatchArray([
        'status' => 'active',
        'created_at' => '2024-01-01'
    ]);
});

it('logs export events with different formats', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Test Excel export
    event(new DataExported(
        module: 'deals',
        filters: ['value' => '>1000'],
        recordCount: 25,
        format: 'xlsx'
    ));
    
    $activity = Activity::where('log_name', 'data_ops')
        ->where('description', 'data_exported')
        ->where('causer_id', $user->id)
        ->first();
    
    expect($activity)->not->toBeNull();
    expect($activity->properties->get('module'))->toBe('deals');
    expect($activity->properties->get('format'))->toBe('xlsx');
    expect($activity->properties->get('record_count'))->toBe(25);
});

it('logs export events without export path', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Simulate export that doesn't save to file (direct download)
    event(new DataExported(
        module: 'tasks',
        filters: ['priority' => 'high'],
        recordCount: 5,
        format: 'json'
    ));
    
    $activity = Activity::where('log_name', 'data_ops')
        ->where('description', 'data_exported')
        ->where('causer_id', $user->id)
        ->first();
    
    expect($activity)->not->toBeNull();
    expect($activity->properties->get('export_path'))->toBeNull();
    expect($activity->properties->get('module'))->toBe('tasks');
    expect($activity->properties->get('format'))->toBe('json');
});

it('includes request metadata in export logs', function () {
    $user = User::factory()->create();
    
    // Simulate request with specific IP and user agent
    $this->actingAs($user)
        ->withHeaders([
            'User-Agent' => 'Mozilla/5.0 Test Browser',
            'X-Forwarded-For' => '192.168.1.100',
        ])
        ->withServerVariables(['REMOTE_ADDR' => '192.168.1.100']);
    
    event(new DataExported(
        module: 'products',
        filters: [],
        recordCount: 100,
        format: 'csv'
    ));
    
    $activity = Activity::where('log_name', 'data_ops')
        ->where('description', 'data_exported')
        ->first();
    
    expect($activity)->not->toBeNull();
    expect($activity->properties->get('ip'))->toBe('192.168.1.100');
    expect($activity->properties->get('user_agent'))->toBe('Mozilla/5.0 Test Browser');
});
