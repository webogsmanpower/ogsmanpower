<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ContractTemplate Model
 * 
 * Reusable contract templates with WYSIWYG content and dynamic placeholders.
 */
class ContractTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employer_id',
        'created_by',
        'name',
        'description',
        'content',
        'placeholders',
        'header_image_path',
        'footer_text',
        'is_default',
        'is_active',
        'usage_count',
        // Enhanced Branding
        'company_address',
        'company_phone',
        'company_email',
        'signatory_name',
        'signatory_title',
        'signatory_signature_path',
        // Approval Workflow
        'default_approver_id',
        'requires_approval',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
        'requires_approval' => 'boolean',
    ];

    /**
     * Default placeholders available for all templates.
     */
    public const DEFAULT_PLACEHOLDERS = [
        ['key' => 'candidate_name', 'label' => 'Candidate Name', 'description' => 'Full name of the candidate'],
        ['key' => 'candidate_email', 'label' => 'Candidate Email', 'description' => 'Email address of the candidate'],
        ['key' => 'candidate_phone', 'label' => 'Candidate Phone', 'description' => 'Phone number of the candidate'],
        ['key' => 'job_title', 'label' => 'Job Title', 'description' => 'Position being offered'],
        ['key' => 'department', 'label' => 'Department', 'description' => 'Department or team'],
        ['key' => 'salary', 'label' => 'Salary', 'description' => 'Offered salary amount'],
        ['key' => 'salary_currency', 'label' => 'Salary Currency', 'description' => 'Currency code (USD, EUR, etc.)'],
        ['key' => 'start_date', 'label' => 'Start Date', 'description' => 'Employment start date'],
        ['key' => 'end_date', 'label' => 'End Date', 'description' => 'Contract end date (if applicable)'],
        ['key' => 'work_location', 'label' => 'Work Location', 'description' => 'Work location/address'],
        ['key' => 'company_name', 'label' => 'Company Name', 'description' => 'Employer company name'],
        ['key' => 'company_address', 'label' => 'Company Address', 'description' => 'Company address'],
        ['key' => 'reporting_to', 'label' => 'Reporting To', 'description' => 'Manager/supervisor name'],
        ['key' => 'probation_period', 'label' => 'Probation Period', 'description' => 'Probation period in months'],
        ['key' => 'notice_period', 'label' => 'Notice Period', 'description' => 'Notice period in days'],
        ['key' => 'working_hours', 'label' => 'Working Hours', 'description' => 'Working hours description'],
        ['key' => 'current_date', 'label' => 'Current Date', 'description' => 'Today\'s date'],
        ['key' => 'signatory_name', 'label' => 'Signatory Name', 'description' => 'Authorized signatory name'],
        ['key' => 'signatory_title', 'label' => 'Signatory Title', 'description' => 'Signatory job title'],
    ];

    /**
     * Get the employer.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Get the user who created.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the default approver.
     */
    public function defaultApprover(): BelongsTo
    {
        return $this->belongsTo(EmployerUser::class, 'default_approver_id');
    }

    /**
     * Get contracts created from this template.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'template_id');
    }

    /**
     * Increment usage count.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Set as default template (unset others).
     */
    public function setAsDefault(): void
    {
        // Unset other defaults for this employer
        static::where('employer_id', $this->employer_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Fill placeholders in content with actual data.
     */
    public function fillPlaceholders(array $data): string
    {
        $content = $this->content;

        foreach ($data as $key => $value) {
            $content = str_replace("{{" . $key . "}}", $value ?? '', $content);
        }

        return $content;
    }

    /**
     * Get all available placeholders (default + custom).
     */
    public function getAllPlaceholders(): array
    {
        return array_merge(
            self::DEFAULT_PLACEHOLDERS,
            $this->placeholders ?? []
        );
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for employer.
     */
    public function scopeForEmployer($query, int $employerId)
    {
        return $query->where('employer_id', $employerId);
    }
}
