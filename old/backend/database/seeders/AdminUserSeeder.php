<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin users for testing
        $adminUsers = [
            [
                'name' => 'Super Admin',
                'email' => 'admin@ogs.com',
                'password' => Hash::make('password'), // Change this in production!
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Test Admin',
                'email' => 'testadmin@ogs.com',
                'password' => Hash::make('password'), // Change this in production!
                'role' => 'admin',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($adminUsers as $adminUser) {
            // Check if user already exists
            $existingUser = User::where('email', $adminUser['email'])->first();
            
            if (!$existingUser) {
                User::create($adminUser);
                $this->command->info("✅ Created admin user: {$adminUser['email']}");
            } else {
                $this->command->info("ℹ️  Admin user already exists: {$adminUser['email']}");
            }
        }

        $this->command->info('✅ Admin users seeded successfully!');
        $this->command->info('📝 Login credentials:');
        $this->command->info('   Email: admin@ogs.com');
        $this->command->info('   Password: password');
    }
}
