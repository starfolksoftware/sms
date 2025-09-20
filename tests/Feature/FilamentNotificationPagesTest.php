<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageNotificationSettings;
use App\Filament\Pages\UserNotificationPreferences;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentNotificationPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_access_notification_settings_page(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        Filament::setCurrentPanel('app');

        $this->actingAs($admin);

        Livewire::test(ManageNotificationSettings::class)
            ->assertStatus(200)
            ->assertSee('Notification Settings');
    }

    public function test_user_can_access_notification_preferences_page(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('Sales');

        Filament::setCurrentPanel('app');

        $this->actingAs($user);

        Livewire::test(UserNotificationPreferences::class)
            ->assertStatus(200)
            ->assertSee('Notification Preferences');
    }

    public function test_user_can_update_notification_preferences(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('Sales');

        Filament::setCurrentPanel('app');

        $this->actingAs($user);

        Livewire::test(UserNotificationPreferences::class)
            ->set('deal_created_email', true)
            ->set('deal_created_database', true)
            ->set('deal_won_email', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertNotified();

        // Verify preferences were saved
        $this->assertTrue(
            UserNotificationPreference::isUserPreferenceEnabled($user->id, 'deal_created', 'email')
        );
        $this->assertTrue(
            UserNotificationPreference::isUserPreferenceEnabled($user->id, 'deal_created', 'database')
        );
        $this->assertFalse(
            UserNotificationPreference::isUserPreferenceEnabled($user->id, 'deal_won', 'email')
        );
    }

    public function test_admin_can_update_notification_settings(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        Filament::setCurrentPanel('app');

        $this->actingAs($admin);

        Livewire::test(ManageNotificationSettings::class)
            ->fillForm([
                // Deal Created
                'deal_created_enabled' => true,
                'deal_created_roles' => [],
                'deal_created_users' => [],
                'deal_created_email_enabled' => true,
                'deal_created_database_enabled' => false,

                // Deal Stage Changed
                'deal_stage_changed_enabled' => false,
                'deal_stage_changed_roles' => [],
                'deal_stage_changed_users' => [],
                'deal_stage_changed_email_enabled' => true,
                'deal_stage_changed_database_enabled' => true,

                // Deal Won
                'deal_won_enabled' => false,
                'deal_won_roles' => [],
                'deal_won_users' => [],
                'deal_won_email_enabled' => true,
                'deal_won_database_enabled' => true,

                // Deal Lost
                'deal_lost_enabled' => false,
                'deal_lost_roles' => [],
                'deal_lost_users' => [],
                'deal_lost_email_enabled' => true,
                'deal_lost_database_enabled' => true,

                // Deal Assigned
                'deal_assigned_enabled' => false,
                'deal_assigned_roles' => [],
                'deal_assigned_users' => [],
                'deal_assigned_email_enabled' => true,
                'deal_assigned_database_enabled' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        // Verify settings were saved
        $settings = app(\App\Settings\NotificationSettings::class);
        $this->assertTrue($settings->deal_created_enabled);
        $this->assertTrue($settings->deal_created_email_enabled);
        $this->assertFalse($settings->deal_created_database_enabled);
    }
}
