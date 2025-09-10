<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;

class SimpleTest
{
    private $app;
    
    public function __construct()
    {
        $this->app = require_once 'bootstrap/app.php';
        $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    }
    
    public function testAdminRoute()
    {
        echo "Testing admin route...\n";
        
        // Create user
        $user = \App\Models\User::factory()->create();
        
        // Assign admin role
        $user->assignRole('admin');
        
        // Test the route
        $response = $this->actingAs($user)->get('/admin');
        
        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response content: " . $response->getContent() . "\n";
    }
    
    private function actingAs($user)
    {
        auth()->login($user);
        return $this;
    }
    
    private function get($uri)
    {
        $request = \Illuminate\Http\Request::create($uri, 'GET');
        return $this->app->handle($request);
    }
}

try {
    $test = new SimpleTest();
    $test->testAdminRoute();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}