<?php

namespace App\Console\Commands;

use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmailVerificationCode;

class TestOTPVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:otp-verification {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test OTP email verification with SendGrid';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Testing OTP verification for: {$email}");
        
        // Find or create user
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found.");
            $this->info("Creating test user...");
            
            $user = User::create([
                'name' => 'Test User',
                'email' => $email,
                'password' => bcrypt('password123'),
            ]);
        }
        
        // Delete existing verification codes
        EmailVerification::where('user_id', $user->id)->delete();
        
        // Create new verification code
        $verification = EmailVerification::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(30),
        ]);
        
        $this->info("Generated verification code: {$verification->code}");
        
        try {
            // Send email using Laravel's Mail system (should use SendGrid)
            Mail::to($user->email)->send(new EmailVerificationCode($verification));
            
            $this->info("✅ OTP email sent successfully via SendGrid!");
            $this->info("Check your email for the verification code.");
            $this->info("Expected code: {$verification->code}");
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to send OTP email: " . $e->getMessage());
            
            // Check if it's a configuration issue
            if (str_contains($e->getMessage(), 'transport')) {
                $this->warn("This might be a SendGrid transport configuration issue.");
                $this->info("Current MAIL_MAILER: " . config('mail.default'));
                $this->info("SendGrid configured: " . (config('mail.mailers.sendgrid') ? 'Yes' : 'No'));
            }
        }
        
        return 0;
    }
}
