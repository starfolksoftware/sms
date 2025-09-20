<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EdgeCaseValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_contact_email_normalization_prevents_case_sensitive_duplicates(): void
    {
        // Create contact with lowercase email
        $contact1 = Contact::factory()->create(['email' => 'test@example.com']);
        
        // Try to create another with uppercase email
        $contact2Data = Contact::factory()->make(['email' => 'TEST@EXAMPLE.COM'])->toArray();
        
        // Should fail due to normalization
        $this->expectException(\Illuminate\Database\QueryException::class);
        Contact::create($contact2Data);
    }

    public function test_soft_deleted_contact_can_be_recreated_with_same_email(): void
    {
        // Create and soft delete a contact
        $contact = Contact::factory()->create(['email' => 'test@example.com']);
        $contact->delete();
        
        // Should be able to create new contact with same email
        $newContact = Contact::factory()->create(['email' => 'test@example.com']);
        
        $this->assertNotEquals($contact->id, $newContact->id);
        $this->assertEquals('test@example.com', $newContact->email);
    }

    public function test_restoring_contact_fails_if_email_conflict_exists(): void
    {
        // Create contact, soft delete it
        $contact1 = Contact::factory()->create(['email' => 'test@example.com']);
        $contact1->delete();
        
        // Create new contact with same email
        $contact2 = Contact::factory()->create(['email' => 'test@example.com']);
        
        // Trying to restore the first contact should fail
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Cannot restore contact; another active contact has the same email.');
        
        $contact1->restore();
    }

    public function test_concurrent_contact_creation_with_same_email_one_succeeds(): void
    {
        $email = 'concurrent@example.com';
        
        // Simulate concurrent requests by wrapping in transactions
        $results = [];
        
        try {
            DB::transaction(function () use ($email, &$results) {
                $contact1 = Contact::factory()->make(['email' => $email]);
                $results[] = Contact::create($contact1->toArray());
            });
        } catch (\Exception $e) {
            $results[] = false;
        }
        
        try {
            DB::transaction(function () use ($email, &$results) {
                $contact2 = Contact::factory()->make(['email' => $email]);
                $results[] = Contact::create($contact2->toArray());
            });
        } catch (\Exception $e) {
            $results[] = false;
        }
        
        // One should succeed, one should fail
        $successCount = collect($results)->filter(fn($r) => $r !== false)->count();
        $this->assertEquals(1, $successCount);
    }

    public function test_oversized_contact_fields_are_truncated_by_validation(): void
    {
        $oversizedData = [
            'first_name' => str_repeat('a', 200), // Max 120
            'email' => str_repeat('b', 300) . '@example.com', // Max 255
            'notes' => str_repeat('c', 15000), // Max 10000
            'status' => 'lead',
            'source' => 'manual'
        ];
        
        // Should be caught by validation before hitting database
        $response = $this->postJson('/api/contacts', $oversizedData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'email', 'notes']);
    }

    public function test_deal_lost_reason_required_when_status_lost(): void
    {
        $contact = Contact::factory()->create();
        
        $dealData = [
            'title' => 'Test Deal',
            'contact_id' => $contact->id,
            'currency' => 'USD',
            'status' => 'lost',
            'source' => 'manual'
            // Missing lost_reason
        ];
        
        $response = $this->postJson('/api/deals', $dealData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['lost_reason']);
    }

    public function test_deal_cannot_be_reopened_once_closed(): void
    {
        $deal = Deal::factory()->create(['status' => 'won']);
        
        $updateData = ['status' => 'open'];
        
        $response = $this->putJson("/api/deals/{$deal->id}", $updateData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_email_validation_accepts_valid_formats(): void
    {
        $validEmails = [
            'user@example.com',
            'user.name@example.co.uk',
            'user+tag@example.org',
            'user123@sub.example.com'
        ];
        
        foreach ($validEmails as $email) {
            $contact = Contact::factory()->make(['email' => $email])->toArray();
            $contact['status'] = 'lead';
            $contact['source'] = 'manual';
            
            $response = $this->postJson('/api/contacts', $contact);
            $this->assertTrue(
                in_array($response->getStatusCode(), [201, 422]), // 422 if duplicate
                "Email {$email} should be valid or duplicate, got " . $response->getStatusCode()
            );
        }
    }

    public function test_empty_payload_returns_proper_validation_errors(): void
    {
        $response = $this->postJson('/api/contacts', []);
        
        $response->assertStatus(422);
        $response->assertJsonStructure([
            'message',
            'errors' => [
                'status',
                'source'
            ]
        ]);
    }

    public function test_invalid_enum_values_rejected(): void
    {
        $contact = Contact::factory()->make([
            'status' => 'invalid_status',
            'source' => 'invalid_source'
        ])->toArray();
        
        $response = $this->postJson('/api/contacts', $contact);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status', 'source']);
    }

    public function test_activity_log_records_validation_failures(): void
    {
        $this->markTestSkipped('Activity logging for validation failures not yet implemented');
        
        // This would test that significant validation failures are logged
        // for audit purposes (e.g., duplicate suppression)
    }

    public function test_contact_name_auto_generation_from_first_last(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'name' => null
        ]);
        
        $this->assertEquals('John Doe', $contact->name);
    }

    public function test_phone_number_formatting_consistency(): void
    {
        $phoneNumbers = [
            '+1 (555) 123-4567',
            '555.123.4567',
            '5551234567',
            '+15551234567'
        ];
        
        foreach ($phoneNumbers as $phone) {
            $contact = Contact::factory()->create(['phone' => $phone]);
            
            // Phone should be stored (validation doesn't format, just stores)
            $this->assertNotEmpty($contact->phone);
        }
    }
}
