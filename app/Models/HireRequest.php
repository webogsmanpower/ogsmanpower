<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HireRequest extends Model
{
    use HasFactory;
    protected $fillable = ['candidate_id', 'company_id', 'message'];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function employer()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
