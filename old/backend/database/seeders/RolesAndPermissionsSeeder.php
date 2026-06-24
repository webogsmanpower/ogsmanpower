<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates the RBAC structure for the Admin Command Center:
     * - Super Admin: Full system access
     * - Verification Officer: Document and employer verification
     * - Support Agent: User support and read-only access
     * - Content Manager: Plans, forms, and content management
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =====================================================
        // PERMISSIONS - Organized by Module
        // =====================================================

        $permissions = [
            // =====================================================
            // SYSTEM ADMIN PERMISSIONS (admin.*)
            // =====================================================
            
            // Dashboard
            'admin.view_dashboard',
            'admin.view_analytics',
            'admin.export_reports',

            // Employer Management
            'admin.view_employers',
            'admin.edit_employers',
            'admin.delete_employers',
            'admin.verify_employers',
            'admin.reject_employers',
            'admin.view_employer_documents',
            'admin.download_employer_documents',
            'admin.verify_employer_documents',
            'admin.reject_employer_documents',

            // Seeker Management
            'admin.view_seekers',
            'admin.edit_seekers',
            'admin.delete_seekers',
            'admin.ban_seekers',
            'admin.unban_seekers',
            'admin.verify_seekers',
            'admin.view_seeker_documents',
            'admin.download_seeker_documents',
            'admin.verify_seeker_documents',
            'admin.reject_seeker_documents',

            // Job Management
            'admin.view_jobs',
            'admin.edit_jobs',
            'admin.delete_jobs',
            'admin.approve_jobs',
            'admin.reject_jobs',
            'admin.feature_jobs',

            // User Management (Staff)
            'admin.view_staff',
            'admin.create_staff',
            'admin.edit_staff',
            'admin.delete_staff',
            'admin.assign_roles',

            // Role & Permission Management
            'admin.view_roles',
            'admin.create_roles',
            'admin.edit_roles',
            'admin.delete_roles',
            'admin.view_permissions',
            'admin.assign_permissions',

            // Finance & Plans
            'admin.view_plans',
            'admin.create_plans',
            'admin.edit_plans',
            'admin.delete_plans',
            'admin.view_subscriptions',
            'admin.manage_subscriptions',
            'admin.view_transactions',
            'admin.process_refunds',

            // Form Builder
            'admin.view_form_schemas',
            'admin.create_form_schemas',
            'admin.edit_form_schemas',
            'admin.delete_form_schemas',

            // Configuration
            'admin.view_settings',
            'admin.edit_settings',
            'admin.manage_skills',
            'admin.manage_industries',
            'admin.manage_job_titles',

            // Content Management
            'admin.manage_content',

            // Assessments
            'admin.view_assessments',
            'admin.create_assessments',
            'admin.edit_assessments',
            'admin.delete_assessments',
            'admin.grant_assessment_retries',

            // Audit & Logs
            'admin.view_audit_logs',
            'admin.export_audit_logs',
            'admin.view_activity_logs',

            // Impersonation (Shadow Login)
            'admin.impersonate_users',
            'admin.view_impersonation_history',

            // Staff Management
            'admin.view_staff',
            'admin.create_staff',
            'admin.edit_staff',
            'admin.delete_staff',
            'admin.manage_staff',

            // Roles & Permissions Management
            'admin.view_roles',
            'admin.create_roles',
            'admin.edit_roles',
            'admin.delete_roles',
            'admin.manage_roles',

            // System
            'admin.access_admin_panel',
            'admin.view_system_health',
            'admin.manage_cache',
            'admin.run_maintenance',

            // =====================================================
            // EMPLOYER TEAM PERMISSIONS (employer.*)
            // =====================================================
            
            // Company Management
            'employer.view_company',
            'employer.edit_company',
            'employer.manage_billing',
            'employer.view_billing',
            'employer.upgrade_plan',
            'employer.cancel_subscription',

            // Job Management
            'employer.post_job',
            'employer.edit_job',
            'employer.delete_job',
            'employer.view_job_analytics',
            'employer.feature_job',

            // Candidate Management
            'employer.view_candidates',
            'employer.shortlist_candidate',
            'employer.reject_candidate',
            'employer.interview_candidate',
            'employer.hire_candidate',
            'employer.view_candidate_details',
            'employer.download_candidate_resume',
            'employer.add_candidate_notes',

            // Team Management
            'employer.invite_team',
            'employer.view_team',
            'employer.edit_team',
            'employer.remove_team',
            'employer.assign_team_roles',

            // Communication
            'employer.message_candidates',
            'employer.schedule_interview',
            'employer.send_offer',

            // Reports & Analytics
            'employer.view_reports',
            'employer.export_applications',
            'employer.view_job_metrics',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // =====================================================
        // ROLES - With Permission Assignments
        // =====================================================

        // 1. Super Admin - Full Access
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // 2. Verification Officer - Document & Employer Verification
        $verificationOfficer = Role::firstOrCreate(['name' => 'verification_officer', 'guard_name' => 'web']);
        $verificationOfficer->syncPermissions([
            'admin.access_admin_panel',
            'admin.view_dashboard',
            'admin.view_employers',
            'admin.verify_employers',
            'admin.reject_employers',
            'admin.view_employer_documents',
            'admin.download_employer_documents',
            'admin.verify_employer_documents',
            'admin.reject_employer_documents',
            'admin.view_seekers',
            'admin.verify_seekers',
            'admin.view_seeker_documents',
            'admin.download_seeker_documents',
            'admin.verify_seeker_documents',
            'admin.reject_seeker_documents',
            'admin.view_activity_logs',
        ]);

        // 3. Support Agent - User Support & Read Access
        $supportAgent = Role::firstOrCreate(['name' => 'support_agent', 'guard_name' => 'web']);
        $supportAgent->syncPermissions([
            'admin.access_admin_panel',
            'admin.view_dashboard',
            'admin.view_employers',
            'admin.view_employer_documents',
            'admin.view_seekers',
            'admin.view_seeker_documents',
            'admin.view_jobs',
            'admin.ban_seekers',
            'admin.unban_seekers',
            'admin.view_activity_logs',
            'admin.view_audit_logs',
            'admin.impersonate_users',
            'admin.view_impersonation_history',
        ]);

        // 4. Content Manager - Plans, Forms, Content
        $contentManager = Role::firstOrCreate(['name' => 'content_manager', 'guard_name' => 'web']);
        $contentManager->syncPermissions([
            'admin.access_admin_panel',
            'admin.view_dashboard',
            'admin.view_plans',
            'admin.create_plans',
            'admin.edit_plans',
            'admin.view_form_schemas',
            'admin.create_form_schemas',
            'admin.edit_form_schemas',
            'admin.view_settings',
            'admin.edit_settings',
            'admin.manage_skills',
            'admin.manage_industries',
            'admin.manage_job_titles',
            'admin.view_assessments',
            'admin.create_assessments',
            'admin.edit_assessments',
            'admin.manage_content',
        ]);

        // 5. Finance Manager - Subscriptions & Transactions
        $financeManager = Role::firstOrCreate(['name' => 'finance_manager', 'guard_name' => 'web']);
        $financeManager->syncPermissions([
            'admin.access_admin_panel',
            'admin.view_dashboard',
            'admin.view_analytics',
            'admin.export_reports',
            'admin.view_plans',
            'admin.view_subscriptions',
            'admin.manage_subscriptions',
            'admin.view_transactions',
            'admin.process_refunds',
            'admin.view_audit_logs',
        ]);

        // 6. Job Moderator - Job Listings Management
        $jobModerator = Role::firstOrCreate(['name' => 'job_moderator', 'guard_name' => 'web']);
        $jobModerator->syncPermissions([
            'admin.access_admin_panel',
            'admin.view_dashboard',
            'admin.view_jobs',
            'admin.edit_jobs',
            'admin.approve_jobs',
            'admin.reject_jobs',
            'admin.feature_jobs',
            'admin.view_employers',
            'admin.view_activity_logs',
        ]);

        // =====================================================
        // EMPLOYER TEAM ROLES
        // =====================================================

        // 7. Employer Owner - Full Company Access
        $employerOwner = Role::firstOrCreate(['name' => 'employer_owner', 'guard_name' => 'web']);
        $employerOwner->syncPermissions([
            'employer.view_company',
            'employer.edit_company',
            'employer.manage_billing',
            'employer.view_billing',
            'employer.upgrade_plan',
            'employer.cancel_subscription',
            'employer.post_job',
            'employer.edit_job',
            'employer.delete_job',
            'employer.view_job_analytics',
            'employer.feature_job',
            'employer.view_candidates',
            'employer.shortlist_candidate',
            'employer.reject_candidate',
            'employer.interview_candidate',
            'employer.hire_candidate',
            'employer.view_candidate_details',
            'employer.download_candidate_resume',
            'employer.add_candidate_notes',
            'employer.invite_team',
            'employer.view_team',
            'employer.edit_team',
            'employer.remove_team',
            'employer.assign_team_roles',
            'employer.message_candidates',
            'employer.schedule_interview',
            'employer.send_offer',
            'employer.view_reports',
            'employer.export_applications',
            'employer.view_job_metrics',
        ]);

        // 8. HR Manager - Jobs & Candidates, No Billing
        $hrManager = Role::firstOrCreate(['name' => 'hr_manager', 'guard_name' => 'web']);
        $hrManager->syncPermissions([
            'employer.view_company',
            'employer.post_job',
            'employer.edit_job',
            'employer.delete_job',
            'employer.view_job_analytics',
            'employer.view_candidates',
            'employer.shortlist_candidate',
            'employer.reject_candidate',
            'employer.interview_candidate',
            'employer.view_candidate_details',
            'employer.download_candidate_resume',
            'employer.add_candidate_notes',
            'employer.view_team',
            'employer.message_candidates',
            'employer.schedule_interview',
            'employer.send_offer',
            'employer.view_reports',
            'employer.view_job_metrics',
        ]);

        // 9. Interviewer - View Candidates Only
        $interviewer = Role::firstOrCreate(['name' => 'interviewer', 'guard_name' => 'web']);
        $interviewer->syncPermissions([
            'employer.view_company',
            'employer.view_candidates',
            'employer.interview_candidate',
            'employer.view_candidate_details',
            'employer.download_candidate_resume',
            'employer.add_candidate_notes',
            'employer.message_candidates',
            'employer.schedule_interview',
        ]);

        // 10. Recruiter - Sourcing & Communication
        $recruiter = Role::firstOrCreate(['name' => 'recruiter', 'guard_name' => 'web']);
        $recruiter->syncPermissions([
            'employer.view_company',
            'employer.post_job',
            'employer.edit_job',
            'employer.view_job_analytics',
            'employer.view_candidates',
            'employer.shortlist_candidate',
            'employer.reject_candidate',
            'employer.interview_candidate',
            'employer.view_candidate_details',
            'employer.download_candidate_resume',
            'employer.add_candidate_notes',
            'employer.message_candidates',
            'employer.schedule_interview',
            'employer.send_offer',
            'employer.view_reports',
            'employer.view_job_metrics',
        ]);

        // =====================================================
        // ASSIGN SUPER ADMIN TO EXISTING ADMINS
        // =====================================================
        
        // Find all users with role='admin' or super_admin=true and assign super_admin role
        $existingAdmins = User::where('role', 'admin')
            ->orWhere('super_admin', true)
            ->get();

        foreach ($existingAdmins as $admin) {
            if (!$admin->hasRole('super_admin')) {
                $admin->assignRole('super_admin');
            }
        }

        $this->command->info('✅ Roles and Permissions seeded successfully!');
        $this->command->info('   - ' . Permission::count() . ' permissions created');
        $this->command->info('   - ' . Role::count() . ' roles created');
        $this->command->info('   - ' . $existingAdmins->count() . ' existing admins assigned super_admin role');
    }
}
