<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRequirement extends Model
{
    use HasFactory;
    protected $fillable = [
        'candidate_id', 'jobs', 'industries', 'region', 'currency',
        'salary', 'search_country_id', 'state_id', 'city_id'
    ];

    protected $casts = [
        'jobs' => 'array',
        'industries' => 'array'
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
