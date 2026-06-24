<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * VisaStepQuestionnaire Model
 * 
 * Questionnaires for visa workflow steps.
 */
class VisaStepQuestionnaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_step_id',
        'title',
        'description',
        'question_type',
        'options',
        'is_required',
        'sort_order',
        'validation_rules',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'is_required' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the visa step.
     */
    public function visaStep(): BelongsTo
    {
        return $this->belongsTo(VisaStep::class, 'visa_step_id');
    }

    /**
     * Get answers for this questionnaire.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(VisaStepQuestionnaireAnswer::class, 'questionnaire_id');
    }

    /**
     * Scope for required questions.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Scope for optional questions.
     */
    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }
}
