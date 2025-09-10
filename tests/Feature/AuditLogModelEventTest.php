<?php

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\Task;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('logs contact deletion events', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $contact = Contact::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'company' => 'Example Corp',
        'created_by' => $user->id,
    ]);

    $contact->delete();

    // Check that delete activity was logged
    $activity = Activity::where('log_name', 'data_ops')
        ->where('event', 'deleted')
        ->where('subject_type', Contact::class)
        ->where('subject_id', $contact->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($user->id);

    // Check the actual structure of properties
    $properties = $activity->properties->all();

    // For deleted model, the actual attributes should be in the top level or in 'old' key
    $attributes = $properties['old'] ?? $properties;
    expect($attributes)->toHaveKey('name', 'John Doe');
    expect($attributes)->toHaveKey('email', 'john@example.com');
    expect($attributes)->toHaveKey('company', 'Example Corp');
});

it('logs deal deletion events', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $contact = Contact::factory()->create();
    $deal = Deal::factory()->create([
        'title' => 'Big Deal',
        'value' => '10000.00',
        'status' => 'open',
        'contact_id' => $contact->id,
        'created_by' => $user->id,
    ]);

    $deal->delete();

    // Check that delete activity was logged
    $activity = Activity::where('log_name', 'data_ops')
        ->where('event', 'deleted')
        ->where('subject_type', Deal::class)
        ->where('subject_id', $deal->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($user->id);

    $attributes = $activity->properties->get('old');
    expect($attributes)->toHaveKey('title', 'Big Deal');
    expect($attributes)->toHaveKey('value', '10000.00');
    expect($attributes)->toHaveKey('status', 'open');
});

it('logs task deletion events', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create([
        'title' => 'Important Task',
        'status' => 'pending',
        'priority' => 'high',
        'created_by' => $user->id,
    ]);

    $task->delete();

    // Check that delete activity was logged
    $activity = Activity::where('log_name', 'data_ops')
        ->where('event', 'deleted')
        ->where('subject_type', Task::class)
        ->where('subject_id', $task->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($user->id);

    $attributes = $activity->properties->get('old');
    expect($attributes)->toHaveKey('title', 'Important Task');
    expect($attributes)->toHaveKey('status', 'pending');
    expect($attributes)->toHaveKey('priority', 'high');
});

it('logs product deletion events', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $product = Product::factory()->create([
        'name' => 'Premium Product',
        'sku' => 'PREM-001',
        'price' => '99.99',
        'active' => true,
        'created_by' => $user->id,
    ]);

    $product->delete();

    // Check that delete activity was logged
    $activity = Activity::where('log_name', 'data_ops')
        ->where('event', 'deleted')
        ->where('subject_type', Product::class)
        ->where('subject_id', $product->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_id)->toBe($user->id);

    $attributes = $activity->properties->get('old');
    expect($attributes)->toHaveKey('name', 'Premium Product');
    expect($attributes)->toHaveKey('sku', 'PREM-001');
    expect($attributes)->toHaveKey('price', '99.99');
    expect($attributes)->toHaveKey('active', true);
});

it('does not log empty activities when no significant changes occur', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $contact = Contact::factory()->create(['created_by' => $user->id]);

    // Clear existing activities from creation
    Activity::truncate();

    // Make a change that's not tracked (timestamps)
    $contact->touch();

    // Should not create activity log since no tracked fields changed
    $activity = Activity::where('subject_type', Contact::class)
        ->where('subject_id', $contact->id)
        ->where('event', 'updated')
        ->first();

    expect($activity)->toBeNull();
});
