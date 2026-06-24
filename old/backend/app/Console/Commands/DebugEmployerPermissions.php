<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class DebugEmployerPermissions extends Command
{
    protected $signature = 'employer:debug {email?}';
    protected $description = 'Debug employer permissions and verification status';

    public function handle()
    {
        $email = $this->argument('email');
        
        if ($email) {
            $user = User::where('email', $email)->first();
            if (!$user) {
                $this->error("User with email {$email} not found");
                return 1;
            }
            $this->debugUser($user);
        } else {
            $employers = User::where('role', 'employer')->with('employer')->get();
            foreach ($employers as $user) {
                $this->debugUser($user);
                $this->info(str_repeat('-', 50));
            }
        }
        
        return 0;
    }
    
    private function debugUser($user)
    {
        $this->info("=== Employer Debug Info ===");
        $this->info("Email: {$user->email}");
        $this->info("User ID: {$user->id}");
        $this->info("Role: {$user->role}");
        
        // Employer profile
        if ($user->employer) {
            $this->info("Company: {$user->employer->company_name}");
            $this->info("Verification Status: {$user->employer->verification_status}");
            $this->info("Is Verified: " . ($user->employer->is_verified ? '✅ Yes' : '❌ No'));
        } else {
            $this->error("❌ No employer profile found");
        }
        
        // Spatie roles
        $roles = $user->roles->pluck('name')->implode(', ') ?: 'None';
        $this->info("Spatie Roles: {$roles}");
        
        // Key permissions
        $this->info("Permissions:");
        $this->info("  - employer.post_job: " . ($user->can('employer.post_job') ? '✅ Yes' : '❌ No'));
        $this->info("  - employer.edit_job: " . ($user->can('employer.edit_job') ? '✅ Yes' : '❌ No'));
        $this->info("  - employer.delete_job: " . ($user->can('employer.delete_job') ? '✅ Yes' : '❌ No'));
        $this->info("  - employer.view_job_analytics: " . ($user->can('employer.view_job_analytics') ? '✅ Yes' : '❌ No'));
        
        // Middleware check
        $canPostJob = $user->employer && $user->employer->is_verified && $user->can('employer.post_job');
        $this->info("Can Post Job (All Checkpoints): " . ($canPostJob ? '✅ Yes' : '❌ No'));
        
        if (!$canPostJob) {
            $this->error("Issues found:");
            if (!$user->employer) {
                $this->error("  - No employer profile");
            }
            if ($user->employer && !$user->employer->is_verified) {
                $this->error("  - Employer not verified");
            }
            if (!$user->can('employer.post_job')) {
                $this->error("  - Missing employer.post_job permission");
            }
        }
    }
}
