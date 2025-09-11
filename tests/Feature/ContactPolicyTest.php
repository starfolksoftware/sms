<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_sales_user_can_view_and_manage_clients(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Sales');
    $this->actingAs($user); // @phpstan-ignore-line

    $this->assertTrue($user->can('manage_clients'));
    $this->assertTrue($user->can('view_clients'));
    $contact = Contact::factory()->create();
    $this->assertTrue($user->can('view', $contact));
    $this->assertTrue($user->can('update', $contact));
    }

    public function test_non_privileged_user_cannot_manage_contacts(): void
    {
        $user = User::factory()->create();
    $this->actingAs($user); // @phpstan-ignore-line
        $contact = Contact::factory()->create();
        $this->assertFalse($user->can('update', $contact));
    }
}
