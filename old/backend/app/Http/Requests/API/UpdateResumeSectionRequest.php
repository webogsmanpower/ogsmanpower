<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateResumeSectionRequest
 * 
 * Validates resume section update requests.
 * Provides strict validation rules for each section type.
 * 
 * @package App\Http\Requests\API
 */
class UpdateResumeSectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $section = $this->route('section');
        $normalizedSection = str_replace('-', '_', strtolower($section));

        return match ($normalizedSection) {
            'basic_information' => $this->basicInformationRules(),
            'work_experience' => $this->workExperienceRules(),
            'education' => $this->educationRules(),
            'certifications' => $this->certificationsRules(),
            'references' => $this->referencesRules(),
            'skills' => $this->skillsRules(),
            'languages' => $this->languagesRules(),
            'professional_summary' => $this->professionalSummaryRules(),
            'job_preferences' => $this->jobPreferencesRules(),
            'documents' => $this->documentsRules(),
            'social_profiles' => $this->socialProfilesRules(),
            'availability' => $this->availabilityRules(),
            'privacy_settings' => $this->privacySettingsRules(),
            default => [],
        };
    }

    /**
     * Basic Information validation rules
     */
    private function basicInformationRules(): array
    {
        return [
            'profile_photo' => 'nullable|string|max:500',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:500',
            'date_of_birth' => 'required|date|before:today',
            'father_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:50',
            'country' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'state_province' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'postal_code' => 'nullable|string|max:20',
            'gender' => 'nullable|string|in:male,female,other',
            'marital_status' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:255',
            'religion' => 'nullable|string|max:100',
            'current_location' => 'nullable|string|max:255',
        ];
    }

    /**
     * Work Experience validation rules
     */
    private function workExperienceRules(): array
    {
        return [
            '*.title' => 'required|string|max:255',
            '*.role_title' => 'nullable|string|max:255',
            '*.company' => 'required|string|max:255',
            '*.location' => 'nullable|string|max:255',
            '*.country' => 'nullable|string|max:255',
            '*.start_date' => 'required|date',
            '*.end_date' => 'nullable|date|after_or_equal:*.start_date',
            '*.current' => 'nullable|boolean',
            '*.description' => 'nullable|string|max:2000',
            '*.achievements' => 'nullable|string|max:2000',
            '*.document_path' => 'nullable|string|max:500',
        ];
    }

    /**
     * Education validation rules
     */
    private function educationRules(): array
    {
        return [
            '*.degree' => 'required|string|max:255',
            '*.degree_title' => 'nullable|string|max:255',
            '*.institution' => 'required|string|max:255',
            '*.institution_name' => 'nullable|string|max:255',
            '*.field_of_study' => 'nullable|string|max:255',
            '*.start_date' => 'required|date',
            '*.end_date' => 'nullable|date|after_or_equal:*.start_date',
            '*.grade' => 'nullable|string|max:100',
            '*.description' => 'nullable|string|max:2000',
            '*.document_path' => 'nullable|string|max:500',
        ];
    }

    /**
     * Certifications validation rules
     */
    private function certificationsRules(): array
    {
        return [
            '*.certification_name' => 'required|string|max:255',
            '*.issuer' => 'required|string|max:255',
            '*.issue_date' => 'required|date',
            '*.expiry_date' => 'nullable|date|after_or_equal:*.issue_date',
            '*.does_not_expire' => 'nullable|boolean',
            '*.credential_id' => 'nullable|string|max:100',
            '*.credential_url' => 'nullable|url|max:500',
        ];
    }

    /**
     * References validation rules
     */
    private function referencesRules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.job_title' => 'nullable|string|max:255',
            '*.company_name' => 'nullable|string|max:255',
            '*.email' => 'nullable|email|max:255',
            '*.phone' => 'nullable|string|max:50',
            '*.relationship' => 'nullable|string|max:100',
        ];
    }

    /**
     * Skills validation rules
     */
    private function skillsRules(): array
    {
        return [
            '*.skill' => 'required|string|max:100',
            '*.skill_name' => 'nullable|string|max:100',
            '*.proficiency' => 'nullable|string|in:Beginner,Intermediate,Advanced,Expert',
            '*.proficiency_level' => 'nullable|string|max:50',
            '*.category' => 'nullable|string|max:100',
        ];
    }

    /**
     * Languages validation rules
     */
    private function languagesRules(): array
    {
        return [
            '*.language_name' => 'required|string|max:100',
            '*.proficiency_level' => 'nullable|string|max:50',
            '*.level' => 'nullable|string|max:50',
            '*.is_native' => 'nullable|boolean',
        ];
    }

    /**
     * Professional Summary validation rules
     */
    private function professionalSummaryRules(): array
    {
        return [
            'career_objective' => 'nullable|string|max:3000',
            'professional_summary' => 'nullable|string|max:3000',
            'strengths' => 'nullable|array',
            'strengths.*' => 'string|max:255',
            'industry_experience' => 'nullable|array',
            'industry_experience.*' => 'string|max:255',
        ];
    }

    /**
     * Job Preferences validation rules
     */
    private function jobPreferencesRules(): array
    {
        return [
            'preferred_locations' => 'nullable',
            'preferred_location' => 'nullable|string|max:500',
            'job_types' => 'nullable',
            'salary_expectations' => 'nullable',
            'willing_to_relocate' => 'nullable|boolean',
            'remote_work' => 'nullable|boolean',
        ];
    }

    /**
     * Documents validation rules
     */
    private function documentsRules(): array
    {
        return [
            'passport_number' => 'nullable|string|max:50',
            'passport_expiry' => 'nullable|date',
            'visa_status' => 'nullable|string|max:100',
            '*.document_type' => 'nullable|string|max:100',
            '*.file_path' => 'nullable|string|max:500',
            '*.file_name' => 'nullable|string|max:255',
        ];
    }

    /**
     * Social Profiles validation rules
     */
    private function socialProfilesRules(): array
    {
        return [
            'linkedin' => 'nullable|url|max:500',
            'github' => 'nullable|url|max:500',
            'portfolio' => 'nullable|url|max:500',
            'website' => 'nullable|url|max:500',
            'twitter' => 'nullable|url|max:500',
        ];
    }

    /**
     * Availability validation rules
     */
    private function availabilityRules(): array
    {
        return [
            'available_from' => 'nullable|date',
            'notice_period' => 'nullable|string|max:100',
            'availability_status' => 'nullable|string|max:100',
            'preferred_work_hours' => 'nullable|string|max:255',
        ];
    }

    /**
     * Privacy Settings validation rules
     */
    private function privacySettingsRules(): array
    {
        return [
            'profile_visibility' => 'nullable|string|in:public,private,connections',
            'show_email' => 'nullable|boolean',
            'show_phone' => 'nullable|boolean',
            'show_location' => 'nullable|boolean',
            'basic_information_visibility' => 'nullable|boolean',
            'work_experience_visibility' => 'nullable|boolean',
            'education_visibility' => 'nullable|boolean',
            'skills_visibility' => 'nullable|boolean',
            'references_visibility' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'phone.required' => 'Phone number is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Email must be a valid email address.',
            'emergency_contact_name.required' => 'Emergency contact name is required.',
            'emergency_contact_phone.required' => 'Emergency contact phone is required.',
            'country.required' => 'Country is required.',
            '*.title.required' => 'Job title is required for each work experience.',
            '*.company.required' => 'Company name is required for each work experience.',
            '*.degree.required' => 'Degree is required for each education entry.',
            '*.institution.required' => 'Institution name is required for each education entry.',
            '*.certification_name.required' => 'Certification name is required.',
            '*.issuer.required' => 'Certificate issuer is required.',
            '*.name.required' => 'Reference name is required.',
            '*.skill.required' => 'Skill name is required.',
            '*.language_name.required' => 'Language name is required.',
        ];
    }
}
