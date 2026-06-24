<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * PlanController - Public API for viewing subscription plans
 * 
 * This controller provides read-only access to subscription plans
 * for seekers and employers. No authentication required.
 */
class PlanController extends Controller
{
    /**
     * Get active subscription plans for a specific role.
     * 
     * @param string $role - 'seeker' or 'employer'
     * @return JsonResponse
     */
    public function getPlansByRole($role): JsonResponse
    {
        // Validate role parameter
        if (!in_array($role, ['seeker', 'employer'])) {
            return response()->json([
                'message' => 'Invalid role type. Must be seeker or employer.',
                'plans' => [],
                'total' => 0
            ], 422);
        }

        // Get active subscription plans (not add-ons) for the role
        $plans = Plan::where('role_type', $role)
            ->where('is_active', true)
            ->where('is_addon', false)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'plans' => PlanResource::collection($plans),
            'total' => $plans->count(),
        ]);
    }

    /**
     * Get active add-ons for a specific role.
     * 
     * @param string $role - 'seeker' or 'employer'
     * @return JsonResponse
     */
    public function getAddonsByRole($role): JsonResponse
    {
        // Validate role parameter
        if (!in_array($role, ['seeker', 'employer'])) {
            return response()->json([
                'message' => 'Invalid role type. Must be seeker or employer.',
                'plans' => [],
                'total' => 0
            ], 422);
        }

        // Get active add-ons for the role
        $addons = Plan::where('role_type', $role)
            ->where('is_active', true)
            ->where('is_addon', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'plans' => PlanResource::collection($addons),
            'total' => $addons->count(),
        ]);
    }

    /**
     * Get a specific plan by ID (public view).
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $plan = Plan::where('is_active', true)->findOrFail($id);

        return response()->json([
            'plan' => new PlanResource($plan),
        ]);
    }

    /**
     * Get all available plans (both seeker and employer).
     * This is useful for a public pricing page.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $plans = Plan::where('is_active', true)
            ->orderBy('role_type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Group by role type
        $groupedPlans = $plans->groupBy('role_type');

        return response()->json([
            'plans' => PlanResource::collection($plans),
            'total' => $plans->count(),
            'grouped' => [
                'seeker' => PlanResource::collection($groupedPlans->get('seeker', collect())),
                'employer' => PlanResource::collection($groupedPlans->get('employer', collect())),
            ],
        ]);
    }
}
