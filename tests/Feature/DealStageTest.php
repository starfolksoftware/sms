<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\DealStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealStageTest extends TestCase
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

    public function test_deal_stages_are_seeded_correctly(): void
    {
        $this->assertDatabaseCount('deal_stages', 6);

        $stages = DealStage::ordered()->get();
        $this->assertEquals('Lead In', $stages->first()->name);
        $this->assertEquals('Closed Lost', $stages->last()->name);

        // Check winning and losing stages
        $this->assertEquals(1, DealStage::winning()->count());
        $this->assertEquals(1, DealStage::losing()->count());
    }

    public function test_deal_can_be_created_with_stage(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $stage = DealStage::where('slug', 'qualified')->first();

        $deal = Deal::create([
            'title' => 'Test Deal',
            'contact_id' => $contact->id,
            'deal_stage_id' => $stage->id,
            'amount' => 1000,
            'currency' => 'USD',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('deals', [
            'title' => 'Test Deal',
            'deal_stage_id' => $stage->id,
        ]);

        $this->assertEquals('Qualified', $deal->dealStage->name);
    }

    public function test_deal_stage_relationship_works(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $stage = DealStage::where('slug', 'proposal-sent')->first();

        $deal = Deal::factory()->create([
            'contact_id' => $contact->id,
            'deal_stage_id' => $stage->id,
        ]);

        $this->assertEquals('Proposal Sent', $deal->dealStage->name);
        $this->assertEquals('proposal-sent', $deal->dealStage->slug);
        $this->assertTrue($stage->deals->contains($deal));
    }

    public function test_only_active_stages_are_returned_by_scope(): void
    {
        $activeCount = DealStage::active()->count();
        $this->assertEquals(6, $activeCount);

        // Deactivate a stage
        $stage = DealStage::first();
        $stage->update(['is_active' => false]);

        $this->assertEquals(5, DealStage::active()->count());
        $this->assertEquals(6, DealStage::count());
    }
}
