<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyAttribute extends Model
{
    use HasFactory;
     protected $fillable = [
        'company_id',
        'attribute_name',
        'input_type',
        'attribute_value',
        'is_required',
        'is_active'
    ];
    public function comapny_id()
    {
        return $this->belongsTo(Company::class);
    }
}
