<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VisaWorkflowPermission Model
 * 
 * Access control for visa workflow management.
 */
class VisaWorkflowPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_status_id',
        'user_id',
        'permission_level',
        'can_view_internal_notes',
        'can_verify_documents',
        'can_edit_steps',
        'can_add_notes',
        'can_request_documents',
        'can_upload_documents',
    ];

    protected $casts = [
        'can_view_internal_notes' => 'boolean',
        'can_verify_documents' => 'boolean',
        'can_edit_steps' => 'boolean',
        'can_add_notes' => 'boolean',
        'can_request_documents' => 'boolean',
        'can_upload_documents' => 'boolean',
    ];

    /**
     * Get the visa status.
     */
    public function visaStatus(): BelongsTo
    {
        return $this->belongsTo(VisaStatus::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user has admin permissions.
     */
    public function isAdmin(): bool
    {
        return $this->permission_level === 'admin';
    }

    /**
     * Check if user can verify documents.
     */
    public function canVerify(): bool
    {
        return $this->can_verify_documents || $this->isAdmin();
    }

    /**
     * Check if user can edit steps.
     */
    public function canEdit(): bool
    {
        return $this->can_edit_steps || in_array($this->permission_level, ['edit', 'admin']);
    }

    /**
     * Check if user can add notes.
     */
    public function canAddNotes(): bool
    {
        return $this->can_add_notes || in_array($this->permission_level, ['edit', 'admin']);
    }
}
