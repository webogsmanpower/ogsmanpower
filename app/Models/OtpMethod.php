<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role;

class OtpMethod extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_active',
        'config'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array'
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_otp_methods')
                    ->withPivot(['is_default', 'priority'])
                    ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Get roles that have this OTP method as default
    public function defaultRoles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_otp_methods')
                    ->withPivot(['is_default', 'priority'])
                    ->wherePivot('is_default', true)
                    ->withTimestamps();
    }
}