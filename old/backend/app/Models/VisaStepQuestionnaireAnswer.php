<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VisaStepQuestionnaireAnswer Model
 * 
 * Answers to visa step questionnaires.
 */
class VisaStepQuestionnaireAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'questionnaire_id',
        'seeker_id',
        'answer',
        'file_path',
        'answered_at',
    ];

    protected $casts = [
        'answer' => 'json',
        'answered_at' => 'datetime',
    ];

    /**
     * Get the questionnaire.
     */
    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(VisaStepQuestionnaire::class, 'questionnaire_id');
    }

    /**
     * Get the seeker.
     */
    public function seeker(): BelongsTo
    {
        return $this->belongsTo(Seeker::class);
    }

    /**
     * Check if answer is complete.
     */
    public function isComplete(): bool
    {
        return !empty($this->answer) || !empty($this->file_path);
    }
}
