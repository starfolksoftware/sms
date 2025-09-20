<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Notifications\DealCreatedNotification;
use App\Services\DealNotificationService;
use App\Settings\NotificationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_settings_control_notification_enablement(): void
    {
        Notification::fake();

        // Create users
        $salesUser = User::factory()->create();
        $salesUser->assignRole('Sales');

        $contact = Contact::factory()->create();

        // Get notification settings and disable deal created notifications
        $settings = app(NotificationSettings::class);
        $settings->deal_created_enabled = false;
        $settings->deal_created_roles = [];
        $settings->deal_created_users = [];
        $settings->deal_created_email_enabled = true;
        $settings->deal_created_database_enabled = true;
        $settings->save();

        // Create a deal
        $deal = Deal::factory()->create([
            'contact_id' => $contact->id,
        ]);

        // Get the service and try to get users for notification
        $service = app(DealNotificationService::class);
        $users = $service->getUsersForDealCreated($deal);

        // Should be empty when disabled
        $this->assertCount(0, $users);
    }

    public function test_admin_settings_filter_users_by_roles(): void
    {
        Notification::fake();

        // Create users with different roles
        $salesUser = User::factory()->create();
        $salesUser->assignRole('Sales');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $marketing = User::factory()->create();
        $marketing->assignRole('Marketing');

        $contact = Contact::factory()->create();

        // Configure settings to only notify Sales users
        $settings = app(NotificationSettings::class);
        $settings->deal_created_enabled = true;
        $settings->deal_created_roles = ['Sales'];
        $settings->deal_created_users = [];
        $settings->deal_created_email_enabled = true;
        $settings->deal_created_database_enabled = true;
        $settings->save();

        // Create a deal
        $deal = Deal::factory()->create([
            'contact_id' => $contact->id,
        ]);

        // Get users for notification
        $service = app(DealNotificationService::class);
        $users = $service->getUsersForDealCreated($deal);

        // Should include only the sales user (compare by ID)
        $this->assertTrue($users->pluck('id')->contains($salesUser->id));
        $this->assertFalse($users->pluck('id')->contains($admin->id));
        $this->assertFalse($users->pluck('id')->contains($marketing->id));
    }

    public function test_user_preferences_filter_channels(): void
    {
        // Create a user
        $user = User::factory()->create();
        $user->assignRole('Sales');

        // Set user preference to disable email notifications for deal created
        UserNotificationPreference::setUserPreference(
            $user->id,
            'deal_created',
            'email',
            false
        );

        // Enable database notifications
        UserNotificationPreference::setUserPreference(
            $user->id,
            'deal_created',
            'database',
            true
        );

        $service = app(DealNotificationService::class);

        // User should not receive email notifications
        $this->assertFalse($service->shouldReceiveNotification($user, 'deal_created', 'email'));

        // But should receive database notifications
        $this->assertTrue($service->shouldReceiveNotification($user, 'deal_created', 'database'));
    }

    public function test_notification_channels_are_dynamic(): void
    {
        // Create a user
        $user = User::factory()->create();

        // Set preferences: email disabled, database enabled
        UserNotificationPreference::setUserPreference($user->id, 'deal_created', 'email', false);
        UserNotificationPreference::setUserPreference($user->id, 'deal_created', 'database', true);

        // Create a deal notification
        $deal = Deal::factory()->create();
        $notification = new DealCreatedNotification($deal);

        // Get channels for this user
        $channels = $notification->via($user);

        // Should only include database, not mail
        $this->assertContains('database', $channels);
        $this->assertNotContains('mail', $channels);
    }

    public function test_admin_channel_settings_override_user_preferences(): void
    {
        // Create a user
        $user = User::factory()->create();
        $user->assignRole('Sales');

        // User wants email notifications
        UserNotificationPreference::setUserPreference($user->id, 'deal_created', 'email', true);

        // But admin disables email channel globally
        $settings = app(NotificationSettings::class);
        $settings->deal_created_enabled = true;
        $settings->deal_created_roles = [];
        $settings->deal_created_users = [];
        $settings->deal_created_email_enabled = false;
        $settings->deal_created_database_enabled = true;
        $settings->save();

        $service = app(DealNotificationService::class);

        // User should NOT receive email notifications (admin override)
        $this->assertFalse($service->shouldReceiveNotification($user, 'deal_created', 'email'));
    }

    public function test_default_user_preferences(): void
    {
        $user = User::factory()->create();

        // Without explicit preferences, user should receive database notifications
        $this->assertTrue(
            UserNotificationPreference::isUserPreferenceEnabled($user->id, 'deal_created', 'database')
        );

        // But not email notifications by default
        $this->assertFalse(
            UserNotificationPreference::isUserPreferenceEnabled($user->id, 'deal_created', 'email')
        );
    }

    public function test_specific_users_in_admin_settings(): void
    {
        // Create users
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $contact = Contact::factory()->create();

        // Configure settings to notify specific users
        $settings = app(NotificationSettings::class);
        $settings->deal_created_enabled = true;
        $settings->deal_created_roles = [];
        $settings->deal_created_users = [$user1->id, $user2->id];
        $settings->deal_created_email_enabled = true;
        $settings->deal_created_database_enabled = true;
        $settings->save();

        // Create a deal
        $deal = Deal::factory()->create(['contact_id' => $contact->id]);

        // Get users for notification
        $service = app(DealNotificationService::class);
        $users = $service->getUsersForDealCreated($deal);

        // Should include only the specified users (compare by ID)
        $this->assertTrue($users->pluck('id')->contains($user1->id));
        $this->assertTrue($users->pluck('id')->contains($user2->id));
        $this->assertFalse($users->pluck('id')->contains($user3->id));
    }

    public function test_deal_assignment_always_includes_owners(): void
    {
        $oldOwner = User::factory()->create();
        $newOwner = User::factory()->create();
        $contact = Contact::factory()->create();

        $deal = Deal::factory()->create([
            'owner_id' => $oldOwner->id,
            'contact_id' => $contact->id,
        ]);

        // Configure settings with no roles or users
        $settings = app(NotificationSettings::class);
        $settings->deal_assigned_enabled = true;
        $settings->deal_assigned_roles = [];
        $settings->deal_assigned_users = [];
        $settings->deal_assigned_email_enabled = true;
        $settings->deal_assigned_database_enabled = true;
        $settings->save();

        $service = app(DealNotificationService::class);
        $users = $service->getUsersForDealAssigned($deal, $oldOwner, $newOwner);

        // Should always include both old and new owners (compare by ID)
        $this->assertTrue($users->pluck('id')->contains($oldOwner->id));
        $this->assertTrue($users->pluck('id')->contains($newOwner->id));
    }
}
