<?php

namespace App\Models;

use App\Http\Controllers\Admin\IndustryTypeController;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustryType extends Model implements TranslatableContract
{
    use HasFactory, Translatable;

    protected $guarded = [];

    public $translatedAttributes = ['name'];

    protected $with = ['translations'];

    /**
     * Get the companies for the industry type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function companies()
    {
        return $this->hasMany(Company::class, 'industry_type_id');
    }
    public function industryTranslation()
    {
        return $this->hasOne(IndustryTypeTranslation::class, 'industry_type_id');
    }

}
