<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Employer;

class CheckEmployerStatus extends Command
{
    protected $signature = 'employer:check-status';
    protected $description = 'Check employer verification and permission status';

    public function handle()
    {
        $this->info('Checking employer status...');
        
        $employers = User::where('role', 'employer')->with('employer')->get();
        
        foreach ($employers as $user) {
            $this->info("Email: {$user->email}");
            $this->info("User ID: {$user->id}");
            
            if ($user->employer) {
                $this->info("Verification Status: {$user->employer->verification_status}");
                $this->info("Is Verified: " . ($user->employer->is_verified ? 'Yes' : 'No'));
            } else {
                $this->info("No employer profile found");
            }
            
            $this->info("Has employer_owner role: " . ($user->hasRole('employer_owner') ? 'Yes' : 'No'));
            $this->info("Can post jobs: " . ($user->can('employer.post_job') ? 'Yes' : 'No'));
            $this->info("---");
        }
        
        return 0;
    }
}
