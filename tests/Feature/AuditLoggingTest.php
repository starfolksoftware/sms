<?php

namespace Tests\Feature;

use App\Events\DataExported;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_login_logout_are_logged(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $user->assignRole('Admin');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret',
        ])->assertStatus(302);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'user.login.success',
            'causer_id' => $user->id,
            'log_name' => 'security',
        ]);

        $this->post('/logout')->assertStatus(302);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'user.logout',
            'causer_id' => $user->id,
            'log_name' => 'security',
        ]);
    }

    public function test_failed_login_logged(): void
    {
        $this->post('/login', [
            'email' => 'missing@example.com',
            'password' => 'wrong',
        ])->assertStatus(302);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'user.login.failed',
            'log_name' => 'security',
        ]);
    }

    public function test_data_export_event_logged(): void
    {
    $user = User::factory()->create();
    $user->assignRole('Admin');
    $this->actingAs($user);

        event(new DataExported(module: 'contacts', filters: ['stage' => 'lead'], count: 5, format: 'csv'));

        $this->assertDatabaseHas('activity_log', [
            'description' => 'data.export.performed',
            'log_name' => 'data_ops',
            'causer_id' => $user->id,
        ]);
    }

    public function test_deletion_logged(): void
    {
    $user = User::factory()->create();
    $user->assignRole('Admin');
    $this->actingAs($user);

        $contact = Contact::create(['name' => 'Test C', 'email' => 't@example.com']);
        $contact->delete();

        $this->assertDatabaseHas('activity_log', [
            'description' => 'model.deleted',
            'subject_id' => $contact->id,
            'subject_type' => Contact::class,
        ]);
    }

    public function test_audit_log_access_requires_permission(): void
    {
    $user = User::factory()->create();
    $this->actingAs($user);

        $this->get('/admin/audit-logs')->assertStatus(403);

        $user->assignRole('Admin'); // inherits permission
        $this->get('/admin/audit-logs')->assertStatus(200);
    }
}
