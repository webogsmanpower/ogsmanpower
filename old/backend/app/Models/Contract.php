<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Contract Model
 * 
 * Employment contracts sent to candidates after successful interviews.
 */
class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_application_id',
        'employer_id',
        'seeker_id',
        'created_by',
        'contract_number',
        'title',
        'job_title',
        'department',
        'reporting_to',
        'work_location',
        'salary',
        'salary_currency',
        'salary_period',
        'allowances',
        'benefits',
        'start_date',
        'end_date',
        'contract_type',
        'probation_months',
        'notice_period_days',
        'working_hours',
        'working_days_per_week',
        'terms',
        'special_conditions',
        'clauses',
        'document_path',
        'template_used',
        'status',
        'sent_at',
        'viewed_at',
        'signed_at',
        'expires_at',
        'seeker_signature_path',
        'employer_signature_path',
        'seeker_ip_address',
        'seeker_user_agent',
        'rejection_reason',
        'negotiation_notes',
        'version',
        'parent_contract_id',
        'template_id',
        'html_content',
        'attachment_path',
        'signed_pdf_path',
        'signature_metadata',
        'seeker_initials',
        'terms_accepted',
        'job_id',
        // Enhanced Branding
        'header_logo_path',
        'company_address',
        'company_phone',
        'company_email',
        'signatory_name',
        'signatory_title',
        'signatory_signature_path',
        // Approval Workflow
        'approver_id',
        'approved_at',
        'approved_by',
        'approval_notes',
    ];

    protected $casts = [
        'allowances' => 'array',
        'benefits' => 'array',
        'clauses' => 'array',
        'salary' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'expires_at' => 'datetime',
        'probation_months' => 'integer',
        'notice_period_days' => 'integer',
        'working_days_per_week' => 'integer',
        'version' => 'integer',
        'signature_metadata' => 'array',
        'terms_accepted' => 'boolean',
        'approved_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            if (empty($contract->contract_number)) {
                $contract->contract_number = static::generateContractNumber();
            }
        });
    }

    /**
     * Generate unique contract number.
     */
    public static function generateContractNumber(): string
    {
        $prefix = 'CON';
        $year = date('Y');
        $random = strtoupper(Str::random(6));
        
        return "{$prefix}-{$year}-{$random}";
    }

    /**
     * Get the job application.
     */
    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    /**
     * Get the employer.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Get the seeker.
     */
    public function seeker(): BelongsTo
    {
        return $this->belongsTo(Seeker::class);
    }

    /**
     * Get the user who created.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the parent contract (if revised).
     */
    public function parentContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'parent_contract_id');
    }

    /**
     * Get visa status.
     */
    public function visaStatus(): HasOne
    {
        return $this->hasOne(VisaStatus::class);
    }

    /**
     * Get the template used.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'template_id');
    }

    /**
     * Get the job posting.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class, 'job_id');
    }

    /**
     * Get the internal approver.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(EmployerUser::class, 'approver_id');
    }

    /**
     * Get the user who approved.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Submit for internal approval.
     */
    public function submitForApproval(int $approverId): void
    {
        $this->update([
            'status' => 'pending_internal_approval',
            'approver_id' => $approverId,
        ]);
    }

    /**
     * Approve contract internally.
     */
    public function approve(int $userId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $userId,
            'approval_notes' => $notes,
        ]);
    }

    /**
     * Check if contract needs internal approval.
     */
    public function needsInternalApproval(): bool
    {
        return $this->status === 'pending_internal_approval';
    }

    /**
     * Get contract messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ContractMessage::class);
    }

    /**
     * Send contract to candidate.
     */
    public function send(): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as viewed.
     */
    public function markViewed(): void
    {
        if (!$this->viewed_at) {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }

    /**
     * Sign contract.
     */
    public function sign(?string $signaturePath = null, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->update([
            'status' => 'signed',
            'signed_at' => now(),
            'seeker_signature_path' => $signaturePath,
            'seeker_ip_address' => $ipAddress,
            'seeker_user_agent' => $userAgent,
        ]);
    }

    /**
     * Reject contract.
     */
    public function reject(?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Create revised version.
     */
    public function createRevision(array $changes): Contract
    {
        $newContract = $this->replicate();
        $newContract->fill($changes);
        $newContract->parent_contract_id = $this->id;
        $newContract->version = $this->version + 1;
        $newContract->status = 'draft';
        $newContract->sent_at = null;
        $newContract->viewed_at = null;
        $newContract->signed_at = null;
        $newContract->contract_number = static::generateContractNumber();
        $newContract->save();

        return $newContract;
    }

    /**
     * Scope for status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending signature.
     */
    public function scopePendingSignature($query)
    {
        return $query->whereIn('status', ['sent', 'viewed']);
    }

    /**
     * Check if contract is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'pending_internal_approval' => 'Pending Internal Approval',
            'sent' => 'Sent',
            'viewed' => 'Viewed',
            'signed' => 'Signed',
            'rejected' => 'Rejected',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get formatted salary.
     */
    public function getFormattedSalaryAttribute(): string
    {
        return "{$this->salary_currency} " . number_format($this->salary, 2) . " / " . ucfirst($this->salary_period);
    }

    /**
     * Get contract content (from html_content or template).
     */
    public function getContentAttribute(): string
    {
        if ($this->html_content) {
            // If html_content is too short or seems corrupted, fall back to template
            if (strlen($this->html_content) < 200 || !str_contains($this->html_content, '<')) {
                if ($this->template && $this->template->content) {
                    return $this->fillTemplateContent();
                }
            }
            return $this->html_content;
        }
        
        return $this->fillTemplateContent() ?? $this->terms ?? 'Contract content not available.';
    }

    /**
     * Fill template content with contract data.
     */
    private function fillTemplateContent(): ?string
    {
        if (!$this->template || !$this->template->content) {
            return null;
        }
        
        // Use more comprehensive placeholder data
        $placeholderData = [
            'contract_number' => $this->contract_number,
            'job_title' => $this->job_title,
            'department' => $this->department ?? '',
            'company_name' => $this->employer?->company_name ?? 'Company',
            'work_location' => $this->work_location ?? '',
            'reporting_to' => $this->reporting_to ?? '',
            'probation_period' => $this->probation_months ? $this->probation_months . ' months' : '',
            'candidate_phone' => $this->seeker?->user?->mobile ?? '',
            'candidate_email' => $this->seeker?->user?->email ?? '',
            'candidate_name' => $this->seeker?->full_name ?? '',
            'start_date' => $this->start_date?->format('F j, Y') ?? 'TBD',
            'end_date' => $this->end_date?->format('F j, Y') ?? '',
            'salary' => $this->formatted_salary,
            'salary_currency' => $this->salary_currency ?? 'USD',
            'salary_period' => $this->salary_period ?? 'monthly',
            'working_hours' => $this->working_hours ?? '',
            'working_days' => $this->working_days_per_week ?? '',
            'notice_period' => $this->notice_period_days ? $this->notice_period_days . ' days' : '',
            'contract_type' => $this->contract_type ?? 'Full-time',
            'current_date' => now()->format('F j, Y'),
        ];
        
        return $this->template->fillPlaceholders($placeholderData);
    }
}
