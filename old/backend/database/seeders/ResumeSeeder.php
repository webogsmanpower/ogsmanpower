<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SeekerResume;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ResumeSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with test resume data.
     */
    public function run(): void
    {
        // Get the test user or create one
        $user = User::where('email', 'test@example.com')->first();
        
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Create or update resume for the test user
        $resume = SeekerResume::updateOrCreate(
            ['user_id' => $user->id],
            [
                'profile_completion' => 45,
                'primary_language' => 'en',
                'is_rtl' => false,
                'resume_format' => 'security-guard',
                'basic_information' => [
                    'profile_photo' => null,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'father_name' => 'James',
                    'mother_name' => 'Mary',
                    'phone' => [
                        'code' => '+971',
                        'number' => '501234567',
                    ],
                    'whatsapp_number' => [
                        'code' => '+971',
                        'number' => '501234567',
                    ],
                    'email' => 'john.doe@example.com',
                    'emergency_contact_name' => 'Jane Doe',
                    'emergency_contact_phone' => [
                        'code' => '+971',
                        'number' => '509876543',
                    ],
                    'address' => '123 Main Street, Dubai',
                    'state_province' => 'Dubai',
                    'city' => 'Dubai',
                    'zip_code' => '12345',
                    'country' => 'ae',
                ],
                'professional_summary' => [
                    'professional_summary' => 'Experienced security professional with 5+ years in the industry.',
                    'career_objective' => 'Seeking a challenging role in security management.',
                    'industry_experience' => 'Security, Loss Prevention, Risk Management',
                ],
                'work_experience' => [
                    [
                        'role_title' => 'Security Officer',
                        'company_name' => 'ABC Security Services',
                        'location' => 'Dubai, UAE',
                        'start_date' => '2019-01-15',
                        'end_date' => '2023-12-31',
                        'currently_working' => false,
                        'description' => 'Managed security operations for commercial buildings.',
                    ],
                    [
                        'role_title' => 'Security Supervisor',
                        'company_name' => 'XYZ Security Corp',
                        'location' => 'Abu Dhabi, UAE',
                        'start_date' => '2024-01-01',
                        'end_date' => null,
                        'currently_working' => true,
                        'description' => 'Supervising security team and managing daily operations.',
                    ],
                ],
                'education' => [
                    [
                        'degree_title' => 'Diploma in Security Management',
                        'institution_name' => 'Dubai Security Institute',
                        'graduation_year' => '2018',
                        'field_of_study' => 'Security Management',
                    ],
                ],
                'skills' => [
                    [
                        'name' => 'Security Operations',
                        'rating' => 8,
                        'description' => 'Expert in managing security operations and protocols',
                    ],
                    [
                        'name' => 'Team Leadership',
                        'rating' => 7,
                        'description' => 'Strong leadership and team management skills',
                    ],
                    [
                        'name' => 'Risk Assessment',
                        'rating' => 7,
                        'description' => 'Proficient in identifying and assessing security risks',
                    ],
                ],
                'languages' => [
                    [
                        'language_name' => 'English',
                        'proficiency_level' => 'fluent',
                    ],
                    [
                        'language_name' => 'Arabic',
                        'proficiency_level' => 'intermediate',
                    ],
                ],
                'certifications' => [
                    [
                        'certification_name' => 'Security Guard License',
                        'issuing_organization' => 'UAE Ministry of Interior',
                        'issue_date' => '2019-01-01',
                        'expiry_date' => '2026-01-01',
                    ],
                ],
                'references' => [
                    [
                        'reference_name' => 'Ahmed Al-Mansouri',
                        'reference_title' => 'Former Manager',
                        'reference_phone' => '+971501234567',
                        'reference_email' => 'ahmed@example.com',
                    ],
                ],
                'job_preferences' => [
                    'preferred_role' => 'Security Manager',
                    'preferred_location' => 'Dubai, UAE',
                    'expected_salary' => '5000-7000',
                    'employment_type' => 'Full-time',
                ],
                'availability' => [
                    'notice_period' => '30 days',
                    'available_from' => now()->addDays(30)->format('Y-m-d'),
                ],
                'height' => 180,
                'weight' => 75,
                'chest_measurement' => 95,
                'license_number' => 'SG-2019-12345',
                'license_expiry' => '2026-01-01',
            ]
        );

        echo "Resume data seeded for user: {$user->email}\n";
    }
}
