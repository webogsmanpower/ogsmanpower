<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Mail\EmailVerificationCode;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\Mail;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Create a test verification
$verification = new EmailVerification([
    'code' => '123456',
    'email' => 'test@example.com',
]);

try {
    // Test email sending
    Mail::to('test@example.com')->send(new EmailVerificationCode($verification));
    echo "Email sent successfully!\n";
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage() . "\n";
    echo "Check your .env mail configuration\n";
}
