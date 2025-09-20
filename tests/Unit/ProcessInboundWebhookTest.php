<?php

namespace Tests\Unit;

use App\Jobs\ProcessInboundWebhook;
use App\Models\Contact;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProcessInboundWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_new_contact_from_webhook_payload(): void
    {
        $webhookEvent = WebhookEvent::create([
            'idempotency_key' => 'test-key-123',
            'event_type' => 'lead_form_submission',
            'source' => 'website_form',
            'payload' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890',
                'company' => 'Test Company',
                'job_title' => 'Developer',
                'message' => 'Interested in your services',
                'utm_source' => 'google',
                'utm_campaign' => 'summer-sale',
            ],
            'received_at' => now(),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $job = new ProcessInboundWebhook($webhookEvent->id);
        $job->handle();

        $contact = Contact::where('email', 'john@example.com')->first();
        $this->assertNotNull($contact);
        $this->assertEquals('John', $contact->first_name);
        $this->assertEquals('Doe', $contact->last_name);
        $this->assertEquals('John Doe', $contact->name);
        $this->assertEquals('john@example.com', $contact->email);
        $this->assertEquals('+1234567890', $contact->phone);
        $this->assertEquals('Test Company', $contact->company);
        $this->assertEquals('Developer', $contact->job_title);
        $this->assertEquals('Interested in your services', $contact->notes);
        $this->assertEquals('lead', $contact->status);
        $this->assertEquals('website_form', $contact->source);
        $this->assertEquals([
            'utm_source' => 'google',
            'utm_campaign' => 'summer-sale',
        ], $contact->source_meta);

        $webhookEvent->refresh();
        $this->assertEquals('processed', $webhookEvent->status);
    }

    public function test_updates_existing_contact_without_overwriting_data(): void
    {
        $existingContact = Contact::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'company' => 'Existing Company',
            'notes' => 'Existing notes',
            'status' => 'customer',
            'source' => 'referral',
            'source_meta' => ['existing' => 'data'],
        ]);

        $webhookEvent = WebhookEvent::create([
            'idempotency_key' => 'test-key-123',
            'event_type' => 'lead_form_submission',
            'source' => 'website_form',
            'payload' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => '+1234567890', // This should be added
                'company' => 'New Company', // This should NOT overwrite
                'job_title' => 'Developer', // This should be added
                'message' => 'New message',
                'utm_source' => 'google',
            ],
            'received_at' => now(),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $job = new ProcessInboundWebhook($webhookEvent->id);
        $job->handle();

        $existingContact->refresh();
        
        // Should add phone and job_title (empty fields)
        $this->assertEquals('+1234567890', $existingContact->phone);
        $this->assertEquals('Developer', $existingContact->job_title);
        
        // Should NOT overwrite existing company
        $this->assertEquals('Existing Company', $existingContact->company);
        
        // Should append to notes
        $this->assertStringContainsString('Existing notes', $existingContact->notes);
        $this->assertStringContainsString('Website Form: New message', $existingContact->notes);
        
        // Should merge source_meta
        $this->assertEquals([
            'existing' => 'data',
            'utm_source' => 'google',
        ], $existingContact->source_meta);
    }

    public function test_handles_name_normalization(): void
    {
        $webhookEvent = WebhookEvent::create([
            'idempotency_key' => 'test-key-123',
            'event_type' => 'lead_form_submission',
            'source' => 'website_form',
            'payload' => [
                'first_name' => '  John  ',
                'last_name' => '  Doe  ',
                'email' => '  JOHN@EXAMPLE.COM  ',
                'phone' => '+1-234-567-890',
            ],
            'received_at' => now(),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $job = new ProcessInboundWebhook($webhookEvent->id);
        $job->handle();

        $contact = Contact::first();
        $this->assertEquals('John', $contact->first_name);
        $this->assertEquals('Doe', $contact->last_name);
        $this->assertEquals('John Doe', $contact->name);
        $this->assertEquals('john@example.com', $contact->email);
        $this->assertEquals('+1234567890', $contact->phone);
    }

    public function test_handles_name_from_name_field(): void
    {
        $webhookEvent = WebhookEvent::create([
            'idempotency_key' => 'test-key-123',
            'event_type' => 'lead_form_submission',
            'source' => 'website_form',
            'payload' => [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
            ],
            'received_at' => now(),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $job = new ProcessInboundWebhook($webhookEvent->id);
        $job->handle();

        $contact = Contact::first();
        $this->assertEquals('Jane Smith', $contact->name);
        $this->assertNull($contact->first_name);
        $this->assertNull($contact->last_name);
    }

    public function test_creates_activity_log_entry(): void
    {
        $webhookEvent = WebhookEvent::create([
            'idempotency_key' => 'test-key-123',
            'event_type' => 'lead_form_submission',
            'source' => 'website_form',
            'payload' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'utm_source' => 'google',
            ],
            'received_at' => now(),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $job = new ProcessInboundWebhook($webhookEvent->id);
        $job->handle();

        $contact = Contact::first();
        $activities = DB::table('activity_log')
            ->where('subject_type', Contact::class)
            ->where('subject_id', $contact->id)
            ->get();

        $this->assertCount(1, $activities);
        $activity = $activities->first();
        $this->assertEquals('Lead created from website form submission', $activity->description);
        
        $properties = json_decode($activity->properties, true);
        $this->assertEquals($webhookEvent->id, $properties['webhook_event_id']);
        $this->assertEquals(['utm_source' => 'google'], $properties['utm_data']);
    }

    public function test_handles_missing_webhook_event(): void
    {
        $job = new ProcessInboundWebhook(99999); // Non-existent ID
        
        // Should not throw exception
        $job->handle();
        
        // No contacts should be created
        $this->assertEquals(0, Contact::count());
    }

    public function test_handles_duplicate_processing(): void
    {
        $webhookEvent = WebhookEvent::create([
            'idempotency_key' => 'test-key-123',
            'event_type' => 'lead_form_submission',
            'source' => 'website_form',
            'payload' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'received_at' => now(),
            'status' => 'processed', // Already processed
            'attempts' => 0,
        ]);

        $job = new ProcessInboundWebhook($webhookEvent->id);
        $job->handle();

        // Should not create duplicate contact
        $this->assertEquals(0, Contact::count());
    }

    public function test_sets_default_owner_when_configured(): void
    {
        $owner = User::factory()->create();
        config(['app.default_lead_owner_id' => $owner->id]);

        $webhookEvent = WebhookEvent::create([
            'idempotency_key' => 'test-key-123',
            'event_type' => 'lead_form_submission',
            'source' => 'website_form',
            'payload' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'received_at' => now(),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $job = new ProcessInboundWebhook($webhookEvent->id);
        $job->handle();

        $contact = Contact::first();
        $this->assertEquals($owner->id, $contact->owner_id);
    }
}
