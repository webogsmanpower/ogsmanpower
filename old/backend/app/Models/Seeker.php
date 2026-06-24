<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Seeker extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'profession',
        'headline',
        'date_of_birth',
        'current_location',
        'experience_years',
        'is_profile_complete',
        'profile_completion',
        'profile_views',
        'skills',
        'bio',
        'resume_path',
        'profile_image_path',
        'license_number',
        'license_expiry_date',
        'license_issuing_country',
        'license_issuing_authority',
        'license_type',
        'accident_free_years',
        'has_clean_driving_record',
        // Domestic worker fields
        'number_of_children',
        'skill_washing',
        'skill_cooking',
        'skill_babysitting',
        'skill_cleaning',
        'full_body_image_path',
        // Translation cache
        'translations',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
        'license_expiry_date' => 'date',
        'skills' => 'array',
        'is_profile_complete' => 'boolean',
        'profile_completion' => 'integer',
        'profile_views' => 'integer',
        'experience_years' => 'integer',
        'has_clean_driving_record' => 'boolean',
        // Domestic worker casts
        'number_of_children' => 'integer',
        'skill_washing' => 'boolean',
        'skill_cooking' => 'boolean',
        'skill_babysitting' => 'boolean',
        'skill_cleaning' => 'boolean',
        // Translation cache
        'translations' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resume(): HasOne
    {
        return $this->hasOne(SeekerResume::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function interviews(): HasMany
    {
        return $this->hasMany(Interview::class);
    }

    public function visaStatuses(): HasMany
    {
        return $this->hasMany(VisaStatus::class);
    }

    /**
     * Calculate and update profile completion percentage.
     * 
     * @return int
     */
    public function calculateProfileCompletion(): int
    {
        // Ensure user relationship is loaded
        if (!$this->relationLoaded('user')) {
            $this->load('user');
        }
        
        $score = 0;
        
        // Basic Information (40 points)
        if (!empty($this->first_name)) $score += 5;
        if (!empty($this->last_name)) $score += 5;
        if (!empty($this->user?->mobile)) $score += 5;
        if (!empty($this->profile_image_path)) $score += 10;
        if (!empty($this->date_of_birth)) $score += 5;
        if (!empty($this->current_location)) $score += 5;
        if (!empty($this->bio)) $score += 5;
        
        // Professional Details (50 points)
        if (!empty($this->profession)) $score += 10;
        if (!empty($this->headline)) $score += 10;
        if (!empty($this->experience_years)) $score += 10;
        if (!empty($this->skills)) $score += 10;
        if (!empty($this->resume_path)) $score += 10;
        
        // Additional Information (10 points)
        if (!empty($this->license_number)) $score += 5;
        if (!empty($this->license_expiry_date)) $score += 5;
        
        $this->profile_completion = $score;
        $this->save();
        
        return $score;
    }

    public static function recalculateForUser($email)
    {
        $user = User::where('email', $email)->first();
        if (!$user || !$user->seeker) {
            return false;
        }
        
        $seeker = $user->seeker;
        $score = $seeker->calculateProfileCompletion();
        $seeker->save();
        
        return $score;
    }
}
