<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobScreeningQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_posting_id',
        'question_text',
        'question_type',
        'options',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ScreeningQuestionAnswer::class, 'screening_question_id');
    }
}
