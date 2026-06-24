<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing plans
        Plan::query()->delete();

        // Create sample plans
        $plans = [
            // Seeker Plans
            [
                'name' => 'Basic Plan',
                'name_ar' => 'خطة أساسية',
                'slug' => 'basic-plan',
                'description' => 'Perfect for getting started with your job search',
                'description_ar' => 'مثالي للبدء في البحث عن وظيفة',
                'price' => 0,
                'interval' => 'monthly',
                'is_addon' => false,
                'role_type' => 'seeker',
                'features' => [
                    'Apply to 5 jobs per month',
                    'Basic profile visibility',
                    'Email support',
                    'CV download (2 per month)'
                ],
                'limits' => [
                    'applications' => 5,
                    'cv_downloads' => 2,
                    'featured_profile' => false,
                    'bilingual_cv' => false,
                ],
                'is_active' => true,
                'sort_order' => 1,
                'trial_days' => 0,
            ],
            [
                'name' => 'Professional Plan',
                'name_ar' => 'خطة احترافية',
                'slug' => 'professional-plan',
                'description' => 'Most popular choice for serious job seekers',
                'description_ar' => 'الخيار الأكثر شعبية للباحثين عن عمل الجادين',
                'price' => 29.99,
                'interval' => 'monthly',
                'is_addon' => false,
                'role_type' => 'seeker',
                'features' => [
                    'Unlimited applications',
                    'Featured profile',
                    'Priority support',
                    'CV downloads (10 per month)',
                    'Resume review service',
                    'Bilingual CV generation'
                ],
                'limits' => [
                    'applications' => -1,
                    'cv_downloads' => 10,
                    'featured_profile' => true,
                    'bilingual_cv' => true,
                    'resume_review' => 1,
                ],
                'is_active' => true,
                'sort_order' => 2,
                'trial_days' => 7,
            ],
            [
                'name' => 'Premium Plan',
                'name_ar' => 'خطة متميزة',
                'slug' => 'premium-plan',
                'description' => 'Ultimate package for maximum career opportunities',
                'description_ar' => 'الحزمة النهائية لأقصى فرص مهنية',
                'price' => 49.99,
                'interval' => 'monthly',
                'is_addon' => false,
                'role_type' => 'seeker',
                'features' => [
                    'Everything in Professional',
                    'Unlimited CV downloads',
                    'Personal career coaching',
                    'Interview preparation',
                    'Salary negotiation tips',
                    'Priority job matching'
                ],
                'limits' => [
                    'applications' => -1,
                    'cv_downloads' => -1,
                    'featured_profile' => true,
                    'bilingual_cv' => true,
                    'resume_review' => -1,
                    'career_coaching' => 2,
                ],
                'is_active' => true,
                'sort_order' => 3,
                'trial_days' => 14,
            ],

            // Employer Plans
            [
                'name' => 'Startup Plan',
                'name_ar' => 'خطة ناشئين',
                'slug' => 'startup-plan',
                'description' => 'Perfect for small businesses and startups',
                'description_ar' => 'مثالي للشركات الصغيرة والشركات الناشئة',
                'price' => 49.99,
                'interval' => 'monthly',
                'is_addon' => false,
                'role_type' => 'employer',
                'features' => [
                    'Post 5 jobs per month',
                    'Access to 50 CVs',
                    'Basic support',
                    'Company profile'
                ],
                'limits' => [
                    'job_posts' => 5,
                    'cv_access' => 50,
                    'featured_jobs' => 0,
                    'urgent_label' => 0,
                    'database_access' => true,
                    'company_highlighting' => false,
                ],
                'is_active' => true,
                'sort_order' => 1,
                'trial_days' => 0,
            ],
            [
                'name' => 'Business Plan',
                'name_ar' => 'خطة أعمال',
                'slug' => 'business-plan',
                'description' => 'Most popular for growing companies',
                'description_ar' => 'الأكثر شعبية للشركات النامية',
                'price' => 99.99,
                'interval' => 'monthly',
                'is_addon' => false,
                'role_type' => 'employer',
                'features' => [
                    'Post 20 jobs per month',
                    'Access to 500 CVs',
                    'Priority support',
                    '5 featured jobs per month',
                    'Company highlighting',
                    'Applicant tracking'
                ],
                'limits' => [
                    'job_posts' => 20,
                    'cv_access' => 500,
                    'featured_jobs' => 5,
                    'urgent_label' => 2,
                    'database_access' => true,
                    'company_highlighting' => true,
                ],
                'is_active' => true,
                'sort_order' => 2,
                'trial_days' => 7,
            ],
            [
                'name' => 'Enterprise Plan',
                'name_ar' => 'خطة مؤسسات',
                'slug' => 'enterprise-plan',
                'description' => 'Complete solution for large organizations',
                'description_ar' => 'حل كامل للمؤسسات الكبيرة',
                'price' => 299.99,
                'interval' => 'monthly',
                'is_addon' => false,
                'role_type' => 'employer',
                'features' => [
                    'Unlimited job postings',
                    'Unlimited CV access',
                    'Dedicated account manager',
                    'Custom branding',
                    'Advanced analytics',
                    'API access',
                    'Bulk import/export'
                ],
                'limits' => [
                    'job_posts' => -1,
                    'cv_access' => -1,
                    'featured_jobs' => -1,
                    'urgent_label' => -1,
                    'database_access' => true,
                    'company_highlighting' => true,
                    'api_access' => true,
                ],
                'is_active' => true,
                'sort_order' => 3,
                'trial_days' => 14,
            ],

            // Add-ons
            [
                'name' => 'CV Database Access',
                'name_ar' => 'الوصول إلى قاعدة بيانات السير الذاتية',
                'slug' => 'cv-database-access',
                'description' => 'Access our extensive CV database',
                'description_ar' => 'الوصول إلى قاعدة بيانات السير الذاتية الواسعة',
                'price' => 19.99,
                'interval' => 'monthly',
                'is_addon' => true,
                'role_type' => 'employer',
                'features' => [
                    'Access to 1000 additional CVs',
                    'Advanced search filters',
                    'Export functionality'
                ],
                'limits' => [
                    'cv_access' => 1000,
                ],
                'is_active' => true,
                'sort_order' => 1,
                'trial_days' => 0,
            ],
            [
                'name' => 'Resume Review Service',
                'name_ar' => 'خدمة مراجعة السيرة الذاتية',
                'slug' => 'resume-review-service',
                'description' => 'Professional resume review and feedback',
                'description_ar' => 'مراجعة احترافية للسيرة الذاتية وتقديم ملاحظات',
                'price' => 9.99,
                'interval' => 'one_time',
                'is_addon' => true,
                'role_type' => 'seeker',
                'features' => [
                    'Professional resume review',
                    'Detailed feedback report',
                    'Improvement suggestions'
                ],
                'limits' => [
                    'resume_review' => 1,
                ],
                'is_active' => true,
                'sort_order' => 1,
                'trial_days' => 0,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::create($plan);
        }

        $this->command->info('✅ Sample plans created successfully!');
    }
}
