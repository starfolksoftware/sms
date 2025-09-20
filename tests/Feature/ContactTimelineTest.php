<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use App\Services\ContactTimelineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTimelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_timeline_aggregates_task_events(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->assignRole('Admin'); 
        $this->actingAs($user);
        
        $contact = Contact::factory()->create();
        
        $task = Task::factory()->create([
            'contact_id' => $contact->id,
            'creator_id' => $user->id,
            'title' => 'Test Task',
            'status' => 'pending',
        ]);

        $service = new ContactTimelineService();
        $timeline = $service->getTimeline($contact, ['types' => ['tasks']]);

        $this->assertGreaterThan(0, $timeline->count());
        
        $items = $timeline->items();
        $this->assertIsIterable($items);
        
        $firstEvent = collect($items)->first();
        $this->assertEquals('task', $firstEvent->type);
        $this->assertEquals('created', $firstEvent->subtype);
        $this->assertStringContainsString('Test Task', $firstEvent->title);
    }

    public function test_timeline_aggregates_deal_events(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        
        $deal = Deal::factory()->create([
            'contact_id' => $contact->id,
            'owner_id' => $user->id,
            'title' => 'Test Deal',
            'amount' => 1000,
            'currency' => 'USD',
        ]);

        $service = new ContactTimelineService();
        $timeline = $service->getTimeline($contact, ['types' => ['deals']]);

        $this->assertGreaterThan(0, $timeline->count());
        
        $items = $timeline->items();
        $firstEvent = collect($items)->first();
        $this->assertEquals('deal', $firstEvent->type);
        $this->assertEquals('created', $firstEvent->subtype);
        $this->assertStringContainsString('Test Deal', $firstEvent->title);
    }

    public function test_timeline_filters_by_type(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        
        Task::factory()->create([
            'contact_id' => $contact->id,
            'creator_id' => $user->id,
        ]);
        
        Deal::factory()->create([
            'contact_id' => $contact->id,
            'owner_id' => $user->id,
        ]);

        $service = new ContactTimelineService();
        
        // Filter for tasks only
        $taskTimeline = $service->getTimeline($contact, ['types' => ['tasks']]);
        $this->assertGreaterThan(0, $taskTimeline->count());
        
        foreach ($taskTimeline->items() as $event) {
            $this->assertEquals('task', $event->type);
        }

        // Filter for deals only
        $dealTimeline = $service->getTimeline($contact, ['types' => ['deals']]);
        $this->assertGreaterThan(0, $dealTimeline->count());
        
        foreach ($dealTimeline->items() as $event) {
            $this->assertEquals('deal', $event->type);
        }
    }

    public function test_timeline_sorts_by_timestamp_descending(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        
        // Create events with different timestamps
        $task1 = Task::factory()->create([
            'contact_id' => $contact->id,
            'creator_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);
        
        $task2 = Task::factory()->create([
            'contact_id' => $contact->id,
            'creator_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);

        $service = new ContactTimelineService();
        $timeline = $service->getTimeline($contact, ['types' => ['tasks']]);

        $events = collect($timeline->items());
        $this->assertGreaterThanOrEqual(2, $events->count());
        
        // Check that events are sorted by timestamp descending (newest first)
        for ($i = 0; $i < $events->count() - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $events->get($i + 1)->timestamp,
                $events->get($i)->timestamp
            );
        }
    }

    public function test_timeline_pagination_works(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        
        // Create multiple tasks to test pagination
        for ($i = 0; $i < 20; $i++) {
            Task::factory()->create([
                'contact_id' => $contact->id,
                'creator_id' => $user->id,
            ]);
        }

        $service = new ContactTimelineService();
        $timeline = $service->getTimeline($contact, ['types' => ['tasks']], 10);

        $this->assertEquals(10, $timeline->perPage());
        $this->assertGreaterThan(10, $timeline->total());
        $this->assertEquals(10, count($timeline->items()));
    }

    public function test_timeline_respects_date_filters(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        
        $oldTask = Task::factory()->create([
            'contact_id' => $contact->id,
            'creator_id' => $user->id,
            'created_at' => now()->subWeek(),
        ]);
        
        $newTask = Task::factory()->create([
            'contact_id' => $contact->id,
            'creator_id' => $user->id,
            'created_at' => now(),
        ]);

        $service = new ContactTimelineService();
        
        // Filter for events from yesterday onwards
        $timeline = $service->getTimeline($contact, [
            'types' => ['tasks'],
            'date_from' => now()->subDay()->toDateString(),
        ]);

        // Should only contain the new task
        $this->assertEquals(1, $timeline->count());
        $firstEvent = collect($timeline->items())->first();
        $this->assertEquals($newTask->id, $firstEvent->metadata['task_id']);
    }
}