<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EmployerUser Model (RBAC Sub-Users)
 * 
 * Represents team members of an employer with specific roles and permissions.
 */
class EmployerUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'user_id',
        'role',
        'permissions',
        'invited_by',
        'invited_at',
        'accepted_at',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'invited_at' => 'datetime',
        'accepted_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Default permissions for each role.
     */
    public const ROLE_PERMISSIONS = [
        'admin' => [
            'jobs.create', 'jobs.read', 'jobs.update', 'jobs.delete',
            'applications.read', 'applications.update', 'applications.shortlist', 'applications.reject',
            'interviews.create', 'interviews.read', 'interviews.update', 'interviews.delete',
            'contracts.create', 'contracts.read', 'contracts.update', 'contracts.send',
            'visa.read', 'visa.update',
            'documents.read', 'documents.verify',
            'team.create', 'team.read', 'team.update', 'team.delete',
            'settings.read', 'settings.update',
            'messages.read', 'messages.send',
        ],
        'hr_manager' => [
            'jobs.create', 'jobs.read', 'jobs.update',
            'applications.read', 'applications.update', 'applications.shortlist', 'applications.reject',
            'interviews.create', 'interviews.read', 'interviews.update',
            'contracts.create', 'contracts.read', 'contracts.update', 'contracts.send',
            'visa.read', 'visa.update',
            'documents.read', 'documents.verify',
            'messages.read', 'messages.send',
        ],
        'recruiter' => [
            'jobs.read',
            'applications.read', 'applications.update', 'applications.shortlist',
            'interviews.create', 'interviews.read', 'interviews.update',
            'documents.read',
            'messages.read', 'messages.send',
        ],
        'interviewer' => [
            'jobs.read',
            'applications.read',
            'interviews.read', 'interviews.update',
            'messages.read', 'messages.send',
        ],
        'viewer' => [
            'jobs.read',
            'applications.read',
            'interviews.read',
            'contracts.read',
            'documents.read',
        ],
    ];

    /**
     * Get the employer.
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who sent the invitation.
     */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Check custom permissions first
        if ($this->permissions && isset($this->permissions[$permission])) {
            return $this->permissions[$permission];
        }

        // Fall back to role-based permissions
        $rolePermissions = self::ROLE_PERMISSIONS[$this->role] ?? [];
        return in_array($permission, $rolePermissions);
    }

    /**
     * Get all effective permissions.
     */
    public function getEffectivePermissions(): array
    {
        $rolePermissions = self::ROLE_PERMISSIONS[$this->role] ?? [];
        
        // Merge with custom permissions
        if ($this->permissions) {
            foreach ($this->permissions as $permission => $granted) {
                if ($granted && !in_array($permission, $rolePermissions)) {
                    $rolePermissions[] = $permission;
                } elseif (!$granted) {
                    $rolePermissions = array_filter($rolePermissions, fn($p) => $p !== $permission);
                }
            }
        }

        return array_values($rolePermissions);
    }

    /**
     * Scope for active members.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for accepted invitations.
     */
    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_at');
    }
}
