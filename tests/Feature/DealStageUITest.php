<?php

namespace Tests\Feature;

use App\Models\DealStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealStageUITest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            \Database\Seeders\RolesAndPermissionsSeeder::class,
            \Database\Seeders\DealStageSeeder::class,
        ]);
    }

    public function test_admin_can_access_deal_stages_page(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|mixed $user */
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $response = $this->actingAs($user)->get('/deal-stages');

        $response->assertStatus(200);
        $response->assertSee('Deal Stages');
    }

    public function test_non_admin_cannot_access_deal_stages_page(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|mixed $user */
        $user = User::factory()->create();
        // User without manage_stages permission

        $response = $this->actingAs($user)->get('/deal-stages');

        $response->assertStatus(403);
    }

    public function test_can_create_new_deal_stage(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|mixed $user */
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->actingAs($user);

        Livewire::test(\App\Filament\Resources\DealStages\Pages\ManageDealStages::class)
            ->callAction('create', [
                'name' => 'Demo Scheduled',
                'slug' => 'demo-scheduled',
                'order' => 10,
                'is_active' => true,
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('deal_stages', [
            'name' => 'Demo Scheduled',
            'slug' => 'demo-scheduled',
            'order' => 10,
            'is_active' => true,
        ]);
    }

    public function test_can_edit_deal_stage(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|mixed $user */
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $stage = DealStage::where('slug', 'qualified')->first();

        $this->actingAs($user);

        Livewire::test(\App\Filament\Resources\DealStages\Pages\ManageDealStages::class)
            ->callAction('edit', $stage, [
                'name' => 'Pre-Qualified Lead',
                'order' => 15,
            ])
            ->assertSuccessful();

        $stage->refresh();
        $this->assertEquals('Pre-Qualified Lead', $stage->name);
        $this->assertEquals(15, $stage->order);
    }

    public function test_cannot_delete_stage_with_existing_deals(): void
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable|mixed $user */
        $user = User::factory()->create();
        $user->assignRole('Admin');

        $stage = DealStage::where('slug', 'qualified')->first();

        // Create a deal with this stage
        $contact = \App\Models\Contact::factory()->create();
        \App\Models\Deal::factory()->create([
            'contact_id' => $contact->id,
            'deal_stage_id' => $stage->id,
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Filament\Resources\DealStages\Pages\ManageDealStages::class)
            ->callAction('delete', $stage)
            ->assertHasFormErrors();

        // Stage should still exist
        $this->assertDatabaseHas('deal_stages', ['id' => $stage->id]);
    }
}
