<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
        Filament::setCurrentPanel('admin');

        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $user->givePermissionTo(['view_dashboard', 'view_deals']);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertStatus(200);
    }

    public function test_admin_can_access_deals(): void
    {
        Filament::setCurrentPanel('admin');

        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $user->givePermissionTo(['view_dashboard', 'view_deals']);

        $this->actingAs($user);

        Livewire::test(\App\Filament\Resources\DealResource\Pages\ListDeals::class)
            ->assertStatus(200);
    }
}
