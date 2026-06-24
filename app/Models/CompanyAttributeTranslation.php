<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyAttributeTranslation extends Model
{
    use HasFactory;
    protected $fillable = [
        'company_id',
        'job_id',
        'company_attribute_id',
        'attribute_value',

    ];
}
