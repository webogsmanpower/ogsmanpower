<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ResetAdminPasswordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset admin@ogs.com password
        $adminUser = User::where('email', 'admin@ogs.com')->first();
        
        if ($adminUser) {
            $adminUser->password = Hash::make('password');
            $adminUser->save();
            $this->command->info('✅ Password reset for admin@ogs.com');
            $this->command->info('📝 New password: password');
        } else {
            $this->command->error('❌ User admin@ogs.com not found');
        }

        // Reset testadmin@ogs.com password
        $testUser = User::where('email', 'testadmin@ogs.com')->first();
        
        if ($testUser) {
            $testUser->password = Hash::make('password');
            $testUser->save();
            $this->command->info('✅ Password reset for testadmin@ogs.com');
            $this->command->info('📝 New password: password');
        } else {
            $this->command->error('❌ User testadmin@ogs.com not found');
        }

        $this->command->info("\n🔐 Updated Login Credentials:");
        $this->command->info("Email: admin@ogs.com");
        $this->command->info("Password: password");
        $this->command->info("\nAlternative:");
        $this->command->info("Email: testadmin@ogs.com");
        $this->command->info("Password: password");
    }
}
