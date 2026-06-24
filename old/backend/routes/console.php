<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sendgrid:test', function () {
    $this->info('Testing SendGrid configuration...');
    
    $apiKey = config('services.sendgrid.api_key');
    
    if (!$apiKey) {
        $this->error('SendGrid API key not found in configuration!');
        $this->info('Please add SENDGRID_API_KEY to your .env file');
        return;
    }
    
    $this->info('SendGrid API key found: ' . substr($apiKey, 0, 10) . '...');
    
    try {
        $sendGrid = new \SendGrid($apiKey);
        
        // Test with a simpler API call that doesn't require sender verification
        $response = $sendGrid->client->user()->email()->get();
        
        if ($response->statusCode() === 200) {
            $this->info('✅ SendGrid API connection successful!');
            $this->info('Status Code: ' . $response->statusCode());
            $this->info('Account is authenticated and working');
        } else {
            $this->error('❌ SendGrid API connection failed!');
            $this->error('Status Code: ' . $response->statusCode());
            $this->error('Response: ' . $response->body());
            
            if ($response->statusCode() === 401) {
                $this->warn('This suggests an invalid API key');
            } elseif ($response->statusCode() === 403) {
                $this->warn('This suggests insufficient permissions or invalid API key');
            }
        }
    } catch (\Exception $e) {
        $this->error('❌ SendGrid connection error: ' . $e->getMessage());
        $this->warn('Please check your API key and internet connection');
    }
})->describe('Test SendGrid API connection');

Artisan::command('sendgrid:test-email {email}', function ($email) {
    $this->info('Sending test email to: ' . $email);
    
    $service = app(\App\Services\SendGridService::class);
    $result = $service->sendEmail(
        $email,
        'Test User',
        'Test Email from OGS App',
        '<h1>Test Email</h1><p>This is a test email from the OGS App using SendGrid.</p><p>Sent at: ' . now() . '</p>'
    );
    
    if ($result['success']) {
        $this->info('✅ Email sent successfully!');
        $this->info('Status Code: ' . $result['status_code']);
    } else {
        $this->error('❌ Failed to send email!');
        $this->error('Error: ' . ($result['error'] ?? 'Unknown error'));
    }
})->describe('Send a test email via SendGrid');
