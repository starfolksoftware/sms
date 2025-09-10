<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test basic routing
echo "Testing basic routes...\n";

// Check if RolePermissionSeeder exists
if (class_exists('Database\Seeders\RolePermissionSeeder')) {
    echo "RolePermissionSeeder class exists\n";
} else {
    echo "RolePermissionSeeder class NOT found\n";
}

echo "Laravel application loaded successfully\n";