<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateSubscription extends Model
{
    protected $guarded = [];
    use HasFactory;

    public function candidateplan(){
        return $this->belongsTo(CandidatePlan::class,'candidate_plan_id');
    }

    public function candidate(){
        return $this->belongsTo(Candidate::class,'candidate_id');
    }
}
