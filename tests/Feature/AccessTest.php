<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $user->givePermissionTo(['view_dashboard', 'view_deals']);

        $this->actingAs($user)
            ->get('/')
            ->assertOk();
    }

    public function test_admin_can_access_deals(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $user->givePermissionTo(['view_dashboard', 'view_deals']);

        $this->actingAs($user)
            ->get('/deals')
            ->assertOk();
    }
}