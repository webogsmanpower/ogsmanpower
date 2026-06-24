<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class TestAdminLogin extends Command
{
    protected $signature = 'admin:test-login';
    protected $description = 'Test admin login credentials';

    public function handle()
    {
        $this->info('Testing admin login...');
        
        // Check if admin user exists
        $admin = User::where('email', 'admin@ogs.com')->first();
        
        if (!$admin) {
            $this->error('Admin user not found!');
            return 1;
        }
        
        $this->info('Admin user found:');
        $this->line('ID: ' . $admin->id);
        $this->line('Email: ' . $admin->email);
        $this->line('Name: ' . $admin->name);
        $this->line('Role field: ' . $admin->role);
        $this->line('Super Admin field: ' . ($admin->super_admin ? 'true' : 'false'));
        $this->line('Has Roles: ' . implode(', ', $admin->roles->pluck('name')->toArray()));
        
        // Test password
        $this->info('Testing password verification...');
        if (Hash::check('Admin@123!', $admin->password)) {
            $this->info('✅ Password is correct');
        } else {
            $this->error('❌ Password is incorrect');
            
            // Reset password
            $this->info('Resetting password...');
            $admin->password = Hash::make('Admin@123!');
            $admin->save();
            $this->info('✅ Password reset to Admin@123!');
        }
        
        // Test role check
        $this->info('Testing role check...');
        if ($admin->hasRole('super_admin')) {
            $this->info('✅ User has super_admin role');
        } else {
            $this->error('❌ User does not have super_admin role');
            
            // Assign role
            $this->info('Assigning super_admin role...');
            $admin->assignRole('super_admin');
            $this->info('✅ super_admin role assigned');
        }
        
        $this->info('✅ Admin login test completed');
        return 0;
    }
}
