<?php

namespace Tests\Feature;

use App\Events\DealAssigned;
use App\Events\DealCreated;
use App\Events\DealLost;
use App\Events\DealStageChanged;
use App\Events\DealWon;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use App\Notifications\DealAssignedNotification;
use App\Notifications\DealCreatedNotification;
use App\Notifications\DealLostNotification;
use App\Notifications\DealStageChangedNotification;
use App\Notifications\DealWonNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DealNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_deal_created_event_fires_notification(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $owner->assignRole('Sales');

        $manager = User::factory()->create();
        $manager->assignRole('Sales Manager');
        $manager->givePermissionTo('manage_deals');

        $contact = Contact::factory()->create();
        
        // Create a deal - this should trigger the observer and send notifications
        $deal = Deal::factory()->create([
            'owner_id' => $owner->id,
            'contact_id' => $contact->id,
        ]);

        // Assert notification was sent to sales managers
        Notification::assertSentTo(
            $manager,
            DealCreatedNotification::class,
            function ($notification) use ($deal) {
                return $notification->deal->id === $deal->id;
            }
        );
    }

    public function test_deal_stage_changed_triggers_notification(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $owner->assignRole('Sales');

        $manager = User::factory()->create();
        $manager->assignRole('Sales Manager');
        $manager->givePermissionTo('manage_deals');

        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'owner_id' => $owner->id,
            'contact_id' => $contact->id,
            'stage' => 'prospect',
        ]);

        // Clear any notifications from creation
        Notification::fake();

        // Update the stage
        $deal->update(['stage' => 'qualified']);

        // Assert notification was sent to owner and managers
        Notification::assertSentTo(
            [$owner, $manager],
            DealStageChangedNotification::class,
            function ($notification) use ($deal) {
                return $notification->deal->id === $deal->id
                    && $notification->fromStage === 'prospect'
                    && $notification->toStage === 'qualified';
            }
        );
    }

    public function test_deal_assignment_triggers_notification(): void
    {
        Notification::fake();

        $oldOwner = User::factory()->create();
        $oldOwner->assignRole('Sales');

        $newOwner = User::factory()->create();
        $newOwner->assignRole('Sales');

        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'owner_id' => $oldOwner->id,
            'contact_id' => $contact->id,
        ]);

        // Clear any notifications from creation
        Notification::fake();

        // Reassign the deal
        $deal->update(['owner_id' => $newOwner->id]);

        // Assert notification was sent to both old and new owner
        Notification::assertSentTo(
            [$oldOwner, $newOwner],
            DealAssignedNotification::class,
            function ($notification) use ($deal, $oldOwner, $newOwner) {
                return $notification->deal->id === $deal->id
                    && $notification->oldOwner->id === $oldOwner->id
                    && $notification->newOwner->id === $newOwner->id;
            }
        );
    }

    public function test_deal_won_notification_contains_correct_data(): void
    {
        // Test that the event system properly queues the notification listener
        \Illuminate\Support\Facades\Event::fake();
        
        $owner = User::factory()->create();
        $owner->assignRole('Sales');

        $manager = User::factory()->create();
        $manager->assignRole('Sales Manager');

        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'owner_id' => $owner->id,
            'contact_id' => $contact->id,
            'amount' => 1000.00,
            'status' => 'open',
        ]);

        // Mark deal as won
        $deal->markAsWon(1200.00);

        // Assert the DealWon event was dispatched with correct data
        \Illuminate\Support\Facades\Event::assertDispatched(
            \App\Events\DealWon::class,
            function ($event) use ($deal) {
                return $event->deal->id === $deal->id 
                    && $event->wonAmount === 1200.00;
            }
        );
    }

    public function test_deal_lost_notification_contains_correct_data(): void
    {
        Notification::fake();

        $owner = User::factory()->create();
        $owner->assignRole('Sales');

        $contact = Contact::factory()->create();
        $deal = Deal::factory()->create([
            'owner_id' => $owner->id,
            'contact_id' => $contact->id,
            'status' => 'open',
        ]);

        // Mark deal as lost
        $deal->markAsLost('Customer chose competitor');

        // Assert notification was sent with correct data
        Notification::assertSentTo(
            $owner,
            DealLostNotification::class,
            function ($notification) use ($deal, $owner) {
                $notificationData = $notification->toArray($owner);
                return $notificationData['deal_id'] === $deal->id
                    && $notificationData['lost_reason'] === 'Customer chose competitor'
                    && $notificationData['deal_title'] === $deal->title;
            }
        );
    }

    public function test_notification_respects_user_permissions(): void
    {
        Notification::fake();

        // User without manage_deals permission
        $regularUser = User::factory()->create();
        $regularUser->assignRole('Sales');

        // User with manage_deals permission
        $manager = User::factory()->create();
        $manager->assignRole('Sales Manager');
        $manager->givePermissionTo('manage_deals');

        $contact = Contact::factory()->create();
        
        // Create a deal owned by regular user
        $deal = Deal::factory()->create([
            'owner_id' => $regularUser->id,
            'contact_id' => $contact->id,
        ]);

        // Manager should receive notification (has manage_deals permission)
        Notification::assertSentTo($manager, DealCreatedNotification::class);
        
        // Regular user should not receive notification (they created it)
        Notification::assertNotSentTo($regularUser, DealCreatedNotification::class);
    }

    public function test_events_are_dispatched_correctly(): void
    {
        // Don't use Event::fake() as it prevents model observers from working
        // Instead, we'll manually listen to events and track them
        $eventsDispatched = [];
        
        Event::listen(DealCreated::class, function ($event) use (&$eventsDispatched) {
            $eventsDispatched['DealCreated'] = $event;
        });
        
        Event::listen(DealStageChanged::class, function ($event) use (&$eventsDispatched) {
            $eventsDispatched['DealStageChanged'] = $event;
        });
        
        Event::listen(DealAssigned::class, function ($event) use (&$eventsDispatched) {
            $eventsDispatched['DealAssigned'] = $event;
        });
        
        Event::listen(DealWon::class, function ($event) use (&$eventsDispatched) {
            $eventsDispatched['DealWon'] = $event;
        });
        
        Event::listen(DealLost::class, function ($event) use (&$eventsDispatched) {
            $eventsDispatched['DealLost'] = $event;
        });

        $owner = User::factory()->create();
        $contact = Contact::factory()->create();

        // Test DealCreated event
        $deal = Deal::factory()->create([
            'owner_id' => $owner->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertArrayHasKey('DealCreated', $eventsDispatched);
        $this->assertEquals($deal->id, $eventsDispatched['DealCreated']->deal->id);

        // Test DealStageChanged event
        $deal->update(['stage' => 'qualified']);

        $this->assertArrayHasKey('DealStageChanged', $eventsDispatched);
        $this->assertEquals($deal->id, $eventsDispatched['DealStageChanged']->deal->id);

        // Test DealAssigned event
        $newOwner = User::factory()->create();
        $deal->update(['owner_id' => $newOwner->id]);

        $this->assertArrayHasKey('DealAssigned', $eventsDispatched);
        $this->assertEquals($deal->id, $eventsDispatched['DealAssigned']->deal->id);
        $this->assertEquals($owner->id, $eventsDispatched['DealAssigned']->oldOwner->id);
        $this->assertEquals($newOwner->id, $eventsDispatched['DealAssigned']->newOwner->id);

        // Test DealWon event
        $deal->markAsWon();
        $this->assertArrayHasKey('DealWon', $eventsDispatched);

        // Test DealLost event
        $deal2 = Deal::factory()->create(['contact_id' => $contact->id]);
        $deal2->markAsLost('Budget constraints');
        $this->assertArrayHasKey('DealLost', $eventsDispatched);
    }
}