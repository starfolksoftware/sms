<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    // RefreshDatabase trait handles migrations.

    public function test_user_without_permission_cannot_access_dashboard(): void
    {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_access_dashboard(): void
    {
        Permission::create(['name' => 'view_dashboard']);
        $role = Role::create(['name' => 'Admin']);
        $role->givePermissionTo('view_dashboard');

    $user = User::factory()->create();
    $user->assignRole('Admin');
    $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }
}
