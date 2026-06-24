<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScreeningQuestionAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_application_id',
        'screening_question_id',
        'answer',
    ];

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(JobScreeningQuestion::class, 'screening_question_id');
    }
}
