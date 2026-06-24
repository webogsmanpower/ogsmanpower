<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminPlanController extends Controller
{
    /**
     * Display a listing of plans.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Plan::query();
        
        // Filter by role type if specified
        if ($request->has('role_type')) {
            $query->where('role_type', $request->role_type);
        }
        
        // Filter by active status if specified
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        $plans = $query->orderBy('sort_order')->orderBy('name')->get();
        
        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Store a newly created plan.
     */
    public function store(Request $request): JsonResponse
    {
        // Phase 1: Debug the incoming payload
        \Log::info('=== PLAN STORE DEBUG ===');
        \Log::info('Raw request data', ['data' => $request->all()]);
        \Log::info('Features type', ['type' => gettype($request->input('features'))]);
        \Log::info('Features value', ['features' => $request->input('features')]);
        \Log::info('Limits type', ['type' => gettype($request->input('limits'))]);
        \Log::info('Limits value', ['limits' => $request->input('limits')]);
        \Log::info('Price type', ['type' => gettype($request->input('price'))]);
        \Log::info('Price value', ['price' => $request->input('price')]);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:plans,slug',
            'role_type' => ['required', Rule::in(['employer', 'seeker'])],
            'price' => 'required|numeric|min:0',
            'interval' => ['required', Rule::in(['monthly', 'yearly', 'one_time'])],
            'is_addon' => 'boolean',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'bilingual_cv_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'integer|min:0',
            'discount_enabled' => 'boolean',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_valid_until' => 'nullable|date',
        ], [
            'slug.unique' => 'The slug must be unique. Please choose a different identifier.',
            'role_type.required' => 'Please select whether this is for Employers or Seekers.',
            'is_addon.boolean' => 'Add-on status must be true or false.',
        ]);

        \Log::info('Validated data', ['data' => $validated]);
        \Log::info('Limits in validated data', ['limits' => $validated['limits'] ?? 'NOT SET']);
        \Log::info('Limits type', ['type' => gettype($validated['limits'] ?? null)]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $plan = Plan::create($validated);
        
        \Log::info('Created plan', ['plan' => $plan->toArray()]);
        \Log::info('Created plan limits', ['limits' => $plan->limits]);
        \Log::info('Created plan limits type', ['type' => gettype($plan->limits)]);
        \Log::info('=== END DEBUG ===');
        
        return response()->json([
            'message' => 'Plan created successfully',
            'plan' => $plan,
        ], 201);
    }

    /**
     * Display the specified plan.
     */
    public function show(string $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        
        return response()->json([
            'plan' => $plan,
        ]);
    }

    /**
     * Update the specified plan.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('plans', 'slug')->ignore($plan->id),
            ],
            'role_type' => ['sometimes', 'required', Rule::in(['employer', 'seeker'])],
            'price' => 'sometimes|required|numeric|min:0',
            'interval' => ['sometimes', 'required', Rule::in(['monthly', 'yearly', 'one_time'])],
            'is_addon' => 'boolean',
            'features' => 'nullable|array',
            'limits' => 'nullable|array',
            'bilingual_cv_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'sort_order' => 'sometimes|integer|min:0',
        ], [
            'slug.unique' => 'The slug must be unique. Please choose a different identifier.',
            'role_type.required' => 'Please select whether this is for Employers or Seekers.',
            'is_addon.boolean' => 'Add-on status must be true or false.',
        ]);

        $plan->update($validated);
        
        return response()->json([
            'message' => 'Plan updated successfully',
            'plan' => $plan->fresh(),
        ]);
    }

    /**
     * Remove the specified plan.
     */
    public function destroy(string $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        
        // Check if plan has any active subscriptions
        if ($plan->subscriptions()->active()->exists()) {
            return response()->json([
                'message' => 'Cannot delete plan with active subscriptions. Please cancel them first or archive the plan.',
            ], 422);
        }
        
        $plan->delete();
        
        return response()->json([
            'message' => 'Plan deleted successfully',
        ]);
    }

    /**
     * Toggle plan active status.
     */
    public function toggleStatus(string $id): JsonResponse
    {
        $plan = Plan::findOrFail($id);
        $plan->is_active = !$plan->is_active;
        $plan->save();
        
        return response()->json([
            'message' => "Plan " . ($plan->is_active ? 'activated' : 'deactivated') . " successfully",
            'plan' => $plan,
        ]);
    }

    /**
     * Get plans by role type.
     */
    public function getByRole(string $role): JsonResponse
    {
        if (!in_array($role, ['employer', 'seeker'])) {
            return response()->json([
                'error' => 'Invalid role type',
                'message' => 'Role must be either employer or seeker',
            ], 400);
        }
        
        $plans = Plan::byRole($role)->active()->orderBy('sort_order')->orderBy('name')->get();
        
        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Get add-on plans by role type.
     */
    public function getAddonsByRole(string $role): JsonResponse
    {
        if (!in_array($role, ['employer', 'seeker'])) {
            return response()->json([
                'error' => 'Invalid role type',
                'message' => 'Role must be either employer or seeker',
            ], 400);
        }
        
        $plans = Plan::byRole($role)->addons()->active()->orderBy('sort_order')->orderBy('name')->get();
        
        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Get subscription plans by role type (excluding add-ons).
     */
    public function getSubscriptionsByRole(string $role): JsonResponse
    {
        if (!in_array($role, ['employer', 'seeker'])) {
            return response()->json([
                'error' => 'Invalid role type',
                'message' => 'Role must be either employer or seeker',
            ], 400);
        }
        
        $plans = Plan::byRole($role)->subscriptions()->active()->orderBy('sort_order')->orderBy('name')->get();
        
        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Get role-specific limits structure for plan builder.
     */
    public function getRoleLimitsStructure(string $role): JsonResponse
    {
        if (!in_array($role, ['employer', 'seeker'])) {
            return response()->json([
                'error' => 'Invalid role type',
                'message' => 'Role must be either employer or seeker',
            ], 400);
        }

        $structure = match($role) {
            'seeker' => [
                'cv_downloads' => [
                    'type' => 'number',
                    'label' => 'CV Downloads',
                    'description' => 'Number of CV downloads allowed per month',
                    'default' => 0,
                    'min' => 0,
                ],
                'applications' => [
                    'type' => 'number',
                    'label' => 'Job Applications',
                    'description' => 'Number of job applications allowed per month',
                    'default' => 0,
                    'min' => 0,
                ],
                'featured_profile' => [
                    'type' => 'boolean',
                    'label' => 'Featured Profile',
                    'description' => 'Profile gets highlighted in search results',
                    'default' => false,
                ],
                'bilingual_cv' => [
                    'type' => 'boolean',
                    'label' => 'Bilingual CV',
                    'description' => 'Ability to generate CVs in multiple languages',
                    'default' => false,
                ],
                'priority_application' => [
                    'type' => 'boolean',
                    'label' => 'Priority Application',
                    'description' => 'Applications get priority visibility to employers',
                    'default' => false,
                ],
                'resume_review' => [
                    'type' => 'number',
                    'label' => 'Resume Reviews',
                    'description' => 'Number of professional resume reviews included',
                    'default' => 0,
                    'min' => 0,
                ],
            ],
            'employer' => [
                'job_posts' => [
                    'type' => 'number',
                    'label' => 'Job Posts',
                    'description' => 'Number of active job posts allowed',
                    'default' => 0,
                    'min' => 0,
                ],
                'featured_jobs' => [
                    'type' => 'number',
                    'label' => 'Featured Jobs',
                    'description' => 'Number of jobs that can be featured per month',
                    'default' => 0,
                    'min' => 0,
                ],
                'cv_access' => [
                    'type' => 'number',
                    'label' => 'CV Database Access',
                    'description' => 'Number of CVs that can be viewed/downloaded per month',
                    'default' => 0,
                    'min' => 0,
                ],
                'urgent_label' => [
                    'type' => 'number',
                    'label' => 'Urgent Labels',
                    'description' => 'Number of urgent job labels per month',
                    'default' => 0,
                    'min' => 0,
                ],
                'database_access' => [
                    'type' => 'boolean',
                    'label' => 'Full Database Access',
                    'description' => 'Access to complete candidate database',
                    'default' => false,
                ],
                'company_highlighting' => [
                    'type' => 'boolean',
                    'label' => 'Company Highlighting',
                    'description' => 'Company profile gets premium placement',
                    'default' => false,
                ],
            ],
            default => [],
        };

        return response()->json([
            'structure' => $structure,
        ]);
    }
}
