<?php

namespace Database\Seeders;

use App\Models\SystemSettings;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Branding Settings
            [
                'key' => 'app_name',
                'value' => 'Overseas Jobs',
                'group' => 'branding',
                'type' => 'string',
                'description' => 'Application name displayed in headers and emails',
                'is_public' => true,
            ],
            [
                'key' => 'app_name_ar',
                'value' => 'وظائف الخارج',
                'group' => 'branding',
                'type' => 'string',
                'description' => 'Application name in Arabic',
                'is_public' => true,
            ],
            [
                'key' => 'app_logo',
                'value' => null,
                'group' => 'branding',
                'type' => 'file',
                'description' => 'Application logo file path',
                'is_public' => true,
            ],
            [
                'key' => 'app_favicon',
                'value' => null,
                'group' => 'branding',
                'type' => 'file',
                'description' => 'Browser favicon file path',
                'is_public' => true,
            ],
            [
                'key' => 'primary_color',
                'value' => '#3b82f6',
                'group' => 'branding',
                'type' => 'string',
                'description' => 'Primary brand color (hex)',
                'is_public' => true,
            ],
            [
                'key' => 'secondary_color',
                'value' => '#1e40af',
                'group' => 'branding',
                'type' => 'string',
                'description' => 'Secondary brand color (hex)',
                'is_public' => true,
            ],

            // General Settings
            [
                'key' => 'maintenance_mode',
                'value' => '0',
                'group' => 'general',
                'type' => 'boolean',
                'description' => 'Enable maintenance mode',
                'is_public' => false,
            ],
            [
                'key' => 'registration_enabled',
                'value' => '1',
                'group' => 'general',
                'type' => 'boolean',
                'description' => 'Allow new user registrations',
                'is_public' => true,
            ],
            [
                'key' => 'default_language',
                'value' => 'en',
                'group' => 'general',
                'type' => 'string',
                'description' => 'Default application language',
                'is_public' => true,
            ],

            // Security Settings
            [
                'key' => 'max_login_attempts',
                'value' => '5',
                'group' => 'security',
                'type' => 'integer',
                'description' => 'Maximum failed login attempts before lockout',
                'is_public' => false,
            ],
            [
                'key' => 'password_expiry_days',
                'value' => '90',
                'group' => 'security',
                'type' => 'integer',
                'description' => 'Days until password expiry (0 = never)',
                'is_public' => false,
            ],
        ];

        foreach ($settings as $setting) {
            SystemSettings::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('System settings seeded successfully.');
    }
}
