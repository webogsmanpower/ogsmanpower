<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Employer;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;

class EmployerTeamController extends Controller
{
    // Middleware will be applied via route definition

    /**
     * Get team members for the current employer
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $employer = $user->employer;

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        // Check if user has permission to view team
        if (!$user->can('employer.view_team')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $teamMembers = User::where('employer_id', $employer->id)
            ->with(['roles' => function($query) {
                $query->select('name', 'guard_name');
            }])
            ->select('id', 'name', 'email', 'created_at', 'last_login_at', 'active_role')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->roles->first()?->name ?? 'No role assigned',
                    'created_at' => $member->created_at,
                    'last_login_at' => $member->last_login_at,
                    'is_owner' => $member->id === $member->employer?->user_id,
                ];
            });

        return response()->json([
            'team_members' => $teamMembers,
            'total_members' => $teamMembers->count(),
        ]);
    }

    /**
     * Invite a new team member
     */
    public function invite(Request $request)
    {
        $user = auth()->user();
        $employer = $user->employer;

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        // Check if user has permission to invite team members
        if (!$user->can('employer.invite_team')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'role_name' => 'required|string|exists:roles,name',
        ]);

        // Validate that the role is an employer role (not admin role)
        $validEmployerRoles = ['employer_owner', 'hr_manager', 'interviewer', 'recruiter'];
        if (!in_array($validated['role_name'], $validEmployerRoles)) {
            throw ValidationException::withMessages([
                'role_name' => 'Invalid role selected. Must be an employer team role.',
            ]);
        }

        // Prevent assigning owner role to non-owners
        if ($validated['role_name'] === 'employer_owner' && !$user->hasRole('employer_owner')) {
            throw ValidationException::withMessages([
                'role_name' => 'Only company owners can assign owner role.',
            ]);
        }

        try {
            DB::beginTransaction();

            // Create new user
            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(16)), // Random password, will be reset
                'role' => 'employer', // Set primary role as employer
                'employer_id' => $employer->id,
                'is_onboarding_completed' => false,
            ]);

            // Assign the specified role
            $newUser->assignRole($validated['role_name']);

            // TODO: Send invitation email with setup link
            // Mail::to($validated['email'])->send(new TeamInvitationMail($newUser, $employer));

            DB::commit();

            return response()->json([
                'message' => 'Team member invited successfully',
                'user' => [
                    'id' => $newUser->id,
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'role' => $validated['role_name'],
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to invite team member: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update team member role
     */
    public function updateRole(Request $request, User $teamMember)
    {
        $user = auth()->user();
        $employer = $user->employer;

        if (!$employer || $teamMember->employer_id !== $employer->id) {
            return response()->json(['message' => 'Team member not found'], 404);
        }

        // Check if user has permission to edit team
        if (!$user->can('employer.edit_team')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'role_name' => 'required|string|exists:roles,name',
        ]);

        // Validate that the role is an employer role
        $validEmployerRoles = ['employer_owner', 'hr_manager', 'interviewer', 'recruiter'];
        if (!in_array($validated['role_name'], $validEmployerRoles)) {
            throw ValidationException::withMessages([
                'role_name' => 'Invalid role selected. Must be an employer team role.',
            ]);
        }

        // Prevent modifying owner role unless you are the owner
        if ($teamMember->hasRole('employer_owner') && !$user->hasRole('employer_owner')) {
            return response()->json(['message' => 'Cannot modify company owner role'], 403);
        }

        // Prevent assigning owner role to non-owners
        if ($validated['role_name'] === 'employer_owner' && !$user->hasRole('employer_owner')) {
            return response()->json(['message' => 'Only company owners can assign owner role'], 403);
        }

        // Prevent self-demotion from owner role
        if ($teamMember->id === $user->id && $validated['role_name'] !== 'employer_owner' && $user->hasRole('employer_owner')) {
            return response()->json(['message' => 'Cannot demote yourself from owner role'], 403);
        }

        try {
            DB::beginTransaction();

            // Remove all existing employer roles and assign new one
            $teamMember->syncRoles([$validated['role_name']]);

            DB::commit();

            return response()->json([
                'message' => 'Team member role updated successfully',
                'user' => [
                    'id' => $teamMember->id,
                    'name' => $teamMember->name,
                    'email' => $teamMember->email,
                    'role' => $validated['role_name'],
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update role: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove team member
     */
    public function remove(Request $request, User $teamMember)
    {
        $user = auth()->user();
        $employer = $user->employer;

        if (!$employer || $teamMember->employer_id !== $employer->id) {
            return response()->json(['message' => 'Team member not found'], 404);
        }

        // Check if user has permission to remove team members
        if (!$user->can('employer.remove_team')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Cannot remove the company owner
        if ($teamMember->hasRole('employer_owner')) {
            return response()->json(['message' => 'Cannot remove company owner'], 403);
        }

        // Cannot remove yourself
        if ($teamMember->id === $user->id) {
            return response()->json(['message' => 'Cannot remove yourself from the team'], 403);
        }

        try {
            DB::beginTransaction();

            // Soft delete by setting employer_id to null and deactivating
            $teamMember->update([
                'employer_id' => null,
                'role' => null,
                'active_role' => null,
            ]);

            // Remove all roles
            $teamMember->syncRoles([]);

            DB::commit();

            return response()->json(['message' => 'Team member removed successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to remove team member: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get available roles for team assignment
     */
    public function getAvailableRoles(Request $request)
    {
        $user = auth()->user();

        // Check if user has permission to view team
        if (!$user->can('employer.view_team')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $roles = Role::whereIn('name', ['hr_manager', 'interviewer', 'recruiter'])
            ->get(['name', 'guard_name'])
            ->map(function ($role) {
                return [
                    'name' => $role->name,
                    'display_name' => ucwords(str_replace('_', ' ', $role->name)),
                    'description' => $this->getRoleDescription($role->name),
                ];
            });

        // Add owner role if current user is owner
        if ($user->hasRole('employer_owner')) {
            $ownerRole = [
                'name' => 'employer_owner',
                'display_name' => 'Company Owner',
                'description' => 'Full access to all company settings, billing, and team management',
            ];
            $roles->prepend($ownerRole);
        }

        return response()->json(['roles' => $roles]);
    }

    /**
     * Get role description
     */
    private function getRoleDescription($roleName): string
    {
        $descriptions = [
            'hr_manager' => 'Can manage jobs, view candidates, and handle hiring process',
            'interviewer' => 'Can view candidate profiles and conduct interviews',
            'recruiter' => 'Can post jobs, source candidates, and manage recruitment process',
        ];

        return $descriptions[$roleName] ?? 'Team member role';
    }
}
