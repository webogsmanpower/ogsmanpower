<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

/**
 * AdminPlanController - Full CRUD operations for subscription plans
 */
class AdminPlanController extends Controller
{
    /**
     * Display a listing of subscription plans.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Plan::query();

        // Filter by role type
        if ($request->has('role_type')) {
            $query->forRole($request->role_type);
        }

        // Filter by add-on status
        if ($request->has('is_addon')) {
            $query->where('is_addon', $request->boolean('is_addon'));
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Order by sort_order then name
        $plans = $query->ordered()->get();

        return response()->json([
            'plans' => PlanResource::collection($plans),
            'total' => $plans->count(),
        ]);
    }

    /**
     * Store a newly created subscription plan.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'interval' => ['required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'is_addon' => 'boolean',
            'role_type' => ['required', Rule::in(['seeker', 'employer'])],
            'features' => 'nullable|array',
            'features.*' => 'string',
            'limits' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'trial_days' => 'integer|min:0',
            'stripe_price_id' => 'nullable|string|max:255',
            'paypal_plan_id' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plan = Plan::create($validator->validated());

            return response()->json([
                'message' => 'Plan created successfully',
                'plan' => new PlanResource($plan),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified subscription plan.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        return response()->json([
            'plan' => new PlanResource($plan),
        ]);
    }

    /**
     * Update the specified subscription plan.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'description_ar' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'interval' => ['sometimes', 'required', Rule::in(['monthly', 'yearly', 'lifetime'])],
            'is_addon' => 'boolean',
            'role_type' => ['sometimes', 'required', Rule::in(['seeker', 'employer'])],
            'features' => 'nullable|array',
            'features.*' => 'string',
            'limits' => 'nullable|array',
            'sort_order' => 'sometimes|integer|min:0',
            'trial_days' => 'sometimes|integer|min:0',
            'stripe_price_id' => 'nullable|string|max:255',
            'paypal_plan_id' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plan->update($validator->validated());

            return response()->json([
                'message' => 'Plan updated successfully',
                'plan' => new PlanResource($plan),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified subscription plan.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        try {
            $plan->delete();

            return response()->json([
                'message' => 'Plan deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle the status of a subscription plan.
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        try {
            $plan->is_active = !$plan->is_active;
            $plan->save();

            return response()->json([
                'message' => 'Plan status updated successfully',
                'plan' => new PlanResource($plan),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle plan status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get plans by role.
     */
    public function getByRole(Request $request, $role): JsonResponse
    {
        if (!in_array($role, ['seeker', 'employer'])) {
            return response()->json([
                'message' => 'Invalid role type',
            ], 422);
        }

        $plans = Plan::forRole($role)->ordered()->get();

        return response()->json([
            'plans' => PlanResource::collection($plans),
            'total' => $plans->count(),
        ]);
    }

    /**
     * Duplicate a plan.
     */
    public function duplicate(Request $request, $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);

        try {
            $newPlan = $plan->replicate();
            $newPlan->name = $plan->name . ' (Copy)';
            $newPlan->slug = null; // Will be auto-generated
            $newPlan->save();

            return response()->json([
                'message' => 'Plan duplicated successfully',
                'plan' => new PlanResource($newPlan),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to duplicate plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
