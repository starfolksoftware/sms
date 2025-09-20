<?php

namespace Tests\Feature;

use App\Jobs\ProcessInboundWebhook;
use App\Models\Contact;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookLeadFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.webhook_lead_token' => 'test-token-123']);
    }

    public function test_webhook_requires_valid_token(): void
    {
        $response = $this->postJson('/api/webhooks/lead-form', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);
    }

    public function test_webhook_accepts_valid_token(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/webhooks/lead-form', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ], [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'message',
                'receipt_id'
            ]);

        Queue::assertPushed(ProcessInboundWebhook::class);
    }

    public function test_webhook_validates_required_contact_info(): void
    {
        $response = $this->postJson('/api/webhooks/lead-form', [
            'company' => 'Test Company',
        ], [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['contact_info']);
    }

    public function test_webhook_accepts_email_only(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/webhooks/lead-form', [
            'email' => 'john@example.com',
            'company' => 'Test Company',
        ], [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response->assertStatus(202);
    }

    public function test_webhook_accepts_phone_only(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/webhooks/lead-form', [
            'phone' => '+1234567890',
            'company' => 'Test Company',
        ], [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response->assertStatus(202);
    }

    public function test_webhook_accepts_name_only(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/webhooks/lead-form', [
            'name' => 'John Doe',
            'company' => 'Test Company',
        ], [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response->assertStatus(202);
    }

    public function test_webhook_handles_idempotency(): void
    {
        Queue::fake();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'idempotency_key' => 'unique-key-123',
        ];

        // First request
        $response1 = $this->postJson('/api/webhooks/lead-form', $payload, [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response1->assertStatus(202);
        $receiptId = $response1->json('receipt_id');

        // Second request with same idempotency key
        $response2 = $this->postJson('/api/webhooks/lead-form', $payload, [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response2->assertStatus(202)
            ->assertJson([
                'message' => 'Webhook already processed',
                'receipt_id' => $receiptId,
            ]);

        // Should only be queued once
        Queue::assertPushed(ProcessInboundWebhook::class, 1);
    }

    public function test_webhook_creates_webhook_event_record(): void
    {
        Queue::fake();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'utm_source' => 'google',
            'utm_campaign' => 'summer-sale',
        ];

        $response = $this->postJson('/api/webhooks/lead-form', $payload, [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response->assertStatus(202);
        $receiptId = $response->json('receipt_id');

        $webhookEvent = WebhookEvent::find($receiptId);
        $this->assertNotNull($webhookEvent);
        $this->assertEquals('lead_form_submission', $webhookEvent->event_type);
        $this->assertEquals('website_form', $webhookEvent->source);
        $this->assertEquals('pending', $webhookEvent->status);
        $this->assertEquals($payload, $webhookEvent->payload);
    }

    public function test_webhook_validates_email_format(): void
    {
        $response = $this->postJson('/api/webhooks/lead-form', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
        ], [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_webhook_validates_field_lengths(): void
    {
        $response = $this->postJson('/api/webhooks/lead-form', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => str_repeat('a', 2001), // Exceeds 2000 char limit
        ], [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    public function test_webhook_accepts_utm_parameters(): void
    {
        Queue::fake();

        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'summer-sale',
            'utm_term' => 'crm software',
            'utm_content' => 'headline-1',
        ];

        $response = $this->postJson('/api/webhooks/lead-form', $payload, [
            'X-Starfolk-Webhook-Token' => 'test-token-123'
        ]);

        $response->assertStatus(202);

        $webhookEvent = WebhookEvent::latest()->first();
        $this->assertEquals($payload, $webhookEvent->payload);
    }
}
