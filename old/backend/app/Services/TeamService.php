<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\EmployerUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * TeamService
 * 
 * Business logic for employer team/sub-user management.
 */
class TeamService
{
    /**
     * Get all team members for an employer.
     */
    public function getTeamMembers(Employer $employer): Collection
    {
        return $employer->teamMembers()->with('user')->get();
    }

    /**
     * Get a team member by ID.
     */
    public function getTeamMemberById(Employer $employer, int $id): ?EmployerUser
    {
        return $employer->teamMembers()->find($id);
    }

    /**
     * Invite a new team member.
     */
    public function inviteTeamMember(Employer $employer, User $invitedBy, array $data): array
    {
        $email = $data['email'];

        // Check if user already exists
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Create new user with temporary password
            $user = User::create([
                'name' => $data['name'],
                'email' => $email,
                'password' => Hash::make(Str::random(16)),
                'role' => 'employer', // They're joining as employer team member
            ]);
        }

        // Check if already a team member
        $existingMember = EmployerUser::where('employer_id', $employer->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingMember) {
            return [
                'success' => false,
                'message' => 'User is already a team member',
                'code' => 409,
            ];
        }

        // Create team membership
        $member = EmployerUser::create([
            'employer_id' => $employer->id,
            'user_id' => $user->id,
            'role' => $data['role'],
            'permissions' => $data['permissions'] ?? null,
            'invited_by' => $invitedBy->id,
            'invited_at' => now(),
            'is_active' => true,
        ]);

        // TODO: Send invitation email to user

        return [
            'success' => true,
            'message' => 'Team member invited successfully',
            'member' => $member,
        ];
    }

    /**
     * Update a team member.
     */
    public function updateTeamMember(EmployerUser $member, array $data): EmployerUser
    {
        $member->update($data);
        return $member->fresh();
    }

    /**
     * Remove a team member.
     */
    public function removeTeamMember(EmployerUser $member): bool
    {
        return $member->delete();
    }

    /**
     * Accept team invitation.
     */
    public function acceptInvitation(EmployerUser $member): EmployerUser
    {
        $member->update(['accepted_at' => now()]);
        return $member->fresh();
    }
}
