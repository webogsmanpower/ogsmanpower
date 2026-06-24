<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * AdminSeeder
 * 
 * Creates default Super Admin account for the system.
 * Run with: php artisan db:seed --class=AdminSeeder
 */
class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin if not exists
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@ogs.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123!'),
                'role' => 'admin',
                'super_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Super Admin created/found: {$superAdmin->email}");

        // Create a secondary admin for testing (optional)
        $secondaryAdmin = User::firstOrCreate(
            ['email' => 'moderator@ogs.com'],
            [
                'name' => 'Moderator',
                'password' => Hash::make('Mod@123!'),
                'role' => 'admin',
                'super_admin' => false,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Secondary Admin created/found: {$secondaryAdmin->email}");
    }
}
