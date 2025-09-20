<?php

namespace Tests\Feature;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ContactValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_store_contact_request_validates_required_fields(): void
    {
        $request = new StoreContactRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->messages());
        $this->assertArrayHasKey('source', $validator->errors()->messages());
    }

    public function test_store_contact_request_validates_email_uniqueness(): void
    {
        Contact::factory()->create(['email' => 'test@example.com']);

        $request = new StoreContactRequest();
        $data = [
            'email' => 'test@example.com',
            'status' => 'lead',
            'source' => 'manual'
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->messages());
    }

    public function test_store_contact_request_allows_duplicate_email_when_original_is_soft_deleted(): void
    {
        $contact = Contact::factory()->create(['email' => 'test@example.com']);
        $contact->delete(); // Soft delete

        $request = new StoreContactRequest();
        $data = [
            'email' => 'test@example.com',
            'status' => 'lead',
            'source' => 'manual'
        ];

        $validator = Validator::make($data, $request->rules());
        
        if ($validator->fails()) {
            $this->fail('Validation failed: ' . json_encode($validator->errors()->toArray()));
        }
        
        $this->assertTrue($validator->passes());
    }

    public function test_store_contact_request_normalizes_email(): void
    {
        $request = new StoreContactRequest();
        $request->replace([
            'email' => '  TEST@EXAMPLE.COM  ',
            'status' => 'lead',
            'source' => 'manual'
        ]);

        // Access the method via reflection since it's protected
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertEquals('test@example.com', $request->input('email'));
    }

    public function test_store_contact_request_trims_string_fields(): void
    {
        $request = new StoreContactRequest();
        $request->replace([
            'first_name' => '  John  ',
            'last_name' => '  Doe  ',
            'company' => '  Acme Corp  ',
            'status' => 'lead',
            'source' => 'manual'
        ]);

        // Access the method via reflection since it's protected
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertEquals('John', $request->input('first_name'));
        $this->assertEquals('Doe', $request->input('last_name'));
        $this->assertEquals('Acme Corp', $request->input('company'));
    }

    public function test_store_contact_request_validates_max_lengths(): void
    {
        $request = new StoreContactRequest();
        $data = [
            'first_name' => str_repeat('a', 121),
            'last_name' => str_repeat('b', 121),
            'name' => str_repeat('c', 241),
            'email' => str_repeat('d', 250) . '@example.com',
            'phone' => str_repeat('1', 33),
            'company' => str_repeat('e', 181),
            'job_title' => str_repeat('f', 181),
            'notes' => str_repeat('g', 10001),
            'status' => 'lead',
            'source' => 'manual'
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        
        $errors = $validator->errors()->messages();
        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('phone', $errors);
        $this->assertArrayHasKey('company', $errors);
        $this->assertArrayHasKey('job_title', $errors);
        $this->assertArrayHasKey('notes', $errors);
    }

    public function test_store_contact_request_validates_status_enum(): void
    {
        $request = new StoreContactRequest();
        $data = [
            'status' => 'invalid_status',
            'source' => 'manual'
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->messages());
    }

    public function test_store_contact_request_validates_source_enum(): void
    {
        $request = new StoreContactRequest();
        $data = [
            'status' => 'lead',
            'source' => 'invalid_source'
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('source', $validator->errors()->messages());
    }

    public function test_update_contact_request_ignores_current_contact_for_email_uniqueness(): void
    {
        $contact = Contact::factory()->create(['email' => 'test@example.com']);
        $otherContact = Contact::factory()->create(['email' => 'other@example.com']);

        $request = new UpdateContactRequest();
        
        // Mock the route method to return the contact
        $request->setRouteResolver(function () use ($contact) {
            return new class($contact) {
                public function __construct(private $contact) {}
                public function __call($method, $args) {
                    if ($method === 'parameter' || $method === 'contact') {
                        return $this->contact;
                    }
                    return null;
                }
                public function route($param) { 
                    return $param === 'contact' ? $this->contact : null; 
                }
            };
        });

        $data = ['email' => 'test@example.com']; // Same email as current contact

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->passes());
    }

    public function test_update_contact_request_validates_email_uniqueness_against_other_contacts(): void
    {
        $contact = Contact::factory()->create(['email' => 'test@example.com']);
        $otherContact = Contact::factory()->create(['email' => 'other@example.com']);

        $request = new UpdateContactRequest();
        
        // Mock the route method to return the contact
        $request->setRouteResolver(function () use ($contact) {
            return new class($contact) {
                public function __construct(private $contact) {}
                public function __call($method, $args) {
                    if ($method === 'parameter' || $method === 'contact') {
                        return $this->contact;
                    }
                    return null;
                }
                public function route($param) { 
                    return $param === 'contact' ? $this->contact : null; 
                }
            };
        });

        $data = ['email' => 'other@example.com']; // Different email that exists

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->messages());
    }

    public function test_valid_contact_data_passes_validation(): void
    {
        $user = User::factory()->create();
        
        $request = new StoreContactRequest();
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'company' => 'Acme Corp',
            'job_title' => 'Manager',
            'status' => 'lead',
            'source' => 'website_form',
            'owner_id' => $user->id,
            'notes' => 'Some notes here'
        ];

        $validator = Validator::make($data, $request->rules());
        
        if ($validator->fails()) {
            $this->fail('Validation failed: ' . json_encode($validator->errors()->toArray()));
        }
        
        $this->assertTrue($validator->passes());
    }
}
