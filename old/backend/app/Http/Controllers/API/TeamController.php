<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployerUserResource;
use App\Models\EmployerUser;
use App\Services\TeamService;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * TeamController
 * 
 * Handles employer team/sub-user management (RBAC).
 */
class TeamController extends Controller
{
    public function __construct(
        protected TeamService $teamService,
        protected EmployerService $employerService
    ) {}

    /**
     * List all team members.
     */
    public function index(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $teamMembers = $this->teamService->getTeamMembers($employer);

        return response()->json([
            'data' => EmployerUserResource::collection($teamMembers),
        ]);
    }

    /**
     * Invite a new team member.
     */
    public function invite(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'role' => 'required|in:admin,hr_manager,recruiter,interviewer,viewer',
            'permissions' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $result = $this->teamService->inviteTeamMember(
            $employer,
            $request->user(),
            $validator->validated()
        );

        if (!$result['success']) {
            return response()->json([
                'message' => $result['message'],
            ], $result['code'] ?? 422);
        }

        return response()->json([
            'message' => 'Team member invited successfully',
            'data' => new EmployerUserResource($result['member']->load('user')),
        ], 201);
    }

    /**
     * Update a team member's role/permissions.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'sometimes|in:admin,hr_manager,recruiter,interviewer,viewer',
            'permissions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $member = $this->teamService->getTeamMemberById($employer, $id);

        if (!$member) {
            return response()->json(['message' => 'Team member not found'], 404);
        }

        $member = $this->teamService->updateTeamMember($member, $validator->validated());

        return response()->json([
            'message' => 'Team member updated successfully',
            'data' => new EmployerUserResource($member->load('user')),
        ]);
    }

    /**
     * Remove a team member.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $member = $this->teamService->getTeamMemberById($employer, $id);

        if (!$member) {
            return response()->json(['message' => 'Team member not found'], 404);
        }

        // Prevent removing yourself
        if ($member->user_id === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot remove yourself from the team',
            ], 422);
        }

        $this->teamService->removeTeamMember($member);

        return response()->json([
            'message' => 'Team member removed successfully',
        ]);
    }

    /**
     * Get available roles and their permissions.
     */
    public function roles(): JsonResponse
    {
        return response()->json([
            'data' => EmployerUser::ROLE_PERMISSIONS,
        ]);
    }
}
