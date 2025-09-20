<?php

namespace Tests\Feature;

use App\Jobs\ProcessInboundWebhook;
use App\Models\Contact;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.webhook_lead_token' => 'test-token-123']);
    }

    public function test_complete_webhook_to_contact_flow(): void
    {
        // Don't fake the queue for this integration test
        $payload = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+1-555-123-4567',
            'company' => 'Acme Corp',
            'job_title' => 'Marketing Manager',
            'message' => 'Interested in your CRM solution',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'crm-leads',
            'utm_term' => 'customer relationship management',
            'utm_content' => 'ad-variant-a',
            'idempotency_key' => 'integration-test-123',
        ];

        // Send webhook request
        $response = $this->postJson('/api/webhooks/lead-form', $payload, [
            'X-Starfolk-Webhook-Token' => 'test-token-123',
        ]);

        $response->assertStatus(202);
        $receiptId = $response->json('receipt_id');

        // Verify webhook event was created
        $webhookEvent = WebhookEvent::find($receiptId);
        $this->assertNotNull($webhookEvent);
        $this->assertEquals('lead_form_submission', $webhookEvent->event_type);
        $this->assertEquals('website_form', $webhookEvent->source);
        // Job may have already processed due to sync queue, so check that it exists
        $this->assertContains($webhookEvent->status, ['pending', 'processed']);

        // If still pending, process the job manually
        if ($webhookEvent->status === 'pending') {
            $job = new ProcessInboundWebhook($webhookEvent->id);
            $job->handle();
        }

        // Verify contact was created with correct data
        $contact = Contact::where('email', 'jane.smith@example.com')->first();
        $this->assertNotNull($contact);
        $this->assertEquals('Jane', $contact->first_name);
        $this->assertEquals('Smith', $contact->last_name);
        $this->assertEquals('Jane Smith', $contact->name);
        $this->assertEquals('jane.smith@example.com', $contact->email);
        $this->assertEquals('+15551234567', $contact->phone); // Normalized
        $this->assertEquals('Acme Corp', $contact->company);
        $this->assertEquals('Marketing Manager', $contact->job_title);
        $this->assertEquals('Interested in your CRM solution', $contact->notes);
        $this->assertEquals('lead', $contact->status);
        $this->assertEquals('website_form', $contact->source);

        // Verify UTM data
        $expectedUtmData = [
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'crm-leads',
            'utm_term' => 'customer relationship management',
            'utm_content' => 'ad-variant-a',
        ];
        $this->assertEquals($expectedUtmData, $contact->source_meta);

        // Verify webhook event was marked as processed
        $webhookEvent->refresh();
        $this->assertEquals('processed', $webhookEvent->status);
        $this->assertNotNull($webhookEvent->processed_at);

        // Verify activity log was created
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
            'description' => 'Lead created from website form submission',
        ]);
    }

    public function test_duplicate_webhook_handling(): void
    {
        $payload = [
            'name' => 'John Duplicate',
            'email' => 'john.duplicate@example.com',
            'idempotency_key' => 'duplicate-test-123',
        ];

        // First request
        $response1 = $this->postJson('/api/webhooks/lead-form', $payload, [
            'X-Starfolk-Webhook-Token' => 'test-token-123',
        ]);
        $response1->assertStatus(202);
        $receiptId1 = $response1->json('receipt_id');

        // Process the first webhook
        $webhookEvent1 = WebhookEvent::find($receiptId1);
        $job1 = new ProcessInboundWebhook($webhookEvent1->id);
        $job1->handle();

        // Verify contact was created
        $this->assertEquals(1, Contact::count());
        $contact = Contact::first();

        // Second request with same idempotency key
        $response2 = $this->postJson('/api/webhooks/lead-form', $payload, [
            'X-Starfolk-Webhook-Token' => 'test-token-123',
        ]);

        $response2->assertStatus(202)
            ->assertJson([
                'message' => 'Webhook already processed',
                'receipt_id' => $receiptId1,
            ]);

        // Verify no duplicate contact was created
        $this->assertEquals(1, Contact::count());
        $this->assertEquals(1, WebhookEvent::count());
    }

    public function test_existing_contact_update_flow(): void
    {
        // Create existing contact
        $existingContact = Contact::create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'company' => 'Old Company',
            'status' => 'customer',
            'source' => 'referral',
        ]);

        $payload = [
            'first_name' => 'Existing',
            'last_name' => 'User',
            'email' => 'existing@example.com',
            'phone' => '+1-555-999-8888', // New data
            'job_title' => 'CTO', // New data
            'company' => 'New Company', // Should NOT overwrite
            'message' => 'Follow-up inquiry',
            'utm_source' => 'email',
        ];

        // Send webhook
        $response = $this->postJson('/api/webhooks/lead-form', $payload, [
            'X-Starfolk-Webhook-Token' => 'test-token-123',
        ]);

        $response->assertStatus(202);
        $webhookEvent = WebhookEvent::latest()->first();

        // Process the job
        $job = new ProcessInboundWebhook($webhookEvent->id);
        $job->handle();

        // Verify contact was updated appropriately
        $existingContact->refresh();

        // Should add new data
        $this->assertEquals('+15559998888', $existingContact->phone);
        $this->assertEquals('CTO', $existingContact->job_title);

        // Should NOT overwrite existing data
        $this->assertEquals('Old Company', $existingContact->company);
        $this->assertEquals('customer', $existingContact->status); // Status unchanged

        // Should append to notes
        $this->assertStringContainsString('Website Form: Follow-up inquiry', $existingContact->notes);

        // Should merge UTM data
        $this->assertEquals(['utm_source' => 'email'], $existingContact->source_meta);

        // Should not create duplicate contact
        $this->assertEquals(1, Contact::count());
    }

    public function test_webhook_with_owner_assignment(): void
    {
        $owner = User::factory()->create();
        config(['app.default_lead_owner_id' => $owner->id]);

        $payload = [
            'name' => 'Assigned Lead',
            'email' => 'assigned@example.com',
        ];

        $response = $this->postJson('/api/webhooks/lead-form', $payload, [
            'X-Starfolk-Webhook-Token' => 'test-token-123',
        ]);

        $response->assertStatus(202);
        $webhookEvent = WebhookEvent::latest()->first();

        $job = new ProcessInboundWebhook($webhookEvent->id);
        $job->handle();

        $contact = Contact::first();
        $this->assertEquals($owner->id, $contact->owner_id);
    }
}
