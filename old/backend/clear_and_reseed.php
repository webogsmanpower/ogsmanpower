<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\FormSection;
use App\Models\FormField;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Clear existing seeker profile forms
FormSection::where('module', 'seeker_profile')->delete();
FormField::whereHas('section', function($q) { $q->where('module', 'seeker_profile'); })->delete();

echo "Cleared existing seeker profile forms\n";

// Now run the seeder
$seeder = new Database\Seeders\FormSchemaSeeder();
$seeder->run();

echo "Reseeded with enhanced form structure\n";
