<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidatePlan extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'price', 'duration'];

    public function candidatePlan()
    {
        return $this->hasMany(CandidateSubscription::class, 'candidate_plan_id');
    }
}
