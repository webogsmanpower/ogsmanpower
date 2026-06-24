<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;
    protected $fillable = [
        'candidate_id',
        'passport_image',
        'license_image'
    ];
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
