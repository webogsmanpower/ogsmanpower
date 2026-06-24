<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Language\Entities\Language;
use Modules\Location\Entities\Country;

class Candidate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['full_address'];

    protected $casts = [
        'date_of_birth' => 'datetime',
        'allow_in_search' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($candidate) {
            $candidate->profile_complete = $candidate->calculateProfileCompletion();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getPhotoAttribute($photo)
    {
        if ($photo == null) {
            return asset('backend/image/default.png');
        }

        return asset($photo);
    }

    public function getFullAddressAttribute()
    {
        $country = $this->country;
        $region = $this->region;

        $extra = $region ? ' , ' : '';

        return $region . $extra . $country;
    }

    public function getCvUrlAttribute()
    {
        if ($this->cv == null) {
            return '';
        }

        return route('website.candidate.download.cv', $this->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Profile Completion
    |--------------------------------------------------------------------------
    */

    public function calculateProfileCompletion()
    {
        $totalFields = 18;
        $completed = 0;

        if ($this->user_id) $completed++;
        if ($this->profession_id) $completed++;
        if ($this->experience_id) $completed++;
        if ($this->education_id) $completed++;
        if ($this->title) $completed++;
        if ($this->gender) $completed++;
        if ($this->website) $completed++;

        // check real database column
        if ($this->getRawOriginal('photo')) $completed++;

        if ($this->resume_format) $completed++;
        if ($this->bio) $completed++;
        if ($this->marital_status) $completed++;
        if ($this->birth_date) $completed++;
        if ($this->address) $completed++;
        if ($this->district) $completed++;
        if ($this->region) $completed++;
        if ($this->country_id) $completed++;
        if ($this->status) $completed++;
        if ($this->available_in) $completed++;

        return round(($completed / $totalFields) * 100);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('visibility', 1)->whereHas('user', function ($q) {
            $q->whereStatus(1);
        });
    }

    public function scopeInactive($query)
    {
        return $query->where('visibility', 0)->whereHas('user', function ($q) {
            $q->whereStatus(0);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expected_country()
    {
        return $this->belongsTo(Country::class,'country_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function profession()
    {
        return $this->belongsTo(Profession::class,'profession_id');
    }

    public function experience()
    {
        return $this->belongsTo(Experience::class,'experience_id');
    }

    public function education()
    {
        return $this->belongsTo(Education::class,'education_id');
    }

    public function agent()
    {
        return $this->belongsTo(Admin::class,'admin_id');
    }

    public function bookmarkJobs()
    {
        return $this->belongsToMany(Job::class,'bookmark_candidate_job')
            ->with('company','category','job_type:id');
    }

    public function bookmarkCompanies()
    {
        return $this->belongsToMany(Company::class,'bookmark_candidate_company');
    }

    public function attributes()
    {
        return $this->hasMany(CandidateAttribute::class);
    }

    public function bookmarkCandidates()
    {
        return $this->belongsToMany(Company::class,'bookmark_company')->withTimestamps();
    }

    public function appliedJobs()
    {
        return $this->belongsToMany(Job::class,'applied_jobs')
            ->with('company','job_type:id')
            ->withTimestamps();
    }

    public function resumes()
    {
        return $this->hasMany(CandidateResume::class,'candidate_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class,'candidate_skill');
    }

    public function languages()
    {
        return $this->belongsToMany(CandidateLanguage::class,'candidate_language');
    }

    public function experiences()
    {
        return $this->hasMany(CandidateExperience::class,'candidate_id');
    }

    public function educations()
    {
        return $this->hasMany(CandidateEducation::class,'candidate_id');
    }
    public function jobRole()
{
    return $this->belongsTo(JobRole::class,'job_role_id');
}

    public function coverLetter()
    {
        return $this->hasOne(AppliedJob::class);
    }

    public function socialInfo(): HasMany
    {
        return $this->hasMany(SocialLink::class,'user_id');
    }

    public function already_views()
    {
        return $this->hasMany(CandidateCvView::class,'candidate_id','id');
    }

    public function jobRoleAlerts()
    {
        return $this->hasMany(CandidateJobAlert::class,'candidate_id','id');
    }

    public function candidateSubscription()
    {
        return $this->hasOne(CandidateSubscription::class,'candidate_id');
    }

    public function getCVPath()
    {
        return $this->hasOne(CandidateResume::class);
    }
}