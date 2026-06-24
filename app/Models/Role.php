<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends SpatieRole
{
    public function otpMethods(): BelongsToMany
    {
        return $this->belongsToMany(OtpMethod::class, 'role_has_otp_methods')
                    ->withPivot(['is_default', 'priority'])
                    ->withTimestamps();
    }

    public function activeOtpMethods(): BelongsToMany
    {
        return $this->belongsToMany(OtpMethod::class, 'role_has_otp_methods')
                    ->where('otp_methods.is_active', true)
                    ->withPivot(['is_default', 'priority'])
                    ->withTimestamps();
    }

    public function getDefaultOtpMethod()
    {
        return $this->otpMethods()
                    ->wherePivot('is_default', true)
                    ->first();
    }

    public function hasOtpMethod($otpMethod): bool
    {
        if (is_string($otpMethod)) {
            return $this->otpMethods()->where('name', $otpMethod)->exists();
        }

        if (is_int($otpMethod)) {
            return $this->otpMethods()->where('otp_method_id', $otpMethod)->exists();
        }

        if ($otpMethod instanceof OtpMethod) {
            return $this->otpMethods()->where('otp_method_id', $otpMethod->id)->exists();
        }

        return false;
    }

    public function assignOtpMethod($otpMethod, bool $isDefault = false, int $priority = 0)
    {
        $otpMethodId = $this->getOtpMethodId($otpMethod);
        
        if (!$otpMethodId) {
            throw new \InvalidArgumentException('Invalid OTP method provided');
        }

        // If setting as default, unset other defaults for this role
        if ($isDefault) {
            $this->otpMethods()->updateExistingPivot(
                $this->otpMethods()->pluck('otp_methods.id'),
                ['is_default' => false]
            );
        }

        return $this->otpMethods()->syncWithoutDetaching([
            $otpMethodId => [
                'is_default' => $isDefault,
                'priority' => $priority,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    public function removeOtpMethod($otpMethod)
    {
        $otpMethodId = $this->getOtpMethodId($otpMethod);
        
        if (!$otpMethodId) {
            return false;
        }

        return $this->otpMethods()->detach($otpMethodId);
    }

    public function syncOtpMethods(array $otpMethods)
    {
        $syncData = [];
        
        foreach ($otpMethods as $otpMethod) {
            if (is_array($otpMethod)) {
                $id = $this->getOtpMethodId($otpMethod['method'] ?? $otpMethod['id']);
                $syncData[$id] = [
                    'is_default' => $otpMethod['is_default'] ?? false,
                    'priority' => $otpMethod['priority'] ?? 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            } else {
                $syncData[$otpMethod] = [
                    'is_default' => false,
                    'priority' => 0,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }
        }

        return $this->otpMethods()->sync($syncData);
    }

    private function getOtpMethodId($otpMethod)
    {
        if (is_string($otpMethod)) {
            $method = OtpMethod::where('name', $otpMethod)->first();
            return $method ? $method->id : null;
        }

        if (is_int($otpMethod)) {
            return $otpMethod;
        }

        if ($otpMethod instanceof OtpMethod) {
            return $otpMethod->id;
        }

        return null;
    }
}