<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FormSection;
use App\Models\FormField;

class FormSchemaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $module = 'seeker_profile';
        
        // Create sections - All 13 required sections with enhanced configuration
        $sections = [
            [
                'module' => $module,
                'title' => 'Basic Information',
                'key' => 'basic-information',
                'icon' => 'user',
                'sort_order' => 1,
                'is_active' => true,
                'style_variant' => 'default',
                'description' => 'Personal details recruiters see first.',
                'fields' => [
                    ['label' => 'Profile Photo', 'name' => 'profile_photo', 'type' => 'avatar', 'required' => true, 'is_system' => true, 'col_span' => 2, 'variant' => 'avatar', 'helper_text' => 'Accepted formats: JPG or PNG up to 2MB.'],
                    ['label' => 'First Name', 'name' => 'first_name', 'type' => 'text', 'required' => true, 'is_system' => true],
                    ['label' => 'Last Name', 'name' => 'last_name', 'type' => 'text', 'required' => true, 'is_system' => true],
                    ['label' => 'Profession', 'name' => 'profession', 'type' => 'custom', 'required' => false, 'component' => 'JobTitleSelector', 'helper_text' => 'Your job title or profession for CV display'],
                    ['label' => 'Date of Birth', 'name' => 'date_of_birth', 'type' => 'date', 'required' => true],
                    ['label' => 'Father Name', 'name' => 'father_name', 'type' => 'text', 'required' => false],
                    ['label' => 'Mother Name', 'name' => 'mother_name', 'type' => 'text', 'required' => false],
                    ['label' => 'Marital Status', 'name' => 'marital_status', 'type' => 'select', 'required' => false, 'options' => [
                        ['value' => 'single', 'label' => 'Single'],
                        ['value' => 'married', 'label' => 'Married'],
                        ['value' => 'divorced', 'label' => 'Divorced'],
                        ['value' => 'widowed', 'label' => 'Widowed']
                    ]],
                    ['label' => 'Nationality', 'name' => 'nationality', 'type' => 'select', 'required' => false, 'options_source' => 'countries'],
                    ['label' => 'Phone Number', 'name' => 'phone', 'type' => 'phone', 'required' => true, 'is_system' => true, 'col_span' => 2, 'default_country_code' => '+971'],
                    ['label' => 'WhatsApp Number', 'name' => 'whatsapp_number', 'type' => 'phone', 'required' => false, 'col_span' => 2, 'default_country_code' => '+971'],
                    ['label' => 'Email Address', 'name' => 'email', 'type' => 'email', 'required' => true, 'is_system' => true, 'col_span' => 2],
                    ['label' => 'Emergency Contact Name', 'name' => 'emergency_contact_name', 'type' => 'text', 'required' => true],
                    ['label' => 'Emergency Contact Number', 'name' => 'emergency_contact_phone', 'type' => 'phone', 'required' => true, 'col_span' => 2, 'default_country_code' => '+971'],
                    ['label' => 'Address', 'name' => 'address', 'type' => 'text', 'required' => false, 'col_span' => 2],
                    ['label' => 'State / Province', 'name' => 'state_province', 'type' => 'text', 'required' => false],
                    ['label' => 'City', 'name' => 'city', 'type' => 'text', 'required' => false],
                    ['label' => 'Zip / Postal Code', 'name' => 'zip_code', 'type' => 'text', 'required' => false],
                    ['label' => 'Country', 'name' => 'country', 'type' => 'select', 'required' => true, 'options_source' => 'countries'],
                ]
            ],
            [
                'module' => $module,
                'title' => 'Job Preferences',
                'key' => 'job-preferences',
                'icon' => 'briefcase',
                'sort_order' => 2,
                'is_active' => true,
                'description' => 'Your preferred job roles and locations.',
                'fields' => [
                    ['label' => 'Preferred Job Titles', 'name' => 'preferred_job_titles', 'type' => 'custom', 'required' => true, 'component' => 'JobTitleTagsSelector', 'helper_text' => 'Add job titles you\'re interested in. We\'ll notify you when matching jobs are posted.'],
                    ['label' => 'Preferred Industries', 'name' => 'preferred_industries', 'type' => 'custom', 'required' => true, 'component' => 'IndustryTagsSelector', 'helper_text' => 'Select industries you prefer to work in.'],
                    ['label' => 'Preferred Locations', 'name' => 'preferred_locations', 'type' => 'custom', 'required' => true, 'component' => 'LocationTagsSelector', 'helper_text' => 'Add cities or countries where you\'d like to work. We\'ll match you with jobs in these locations.'],
                    ['label' => 'Job Types', 'name' => 'job_types', 'type' => 'multi_select', 'required' => true, 'options' => [
                        ['value' => 'full-time', 'label' => 'Full-time'],
                        ['value' => 'part-time', 'label' => 'Part-time'],
                        ['value' => 'contract', 'label' => 'Contract'],
                        ['value' => 'temporary', 'label' => 'Temporary'],
                        ['value' => 'internship', 'label' => 'Internship'],
                        ['value' => 'freelance', 'label' => 'Freelance'],
                    ]],
                    ['label' => 'Salary Expectations', 'name' => 'salary_expectations', 'type' => 'text', 'required' => true, 'helper_text' => 'e.g. $80,000 - $100,000'],
                ]
            ],
            [
                'module' => $module,
                'title' => 'Work Experience',
                'key' => 'work-experience',
                'icon' => 'building',
                'sort_order' => 3,
                'is_active' => true,
                'is_multi_entry' => true,
                'add_new_label' => 'Add Work Experience',
                'entry_title_template' => '{role_title} || Experience {index}',
                'description' => 'List roles, responsibilities, and timelines.',
                'fields' => [
                    ['label' => 'Job Title', 'name' => 'role_title', 'type' => 'custom', 'required' => true, 'component' => 'JobTitleSelector'],
                    ['label' => 'Company', 'name' => 'company_name', 'type' => 'text', 'required' => true],
                    ['label' => 'Location', 'name' => 'location', 'type' => 'text', 'required' => false],
                    ['label' => 'Start Date', 'name' => 'start_date', 'type' => 'date', 'required' => true],
                    ['label' => 'End Date', 'name' => 'end_date', 'type' => 'date', 'required' => false],
                    ['label' => 'I currently work here', 'name' => 'is_current_role', 'type' => 'checkbox', 'required' => false],
                    ['label' => 'Job Description', 'name' => 'job_description', 'type' => 'textarea', 'required' => false, 'helper_text' => 'Summarize your day-to-day responsibilities', 'col_span' => 2],
                    ['label' => 'Key Achievements', 'name' => 'key_achievements', 'type' => 'textarea', 'required' => false, 'helper_text' => 'Highlight measurable wins or impact', 'col_span' => 2],
                    ['label' => 'Experience Document', 'name' => 'document_path', 'type' => 'file', 'required' => false, 'helper_text' => 'Upload offer letter or relieving letter', 'col_span' => 2],
                ]
            ],
            [
                'module' => $module,
                'title' => 'Education',
                'key' => 'education',
                'icon' => 'graduation-cap',
                'sort_order' => 4,
                'is_active' => true,
                'is_multi_entry' => true,
                'add_new_label' => 'Add Education',
                'entry_title_template' => '{degree_title} || Education {index}',
                'description' => 'Your educational background and qualifications.',
                'fields' => [
                    ['label' => 'Institution', 'name' => 'institution_name', 'type' => 'text', 'required' => true],
                    ['label' => 'Degree', 'name' => 'degree_title', 'type' => 'custom', 'required' => true, 'component' => 'DegreeSelector'],
                    ['label' => 'Graduation Year', 'name' => 'graduation_year', 'type' => 'date', 'required' => false],
                    ['label' => 'Degree/Transcript', 'name' => 'document_path', 'type' => 'file', 'required' => false, 'helper_text' => 'Upload degree certificate or transcript', 'col_span' => 2],
                ]
            ],
            [
                'module' => $module,
                'title' => 'Documents',
                'key' => 'documents',
                'icon' => 'folder',
                'sort_order' => 5,
                'is_active' => true,
                'description' => 'Attach verified records and resumes.',
                'fields' => [
                    ['label' => 'Passport Number', 'name' => 'passport_number', 'type' => 'text', 'required' => true],
                    ['label' => 'Passport Issue Date', 'name' => 'passport_issue_date', 'type' => 'date', 'required' => false],
                    ['label' => 'Passport Expiry Date', 'name' => 'passport_expiry_date', 'type' => 'date', 'required' => false, 'min_validity_months' => 6, 'min_validity_message' => 'Please renew your passport. Passport should be valid for more than 6 months.'],
                    ['label' => 'Passport Issue Place', 'name' => 'passport_issue_place', 'type' => 'text', 'required' => false],
                    ['label' => 'Passport Photo', 'name' => 'passport_photo', 'type' => 'file', 'required' => false],
                    ['label' => 'CNIC/ID Card Number', 'name' => 'cnic_number', 'type' => 'text', 'required' => false],
                    ['label' => 'CNIC/ID Card Front Photo', 'name' => 'cnic_front_photo', 'type' => 'file', 'required' => false],
                    ['label' => 'CNIC/ID Card Back Photo', 'name' => 'cnic_back_photo', 'type' => 'file', 'required' => false],
                    ['label' => 'Police Character Certificate Photo', 'name' => 'police_certificate_photo', 'type' => 'file', 'required' => false],
                    ['label' => 'Medical Certificate Photo', 'name' => 'medical_certificate_photo', 'type' => 'file', 'required' => false],
                    ['label' => 'Introductory Video', 'name' => 'introductory_video', 'type' => 'file', 'required' => false],
                    ['label' => 'Driver\'s License Photo', 'name' => 'drivers_license_photo', 'type' => 'file', 'required' => false],
                    ['label' => 'Add New field or document', 'name' => 'additional_document', 'type' => 'file', 'required' => false],
                ]
            ],
            [
                'module' => $module,
                'title' => 'Availability',
                'key' => 'availability',
                'icon' => 'calendar',
                'sort_order' => 6,
                'is_active' => true,
                'description' => 'Your current availability status and notice period.',
                'fields' => [
                    ['label' => 'Available From', 'name' => 'availability_date', 'type' => 'date', 'required' => false],
                    ['label' => 'Notice Period', 'name' => 'notice_period', 'type' => 'select', 'required' => false, 'options' => [
                        ['value' => 'immediate', 'label' => 'Immediate'],
                        ['value' => '30days', 'label' => '30 Days'],
                        ['value' => '60days', 'label' => '60 Days']
                    ]],
                ]
            ],
            [
                'module' => $module,
                'title' => 'Professional Summary',
                'key' => 'professional-summary',
                'icon' => 'file-text',
                'sort_order' => 7,
                'is_active' => true,
                'description' => 'Summarize your experience in a few lines.',
                'fields' => [
                    ['label' => 'Professional Summary', 'name' => 'career_objective', 'type' => 'textarea', 'required' => true, 'helper_text' => 'This will be the first thing employers see. Make it count!', 'col_span' => 2],
                    ['label' => 'Key Strengths', 'name' => 'key_strengths', 'type' => 'tags', 'required' => false, 'helper_text' => 'Add your top professional strengths as tags'],
                    ['label' => 'Industry Experience', 'name' => 'industry_experience', 'type' => 'text', 'required' => false, 'helper_text' => 'Industries where you have significant experience'],
                ]
            ],
            [
                'module' => $module,
                'title' => 'Skills & Expertise',
                'key' => 'skills',
                'icon' => 'code',
                'sort_order' => 8,
                'is_active' => true,
                'description' => 'Your professional skills and expertise.',
                'fields' => [
                    [
                        'label' => 'Skills & Expertise',
                        'name' => 'skills',
                        'type' => 'combobox',
                        'required' => false,
                        'helper_text' => 'Add your professional skills. Select from the list or create custom skills.',
                        'col_span' => 2
                    ]
                ]
            ],
            [
                'module' => $module,
                'title' => 'Certificates',
                'key' => 'certificates',
                'icon' => 'award',
                'sort_order' => 9,
                'is_active' => true,
                'is_multi_entry' => true,
                'add_new_label' => 'Add Certification',
                'entry_title_template' => '{certification_name} || Certification {index}',
                'description' => 'Your professional certifications and licenses.',
                'fields' => [
                    ['label' => 'Certification Name', 'name' => 'certification_name', 'type' => 'text', 'required' => true],
                    ['label' => 'Certificate Issuer', 'name' => 'issuer', 'type' => 'text', 'required' => true],
                    ['label' => 'Issue Date', 'name' => 'issue_date', 'type' => 'date', 'required' => true],
                    ['label' => 'Expiry Date', 'name' => 'expiry_date', 'type' => 'date', 'required' => false],
                    ['label' => 'Does not expire', 'name' => 'does_not_expire', 'type' => 'checkbox', 'required' => false],
                    ['label' => 'Credential ID', 'name' => 'credential_id', 'type' => 'text', 'required' => false, 'helper_text' => 'e.g. PMI-123456'],
                    ['label' => 'Credential URL', 'name' => 'credential_url', 'type' => 'url', 'required' => false, 'helper_text' => 'https://verify.example.com/certificate'],
                    ['label' => 'Certificate File', 'name' => 'certificate_file', 'type' => 'file', 'required' => false, 'helper_text' => 'Upload certificate file'],
                ]
            ],
            [
                'module' => $module,
                'title' => 'Languages',
                'key' => 'languages',
                'icon' => 'globe',
                'sort_order' => 10,
                'is_active' => true,
                'is_multi_entry' => true,
                'add_new_label' => 'Add Language',
                'entry_title_template' => '{language_name} || Language {index}',
                'description' => 'Languages you speak and proficiency levels.',
                'fields' => [
                    ['label' => 'Language', 'name' => 'language_name', 'type' => 'custom', 'required' => true, 'component' => 'LanguageSelector'],
                ]
            ],
            [
                'module' => $module,
                'title' => 'References',
                'key' => 'references',
                'icon' => 'users',
                'sort_order' => 11,
                'is_active' => true,
                'is_multi_entry' => true,
                'add_new_label' => 'Add Reference',
                'entry_title_template' => '{name} || Reference {index}',
                'description' => 'Professional references who can vouch for your work.',
                'fields' => [
                    ['label' => 'Reference Name', 'name' => 'name', 'type' => 'text', 'required' => true],
                    ['label' => 'Job Title', 'name' => 'job_title', 'type' => 'custom', 'required' => false, 'component' => 'JobTitleSelector'],
                    ['label' => 'Company Name', 'name' => 'company_name', 'type' => 'text', 'required' => false],
                    ['label' => 'Email Address', 'name' => 'email', 'type' => 'email', 'required' => false],
                    ['label' => 'Phone Number', 'name' => 'phone', 'type' => 'phone', 'required' => false, 'default_country_code' => '+971', 'col_span' => 2],
                    ['label' => 'Relationship', 'name' => 'relationship', 'type' => 'select', 'required' => false, 'options' => [
                        ['value' => 'Manager', 'label' => 'Manager'],
                        ['value' => 'Supervisor', 'label' => 'Supervisor'],
                        ['value' => 'Colleague', 'label' => 'Colleague'],
                        ['value' => 'Team Lead', 'label' => 'Team Lead'],
                        ['value' => 'Mentor', 'label' => 'Mentor'],
                        ['value' => 'Client', 'label' => 'Client'],
                        ['value' => 'Professor', 'label' => 'Professor'],
                        ['value' => 'Other', 'label' => 'Other']
                    ]],
                ]
            ],
            [
                'module' => $module,
                'title' => 'Privacy',
                'key' => 'privacy',
                'icon' => 'shield',
                'sort_order' => 12,
                'is_active' => true,
                'description' => 'Control who can see your profile and contact information.',
                'fields' => [
                    ['label' => 'Profile Visibility', 'name' => 'profile_visibility', 'type' => 'select', 'required' => false, 'options' => [
                        ['value' => 'public', 'label' => 'Public'],
                        ['value' => 'recruiters', 'label' => 'Recruiters Only'],
                        ['value' => 'private', 'label' => 'Hidden']
                    ]],
                    ['label' => 'Data Sharing', 'name' => 'data_sharing', 'type' => 'select', 'required' => false, 'options' => [
                        ['value' => 'full', 'label' => 'Share full profile'],
                        ['value' => 'partial', 'label' => 'Share limited data'],
                        ['value' => 'none', 'label' => 'Do not share']
                    ]],
                ]
            ],
            [
                'module' => $module,
                'title' => 'Social Links',
                'key' => 'social-profiles',
                'icon' => 'link',
                'sort_order' => 13,
                'is_active' => true,
                'description' => 'Link external profiles that showcase your work.',
                'fields' => [
                    ['label' => 'LinkedIn Profile', 'name' => 'linkedin_profile', 'type' => 'url', 'required' => false],
                    ['label' => 'Facebook Profile', 'name' => 'facebook_profile', 'type' => 'url', 'required' => false],
                    ['label' => 'Instagram Profile', 'name' => 'instagram_profile', 'type' => 'url', 'required' => false],
                    ['label' => 'Github URL', 'name' => 'github_url', 'type' => 'url', 'required' => false],
                    ['label' => 'Personal Website', 'name' => 'personal_website', 'type' => 'url', 'required' => false],
                ]
            ],
        ];

        foreach ($sections as $sectionData) {
            $fields = $sectionData['fields'];
            unset($sectionData['fields']);
            
            // Use firstOrCreate to avoid duplicates
            $section = FormSection::firstOrCreate(
                ['module' => $sectionData['module'], 'key' => $sectionData['key']],
                $sectionData
            );
            
            // Only create fields if section was just created
            if ($section->wasRecentlyCreated) {
                foreach ($fields as $index => $fieldData) {
                    $fieldData['section_id'] = $section->id;
                    $fieldData['sort_order'] = $index + 1;
                    
                    if (isset($fieldData['options'])) {
                        $fieldData['options'] = json_encode($fieldData['options']);
                    }
                    
                    FormField::create($fieldData);
                }
            }
        }
        
        echo "Form schema for seeker profile seeded successfully! (13 sections)\n";
    }
}
