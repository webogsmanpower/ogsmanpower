<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create seeker_resumes table
 * 
 * Purpose: Single canonical table to store ALL "Edit Resume" page data for Seekers.
 * This table consolidates 13 accordion sections from the Edit Resume page into one queryable structure.
 * 
 * Design reference: /mnt/data/132d327e-26c0-4f16-8c8f-d30743a86ef3.png
 * 
 * JSON Columns Strategy:
 * - Uses JSON columns for flexible/complex nested data (work experience, education, skills, etc.)
 * - Explicit columns for frequently queried fields (user_id, profile_completion, primary_language)
 * - Allows structured data storage while maintaining queryability via JSON path queries
 * 
 * Sections covered:
 * 1. Basic Information (name, contact, address, photo)
 * 2. Documents (ID cards, passports, certificates)
 * 3. Social Profiles (LinkedIn, GitHub, etc.)
 * 4. Professional Summary (career objective, strengths, industry experience)
 * 5. Work Experience (job history with achievements)
 * 6. Education (academic qualifications)
 * 7. Skills (technical and soft skills with proficiency)
 * 8. Languages (language proficiency with certificates)
 * 9. Certifications (professional certifications)
 * 10. References (professional references)
 * 11. Job Preferences (desired roles, salary, location preferences)
 * 12. Availability (current status, working hours, shift preferences)
 * 13. Privacy Settings (profile visibility controls)
 * 
 * Additional features:
 * - Resume format selection and CV generation metadata
 * - Version history tracking
 * - Extensible extra fields for custom data
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seeker_resumes', function (Blueprint $table) {
            // Primary key
            $table->id();
            
            // Foreign keys - Core relationships
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Reference to users table');
            
            $table->foreignId('seeker_id')
                ->nullable()
                ->comment('Reference to seekers table - nullable for flexibility');
            
            // Frequently queried scalar fields
            $table->unsignedTinyInteger('profile_completion')
                ->default(0)
                ->comment('Profile completion percentage (0-100)');
            
            $table->string('primary_language', 10)
                ->nullable()
                ->comment('Primary language code (e.g., en, ar)');
            
            $table->boolean('is_rtl')
                ->default(false)
                ->comment('Right-to-left language flag');
            
            $table->string('resume_format', 100)
                ->nullable()
                ->comment('Selected CV format/template name');
            
            // JSON columns for structured data - Section 1: Basic Information
            $table->json('basic_information')
                ->nullable()
                ->comment('Stores: full_name, father_name, mother_name, phone, whatsapp, email, emergency_contact, address, country, state, city, profile_photo');
            
            // Section 2: Documents
            $table->json('documents')
                ->nullable()
                ->comment('Array of document objects: {type, number, issue_date, expiry_date, file_path}');
            
            // Section 3: Social Profiles
            $table->json('social_profiles')
                ->nullable()
                ->comment('Object/array of social media links and professional profiles');
            
            // Section 4: Professional Summary
            $table->json('professional_summary')
                ->nullable()
                ->comment('Stores: career_objective, strengths, industry_experience (structured)');
            
            // Section 5: Work Experience
            $table->json('work_experience')
                ->nullable()
                ->comment('Array of experience objects: {title, company, location, start_date, end_date, current, description, achievements}');
            
            // Section 6: Education
            $table->json('education')
                ->nullable()
                ->comment('Array of education entries: {degree, institution, field_of_study, start_date, end_date, grade, description}');
            
            // Section 7: Skills
            $table->json('skills')
                ->nullable()
                ->comment('Array of skill objects: {skill, proficiency, category}');
            
            // Section 8: Languages
            $table->json('languages')
                ->nullable()
                ->comment('Array of language objects: {language, proficiency, certificate}');
            
            // Section 9: Certifications
            $table->json('certifications')
                ->nullable()
                ->comment('Array of certification objects: {name, issuing_organization, issue_date, expiry_date, credential_id, credential_url}');
            
            // Section 10: References
            $table->json('references')
                ->nullable()
                ->comment('Array of reference objects: {name, title, company, phone, email, relationship}');
            
            // Section 11: Job Preferences
            $table->json('job_preferences')
                ->nullable()
                ->comment('Stores: preferred_job_type, industries, salary_min, salary_max, relocate, notice_period, work_authorization');
            
            // Section 12: Availability
            $table->json('availability')
                ->nullable()
                ->comment('Stores: current_status, weekly_hours, shift_preference, start_date, additional_notes');
            
            // Section 13: Privacy Settings
            $table->json('privacy_settings')
                ->nullable()
                ->comment('Visibility controls: {profile_visibility, contact_visibility, resume_visibility, etc.}');
            
            // CV Generation metadata
            $table->json('generated_cv')
                ->nullable()
                ->comment('Generated CV metadata: {language, format, file_path, generated_at}');
            
            // Version history
            $table->json('resume_versions')
                ->nullable()
                ->comment('History array for versions: [{snapshot_id, created_at, note, changes}]');
            
            // Extensibility
            $table->json('extra')
                ->nullable()
                ->comment('Admin/user-added custom fields for future extensibility');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('user_id', 'idx_seeker_resumes_user_id');
            $table->index('seeker_id', 'idx_seeker_resumes_seeker_id');
            $table->index('profile_completion', 'idx_seeker_resumes_completion');
            $table->index(['user_id', 'profile_completion'], 'idx_seeker_resumes_user_completion');
            
            // Optional: Fulltext indexes for search functionality
            // Note: MySQL fulltext on JSON requires generated columns or JSON_EXTRACT in queries
            // Consider adding generated columns for searchable text fields if needed:
            // $table->text('searchable_summary')->storedAs('JSON_UNQUOTE(JSON_EXTRACT(professional_summary, "$.career_objective"))')->nullable();
            // $table->fullText('searchable_summary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seeker_resumes');
    }
};
