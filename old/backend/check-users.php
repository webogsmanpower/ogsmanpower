<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "Checking users...\n";

// Check for employer users
$employers = App\Models\User::where('role', 'employer')->get();
echo "Employer users found: " . $employers->count() . "\n";

foreach ($employers as $employer) {
    echo "ID: {$employer->id}, Email: {$employer->email}\n";
}

// Check for admin user
$admin = App\Models\User::find(1);
if ($admin) {
    echo "Admin user: ID {$admin->id}, Email: {$admin->email}, Role: {$admin->role}\n";
}

echo "Done.\n";
