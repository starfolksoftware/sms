<?php

use Spatie\Activitylog\Models\Activity;

it('prunes audit logs older than retention period', function () {
    // Create old activities (older than 180 days)
    $oldActivity = Activity::create([
        'log_name' => 'security',
        'description' => 'old_login',
        'created_at' => now()->subDays(200),
        'updated_at' => now()->subDays(200),
    ]);
    
    // Create recent activities (within retention period)  
    $recentActivity = Activity::create([
        'log_name' => 'security',
        'description' => 'recent_login',
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDays(100),
    ]);
    
    expect(Activity::count())->toBe(2);
    
    // Run the prune command
    $this->artisan('audit:prune')
        ->expectsOutputToContain('Pruning audit logs older than 180 days')
        ->expectsOutputToContain('Deleted 1 audit log entries.')
        ->assertExitCode(0);
    
    // Verify old activity was deleted but recent one remains
    expect(Activity::count())->toBe(1);
    expect(Activity::find($oldActivity->id))->toBeNull();
    expect(Activity::find($recentActivity->id))->not->toBeNull();
});

it('allows custom retention period via command option', function () {
    // Create activities at different ages
    $veryOldActivity = Activity::create([
        'log_name' => 'data_ops',
        'description' => 'very_old',
        'created_at' => now()->subDays(100),
        'updated_at' => now()->subDays(100),
    ]);
    
    $recentActivity = Activity::create([
        'log_name' => 'data_ops',
        'description' => 'recent',
        'created_at' => now()->subDays(30),
        'updated_at' => now()->subDays(30),
    ]);
    
    expect(Activity::count())->toBe(2);
    
    // Run prune with 50 day retention
    $this->artisan('audit:prune --days=50')
        ->expectsOutputToContain('Pruning audit logs older than 50 days')
        ->expectsOutputToContain('Deleted 1 audit log entries.')
        ->assertExitCode(0);
    
    // Verify only the 100-day old activity was deleted
    expect(Activity::count())->toBe(1);
    expect(Activity::find($veryOldActivity->id))->toBeNull();
    expect(Activity::find($recentActivity->id))->not->toBeNull();
});

it('handles empty audit log table gracefully', function () {
    expect(Activity::count())->toBe(0);
    
    $this->artisan('audit:prune')
        ->expectsOutputToContain('Deleted 0 audit log entries.')
        ->assertExitCode(0);
});

it('respects configuration file retention setting', function () {
    // Temporarily change config
    config(['activitylog.delete_records_older_than_days' => 90]);
    
    $oldActivity = Activity::create([
        'log_name' => 'security',
        'description' => 'old_activity',
        'created_at' => now()->subDays(120),
        'updated_at' => now()->subDays(120),
    ]);
    
    $recentActivity = Activity::create([
        'log_name' => 'security',
        'description' => 'recent_activity',
        'created_at' => now()->subDays(60),
        'updated_at' => now()->subDays(60),
    ]);
    
    expect(Activity::count())->toBe(2);
    
    $this->artisan('audit:prune')
        ->expectsOutputToContain('Pruning audit logs older than 90 days')
        ->expectsOutputToContain('Deleted 1 audit log entries.')
        ->assertExitCode(0);
    
    expect(Activity::count())->toBe(1);
    expect(Activity::find($recentActivity->id))->not->toBeNull();
});

it('preserves activities created exactly at retention boundary', function () {
    $boundaryActivity = Activity::create([
        'log_name' => 'security',
        'description' => 'boundary_activity',
        'created_at' => now()->subDays(180)->addMinute(), // Just within boundary
        'updated_at' => now()->subDays(180)->addMinute(),
    ]);
    
    $this->artisan('audit:prune')
        ->assertExitCode(0);
    
    // Activity should still exist (not deleted)
    expect(Activity::find($boundaryActivity->id))->not->toBeNull();
});

it('can handle large volumes of old activities', function () {
    // Create multiple old activities efficiently
    $oldActivityIds = collect(range(1, 10))->map(function () {
        return Activity::create([
            'log_name' => 'data_ops',
            'description' => 'bulk_old_activity',
            'created_at' => now()->subDays(200),
            'updated_at' => now()->subDays(200),
        ])->id;
    });
    
    $recentActivity = Activity::create([
        'log_name' => 'security',
        'description' => 'recent_activity',
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);
    
    expect(Activity::count())->toBe(11);
    
    $this->artisan('audit:prune')
        ->expectsOutputToContain('Deleted 10 audit log entries.')
        ->assertExitCode(0);
    
    expect(Activity::count())->toBe(1);
    expect(Activity::find($recentActivity->id))->not->toBeNull();
});
