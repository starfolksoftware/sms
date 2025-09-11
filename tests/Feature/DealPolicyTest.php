<?php

use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use App\Policies\DealPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    
    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRole('admin');
    
    $this->salesUser = User::factory()->create();
    $this->salesUser->assignRole('sales');
    
    $this->regularUser = User::factory()->create();
    // Regular user without specific permissions
    
    $this->contact = Contact::factory()->create(['created_by' => $this->salesUser->id]);
    
    $this->deal = Deal::factory()->create([
        'contact_id' => $this->contact->id,
        'created_by' => $this->salesUser->id
    ]);
    
    $this->policy = new DealPolicy();
});

test('user with view_deals permission can view any deals', function () {
    expect($this->policy->viewAny($this->salesUser))->toBeTrue();
});

test('user without view_deals permission cannot view any deals', function () {
    expect($this->policy->viewAny($this->regularUser))->toBeFalse();
});

test('user with view_deals permission can view specific deal', function () {
    expect($this->policy->view($this->salesUser, $this->deal))->toBeTrue();
});

test('user without view_deals permission cannot view specific deal', function () {
    expect($this->policy->view($this->regularUser, $this->deal))->toBeFalse();
});

test('user with manage_deals permission can create deals', function () {
    expect($this->policy->create($this->salesUser))->toBeTrue();
});

test('user without manage_deals permission cannot create deals', function () {
    expect($this->policy->create($this->regularUser))->toBeFalse();
});

test('admin can update any deal', function () {
    expect($this->policy->update($this->adminUser, $this->deal))->toBeTrue();
});

test('deal creator can update their own deal', function () {
    expect($this->policy->update($this->salesUser, $this->deal))->toBeTrue();
});

test('user with manage_deals permission but not creator cannot update deal', function () {
    $anotherSalesUser = User::factory()->create();
    $anotherSalesUser->assignRole('sales');
    
    expect($this->policy->update($anotherSalesUser, $this->deal))->toBeFalse();
});

test('user without manage_deals permission cannot update deal', function () {
    expect($this->policy->update($this->regularUser, $this->deal))->toBeFalse();
});

test('admin can delete any deal', function () {
    expect($this->policy->delete($this->adminUser, $this->deal))->toBeTrue();
});

test('deal creator can delete their own deal', function () {
    expect($this->policy->delete($this->salesUser, $this->deal))->toBeTrue();
});

test('user with manage_deals permission but not creator cannot delete deal', function () {
    $anotherSalesUser = User::factory()->create();
    $anotherSalesUser->assignRole('sales');
    
    expect($this->policy->delete($anotherSalesUser, $this->deal))->toBeFalse();
});

test('user without manage_deals permission cannot delete deal', function () {
    expect($this->policy->delete($this->regularUser, $this->deal))->toBeFalse();
});

test('admin can restore deals', function () {
    expect($this->policy->restore($this->adminUser, $this->deal))->toBeTrue();
});

test('non-admin cannot restore deals they did not create', function () {
    $anotherSalesUser = \App\Models\User::factory()->create();
    $anotherSalesUser->assignRole('sales');
    expect($this->policy->restore($anotherSalesUser, $this->deal))->toBeFalse();
});

test('deal creator can restore their own deal', function () {
    expect($this->policy->restore($this->salesUser, $this->deal))->toBeTrue();
});

test('no user can force delete deals', function () {
    expect($this->policy->forceDelete($this->adminUser, $this->deal))->toBeFalse();
    expect($this->policy->forceDelete($this->salesUser, $this->deal))->toBeFalse();
});

test('user with manage_deals permission can change stage', function () {
    expect($this->policy->changeStage($this->salesUser, $this->deal))->toBeTrue();
});

test('user without manage_deals permission cannot change stage', function () {
    expect($this->policy->changeStage($this->regularUser, $this->deal))->toBeFalse();
});

test('user with manage_deals permission can mark deal as won', function () {
    expect($this->policy->win($this->salesUser, $this->deal))->toBeTrue();
});

test('user without manage_deals permission cannot mark deal as won', function () {
    expect($this->policy->win($this->regularUser, $this->deal))->toBeFalse();
});

test('user with manage_deals permission can mark deal as lost', function () {
    expect($this->policy->lose($this->salesUser, $this->deal))->toBeTrue();
});

test('user without manage_deals permission cannot mark deal as lost', function () {
    expect($this->policy->lose($this->regularUser, $this->deal))->toBeFalse();
});