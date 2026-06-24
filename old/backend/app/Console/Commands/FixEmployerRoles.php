<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class FixEmployerRoles extends Command
{
    protected $signature = 'employer:fix-roles';
    protected $description = 'Assign employer_owner role to existing employer users';

    public function handle()
    {
        $this->info('Fixing employer roles...');
        
        $employers = User::where('role', 'employer')->get();
        
        foreach ($employers as $employer) {
            if (!$employer->hasRole('employer_owner')) {
                $employer->assignRole('employer_owner');
                $this->info("✅ Assigned employer_owner role to: {$employer->email}");
            } else {
                $this->info("ℹ️  Already has employer_owner role: {$employer->email}");
            }
        }
        
        $this->info('Employer roles fixed successfully!');
        
        return 0;
    }
}
