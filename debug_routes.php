<?php

// Let's manually check what's happening with our routes

require_once 'bootstrap/app.php';

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Checking route definitions...\n\n";

// Get all routes
$routes = Route::getRoutes();

echo "Looking for admin route:\n";
foreach ($routes as $route) {
    if ($route->uri() === 'admin') {
        echo "Found admin route:\n";
        echo "  URI: " . $route->uri() . "\n";
        echo "  Methods: " . implode(', ', $route->methods()) . "\n";
        echo "  Action: " . $route->getActionName() . "\n";
        echo "  Middleware: " . implode(', ', $route->middleware()) . "\n\n";
    }
}

echo "Looking for settings/roles routes:\n";
foreach ($routes as $route) {
    if (strpos($route->uri(), 'settings/roles') !== false) {
        echo "Found settings/roles route:\n";
        echo "  URI: " . $route->uri() . "\n";
        echo "  Methods: " . implode(', ', $route->methods()) . "\n";
        echo "  Action: " . $route->getActionName() . "\n";
        echo "  Middleware: " . implode(', ', $route->middleware()) . "\n\n";
    }
}

echo "Checking if permissions and roles exist:\n";

// Test database connection
try {
    echo "Testing database connection...\n";
    $userCount = \App\Models\User::count();
    echo "Users in database: $userCount\n";
    
    $roleCount = \Spatie\Permission\Models\Role::count();
    echo "Roles in database: $roleCount\n";
    
    $permissionCount = \Spatie\Permission\Models\Permission::count();
    echo "Permissions in database: $permissionCount\n";
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\nDone.\n";