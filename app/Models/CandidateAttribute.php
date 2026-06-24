<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateAttribute extends Model
{
    use HasFactory;
    protected $fillable = [
        'candidate_id',
        'attribute_name',
        'input_type',
        'attribute_value',
        'is_required',
        'is_active'
    ];
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
