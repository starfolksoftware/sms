<?php

use App\Models\Contact;
use App\Models\User;
use App\Policies\ContactPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('admin can perform any action on contacts', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $contact = Contact::factory()->create();
    $policy = new ContactPolicy();

    expect($policy->viewAny($admin))->toBeTrue();
    expect($policy->view($admin, $contact))->toBeTrue();
    expect($policy->create($admin))->toBeTrue();
    expect($policy->update($admin, $contact))->toBeTrue();
    expect($policy->delete($admin, $contact))->toBeTrue();
});

test('sales user can view and manage contacts they created', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    
    $salesUser = User::factory()->create();
    $salesUser->assignRole('sales');
    
    $ownContact = Contact::factory()->create(['created_by' => $salesUser->id]);
    $otherContact = Contact::factory()->create();
    
    $policy = new ContactPolicy();

    // Can view any and create
    expect($policy->viewAny($salesUser))->toBeTrue();
    expect($policy->view($salesUser, $ownContact))->toBeTrue();
    expect($policy->create($salesUser))->toBeTrue();
    
    // Can update/delete own contacts
    expect($policy->update($salesUser, $ownContact))->toBeTrue();
    expect($policy->delete($salesUser, $ownContact))->toBeTrue();
    
    // Cannot update/delete others' contacts
    expect($policy->update($salesUser, $otherContact))->toBeFalse();
    expect($policy->delete($salesUser, $otherContact))->toBeFalse();
});

test('marketing user cannot access contacts', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    
    $marketingUser = User::factory()->create();
    $marketingUser->assignRole('marketing');
    
    $contact = Contact::factory()->create();
    $policy = new ContactPolicy();

    expect($policy->viewAny($marketingUser))->toBeFalse();
    expect($policy->view($marketingUser, $contact))->toBeFalse();
    expect($policy->create($marketingUser))->toBeFalse();
    expect($policy->update($marketingUser, $contact))->toBeFalse();
    expect($policy->delete($marketingUser, $contact))->toBeFalse();
});

test('user without permissions cannot access contacts', function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    
    $user = User::factory()->create();
    $contact = Contact::factory()->create();
    $policy = new ContactPolicy();

    expect($policy->viewAny($user))->toBeFalse();
    expect($policy->view($user, $contact))->toBeFalse();
    expect($policy->create($user))->toBeFalse();
    expect($policy->update($user, $contact))->toBeFalse();
    expect($policy->delete($user, $contact))->toBeFalse();
});
