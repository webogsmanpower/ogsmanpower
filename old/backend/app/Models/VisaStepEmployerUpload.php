<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VisaStepEmployerUpload Model
 * 
 * Documents uploaded by employers for visa steps.
 */
class VisaStepEmployerUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_step_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'is_visible_to_seeker',
        'uploaded_by',
    ];

    protected $casts = [
        'is_visible_to_seeker' => 'boolean',
        'file_size' => 'integer',
    ];

    /**
     * Get the visa step.
     */
    public function visaStep(): BelongsTo
    {
        return $this->belongsTo(VisaStep::class, 'visa_step_id');
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the file URL.
     */
    public function getUrlAttribute(): string
    {
        return url('storage/' . $this->file_path);
    }

    /**
     * Get file size in human-readable format.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
