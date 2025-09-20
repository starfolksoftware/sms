<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\ContactTimelineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessInboundWebhook implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 5;
    public $backoff = [60, 300, 900, 3600]; // 1m, 5m, 15m, 60m

    public function __construct(
        public int $webhookEventId
    ) {}

    public function handle(): void
    {
        $webhookEvent = WebhookEvent::find($this->webhookEventId);
        
        if (!$webhookEvent) {
            Log::error('WebhookEvent not found', ['id' => $this->webhookEventId]);
            return;
        }

        try {
            // Check for duplicate processing before marking as processing
            if ($webhookEvent->status === 'processed') {
                Log::info('Webhook already processed', ['id' => $webhookEvent->id]);
                return;
            }

            $webhookEvent->markAsProcessing();
            
            $this->processLeadFormSubmission($webhookEvent);
            
            $webhookEvent->markAsProcessed();
            
        } catch (\Exception $e) {
            $webhookEvent->markAsFailed($e->getMessage());
            
            Log::error('Failed to process webhook', [
                'webhook_id' => $webhookEvent->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            
            // If we've reached max attempts, don't retry
            if ($this->attempts() >= $this->tries) {
                $this->createWebhookFailure($webhookEvent, $e);
            }
            
            throw $e; // This will trigger retry if we haven't reached max attempts
        }
    }

    protected function processLeadFormSubmission(WebhookEvent $webhookEvent): void
    {
        $payload = $webhookEvent->payload;
        
        // Normalize and validate the data
        $contactData = $this->normalizeContactData($payload);
        
        DB::transaction(function () use ($contactData, $webhookEvent) {
            // Check for existing contact by email
            $existingContact = null;
            if (!empty($contactData['email'])) {
                $existingContact = Contact::where('email', $contactData['email'])
                    ->whereNull('deleted_at')
                    ->first();
            }
            
            if ($existingContact) {
                // Update existing contact if needed
                $this->updateExistingContact($existingContact, $contactData, $webhookEvent);
            } else {
                // Create new contact/lead
                $this->createNewContact($contactData, $webhookEvent);
            }
        });
    }

    protected function normalizeContactData(array $payload): array
    {
        $data = [
            'first_name' => trim($payload['first_name'] ?? ''),
            'last_name' => trim($payload['last_name'] ?? ''),
            'name' => trim($payload['name'] ?? ''),
            'email' => !empty($payload['email']) ? strtolower(trim($payload['email'])) : null,
            'phone' => $this->normalizePhone($payload['phone'] ?? ''),
            'company' => trim($payload['company'] ?? ''),
            'job_title' => trim($payload['job_title'] ?? ''),
            'notes' => trim($payload['message'] ?? $payload['notes'] ?? ''),
            'status' => 'lead',
            'source' => 'website_form',
        ];

        // Build name from first/last if name is empty
        if (empty($data['name']) && (!empty($data['first_name']) || !empty($data['last_name']))) {
            $data['name'] = trim($data['first_name'] . ' ' . $data['last_name']);
        }

        // Build UTM metadata
        $utmData = [];
        foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'] as $utmField) {
            if (!empty($payload[$utmField])) {
                $utmData[$utmField] = $payload[$utmField];
            }
        }
        $data['source_meta'] = $utmData;

        // Set owner if configured
        if ($defaultOwnerId = config('app.default_lead_owner_id')) {
            $data['owner_id'] = $defaultOwnerId;
        }

        return array_filter($data, function ($value) {
            return $value !== '' && $value !== null;
        });
    }

    protected function normalizePhone(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }
        
        // Basic phone normalization - remove common separators
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // Limit length
        return substr($phone, 0, 50);
    }

    protected function updateExistingContact(Contact $contact, array $contactData, WebhookEvent $webhookEvent): void
    {
        $updates = [];
        
        // Only update empty fields to avoid overwriting existing data
        foreach (['first_name', 'last_name', 'name', 'phone', 'company', 'job_title'] as $field) {
            if (empty($contact->$field) && !empty($contactData[$field])) {
                $updates[$field] = $contactData[$field];
            }
        }
        
        // Append notes instead of replacing
        if (!empty($contactData['notes'])) {
            $existingNotes = $contact->notes ?? '';
            $newNote = "Website Form: " . $contactData['notes'];
            $updates['notes'] = $existingNotes ? $existingNotes . "\n\n" . $newNote : $newNote;
        }
        
        // Merge UTM data
        if (!empty($contactData['source_meta'])) {
            $existingMeta = $contact->source_meta ?? [];
            $updates['source_meta'] = array_merge($existingMeta, $contactData['source_meta']);
        }
        
        if (!empty($updates)) {
            $contact->update($updates);
        }
        
        // Log activity
        activity()
            ->performedOn($contact)
            ->withProperties([
                'webhook_event_id' => $webhookEvent->id,
                'utm_data' => $contactData['source_meta'] ?? [],
                'updated_fields' => array_keys($updates),
            ])
            ->log('Contact updated from website form submission');
    }

    protected function createNewContact(array $contactData, WebhookEvent $webhookEvent): Contact
    {
        $contact = Contact::create($contactData);
        
        // Log activity
        activity()
            ->performedOn($contact)
            ->withProperties([
                'webhook_event_id' => $webhookEvent->id,
                'utm_data' => $contactData['source_meta'] ?? [],
                'source' => 'website_form',
            ])
            ->log('Lead created from website form submission');
            
        return $contact;
    }

    protected function createWebhookFailure(WebhookEvent $webhookEvent, \Exception $e): void
    {
        DB::table('webhook_failures')->insert([
            'direction' => 'inbound',
            'event_type' => $webhookEvent->event_type,
            'endpoint' => null,
            'payload' => json_encode($webhookEvent->payload),
            'headers' => json_encode([]),
            'error_message' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
            'final_attempts' => $webhookEvent->attempts,
            'first_failed_at' => $webhookEvent->created_at,
            'final_failed_at' => now(),
            'failure_reason' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
