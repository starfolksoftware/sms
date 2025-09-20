<?php

namespace Tests\Feature;

use App\Filament\Pages\UserNotificationPreferences;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_notification_preferences_page(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // Test using Livewire component directly
        Livewire::actingAs($user)
            ->test(UserNotificationPreferences::class)
            ->assertOk();
    }

    public function test_user_can_update_notification_preferences(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // Create initial preferences
        UserNotificationPreference::setUserPreference($user->id, 'deal_created', 'email', false);
        UserNotificationPreference::setUserPreference($user->id, 'deal_created', 'database', false);

        Livewire::actingAs($user)
            ->test(UserNotificationPreferences::class)
            ->set('data.deal_created_email', true)
            ->set('data.deal_created_database', true)
            ->call('save')
            ->assertNotified();

        // Verify preferences were updated
        $this->assertTrue(
            UserNotificationPreference::isUserPreferenceEnabled($user->id, 'deal_created', 'email')
        );
        $this->assertTrue(
            UserNotificationPreference::isUserPreferenceEnabled($user->id, 'deal_created', 'database')
        );
    }

    public function test_notification_preferences_display_current_settings(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();

        // Set some initial preferences
        UserNotificationPreference::setUserPreference($user->id, 'deal_won', 'email', true);
        UserNotificationPreference::setUserPreference($user->id, 'deal_lost', 'database', true);

        $component = Livewire::actingAs($user)
            ->test(UserNotificationPreferences::class);

        // Check that the form is populated with current settings
        $this->assertTrue($component->get('data.deal_won_email'));
        $this->assertTrue($component->get('data.deal_lost_database'));
        $this->assertFalse($component->get('data.deal_created_email'));
    }
}
