<?php

namespace Tests\Feature;

use App\Http\Requests\StoreDealRequest;
use App\Http\Requests\UpdateDealRequest;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DealValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_store_deal_request_validates_required_fields(): void
    {
        $request = new StoreDealRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->messages();
        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('contact_id', $errors);
        $this->assertArrayHasKey('currency', $errors);
        $this->assertArrayHasKey('status', $errors);
        $this->assertArrayHasKey('source', $errors);
    }

    public function test_store_deal_request_validates_title_max_length(): void
    {
        $contact = Contact::factory()->create();
        
        $request = new StoreDealRequest();
        $data = [
            'title' => str_repeat('a', 256), // Too long
            'contact_id' => $contact->id,
            'currency' => 'USD',
            'status' => 'open',
            'source' => 'manual'
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('title', $validator->errors()->messages());
    }

    public function test_store_deal_request_validates_contact_exists(): void
    {
        $request = new StoreDealRequest();
        $data = [
            'title' => 'Test Deal',
            'contact_id' => 99999, // Non-existent contact
            'currency' => 'USD',
            'status' => 'open',
            'source' => 'manual'
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('contact_id', $validator->errors()->messages());
    }

    public function test_store_deal_request_validates_amount_range(): void
    {
        $contact = Contact::factory()->create();
        
        $request = new StoreDealRequest();
        $data = [
            'title' => 'Test Deal',
            'contact_id' => $contact->id,
            'amount' => -100, // Negative amount
            'currency' => 'USD',
            'status' => 'open',
            'source' => 'manual'
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->messages());
    }

    public function test_store_deal_request_validates_currency_length(): void
    {
        $contact = Contact::factory()->create();
        
        $request = new StoreDealRequest();
        $data = [
            'title' => 'Test Deal',
            'contact_id' => $contact->id,
            'currency' => 'USDD', // Too long
            'status' => 'open',
            'source' => 'manual'
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('currency', $validator->errors()->messages());
    }

    public function test_store_deal_request_validates_status_enum(): void
    {
        $contact = Contact::factory()->create();
        
        $request = new StoreDealRequest();
        $data = [
            'title' => 'Test Deal',
            'contact_id' => $contact->id,
            'currency' => 'USD',
            'status' => 'invalid_status',
            'source' => 'manual'
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->messages());
    }

    public function test_store_deal_request_validates_source_enum(): void
    {
        $contact = Contact::factory()->create();
        
        $request = new StoreDealRequest();
        $data = [
            'title' => 'Test Deal',
            'contact_id' => $contact->id,
            'currency' => 'USD',
            'status' => 'open',
            'source' => 'invalid_source'
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('source', $validator->errors()->messages());
    }

    public function test_store_deal_request_validates_probability_range(): void
    {
        $contact = Contact::factory()->create();
        
        $request = new StoreDealRequest();
        $data = [
            'title' => 'Test Deal',
            'contact_id' => $contact->id,
            'currency' => 'USD',
            'status' => 'open',
            'source' => 'manual',
            'probability' => 150 // Too high
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('probability', $validator->errors()->messages());
    }

    public function test_store_deal_request_validates_expected_close_date_not_in_past(): void
    {
        $contact = Contact::factory()->create();
        
        $request = new StoreDealRequest();
        $data = [
            'title' => 'Test Deal',
            'contact_id' => $contact->id,
            'currency' => 'USD',
            'status' => 'open',
            'source' => 'manual',
            'expected_close_date' => '2020-01-01' // Past date
        ];

        $validator = Validator::make($data, $request->rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('expected_close_date', $validator->errors()->messages());
    }

    public function test_store_deal_request_requires_lost_reason_when_status_is_lost(): void
    {
        $contact = Contact::factory()->create();
        
        $request = new StoreDealRequest();
        $request->replace([
            'title' => 'Test Deal',
            'contact_id' => $contact->id,
            'currency' => 'USD',
            'status' => 'lost',
            'source' => 'manual'
            // Missing lost_reason
        ]);

        $validator = Validator::make($request->all(), $request->rules());
        
        // Call withValidator to add custom rules
        $request->withValidator($validator);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('lost_reason', $validator->errors()->messages());
    }

    public function test_store_deal_request_normalizes_currency(): void
    {
        $request = new StoreDealRequest();
        $request->replace([
            'currency' => ' usd ',
        ]);

        // Access the method via reflection since it's protected
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertEquals('USD', $request->input('currency'));
    }

    public function test_store_deal_request_trims_string_fields(): void
    {
        $request = new StoreDealRequest();
        $request->replace([
            'title' => '  Test Deal  ',
            'lost_reason' => '  Lost reason  ',
            'notes' => '  Some notes  ',
        ]);

        // Access the method via reflection since it's protected
        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        $this->assertEquals('Test Deal', $request->input('title'));
        $this->assertEquals('Lost reason', $request->input('lost_reason'));
        $this->assertEquals('Some notes', $request->input('notes'));
    }

    public function test_update_deal_request_prevents_reopening_closed_deal(): void
    {
        $deal = Deal::factory()->create(['status' => 'won']);
        
        $request = new UpdateDealRequest();
        $request->setRouteResolver(function () use ($deal) {
            return new class($deal) {
                public function __construct(private $deal) {}
                public function __call($method, $args) {
                    if ($method === 'parameter' || $method === 'deal') {
                        return $this->deal;
                    }
                    return null;
                }
                public function route($param) { 
                    return $param === 'deal' ? $this->deal : null; 
                }
            };
        });

        $request->replace(['status' => 'open']); // Trying to reopen

        $validator = Validator::make($request->all(), $request->rules());
        
        // Call withValidator to add custom rules
        $request->withValidator($validator);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('status', $validator->errors()->messages());
    }

    public function test_valid_deal_data_passes_validation(): void
    {
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $product = Product::factory()->create();
        
        $request = new StoreDealRequest();
        $data = [
            'title' => 'Test Deal',
            'contact_id' => $contact->id,
            'product_id' => $product->id,
            'owner_id' => $user->id,
            'amount' => 1000.50,
            'currency' => 'USD',
            'status' => 'open',
            'expected_close_date' => now()->addDays(30)->format('Y-m-d'),
            'probability' => 75,
            'source' => 'website_form',
            'notes' => 'Some notes here'
        ];

        $validator = Validator::make($data, $request->rules());
        
        if ($validator->fails()) {
            $this->fail('Validation failed: ' . json_encode($validator->errors()->toArray()));
        }
        
        $this->assertTrue($validator->passes());
    }
}
