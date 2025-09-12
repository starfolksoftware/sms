<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_user_can_create_contact_but_not_task(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->assignRole('Sales');

    $this->actingAs($user, 'web');
    $this->assertTrue($user->can('create', Contact::class));
    $this->assertFalse($user->can('create', Task::class));
    }

    public function test_product_role_can_manage_tasks_not_contacts(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->assignRole('Product');
    $this->actingAs($user, 'web');

        $canTask = $user->can('create', Task::class);
        $canContact = $user->can('create', Contact::class);

        $this->assertTrue($canTask);
        $this->assertFalse($canContact);
    }

    public function test_admin_has_universal_access(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->assignRole('Admin');
    $this->actingAs($admin, 'web');

        $this->assertTrue($admin->can('create', Contact::class));
        $this->assertTrue($admin->can('create', Task::class));
        $contact = Contact::factory()->create();
        $deal = Deal::create([
            'title' => 'X',
            'contact_id' => $contact->id,
            'amount' => 10,
            'currency' => 'USD',
            'stage' => 'new',
            'status' => 'open',
        ]);
        $this->assertTrue($admin->can('update', $deal));
    }
}
