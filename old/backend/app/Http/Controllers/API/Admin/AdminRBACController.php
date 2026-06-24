<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminRBACController extends Controller
{
    // =========================================
    // ROLES MANAGEMENT
    // =========================================

    /**
     * List all roles with their permissions
     */
    public function roles(Request $request): JsonResponse
    {
        try {
            $roles = Role::orderBy('name')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => ucwords(str_replace('_', ' ', $role->name)),
                        'is_system' => in_array($role->name, ['super_admin']),
                        'created_at' => $role->created_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $roles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load roles: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single role with full details
     */
    public function showRole(int $id): JsonResponse
    {
        $role = Role::with(['permissions', 'users'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $this->formatRoleName($role->name),
                'permissions' => $role->permissions->pluck('name'),
                'users' => $role->users->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                ]),
                'is_system' => $role->name === 'super_admin',
                'created_at' => $role->created_at,
            ],
        ]);
    }

    /**
     * Create a new role
     */
    public function createRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create([
            'name' => str_replace(' ', '_', strtolower($validated['name'])),
            'guard_name' => 'web',
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $this->formatRoleName($role->name),
                'permissions' => $role->permissions->pluck('name'),
            ],
        ], 201);
    }

    /**
     * Update a role's permissions
     */
    public function updateRole(Request $request, int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        // Prevent editing super_admin role
        if ($role->name === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify the Super Admin role',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:roles,name,' . $id,
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if (isset($validated['name'])) {
            $role->name = str_replace(' ', '_', strtolower($validated['name']));
            $role->save();
        }

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $this->formatRoleName($role->name),
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    /**
     * Delete a role
     */
    public function deleteRole(int $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        // Prevent deleting system roles
        if (in_array($role->name, ['super_admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system roles',
            ], 403);
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete role with assigned users. Reassign users first.',
            ], 400);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }

    // =========================================
    // PERMISSIONS MANAGEMENT
    // =========================================

    /**
     * List all permissions grouped by module
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::orderBy('name')->get();

        // Group permissions by module using the getModuleFromPermission method
        $grouped = $permissions->groupBy(function ($permission) {
            return $this->getModuleFromPermission($permission->name);
        });

        return response()->json([
            'success' => true,
            'data' => [
                'all' => $permissions->pluck('name'),
                'grouped' => $grouped->map(fn($perms) => $perms->pluck('name')),
            ],
        ]);
    }

    // =========================================
    // STAFF MANAGEMENT
    // =========================================

    /**
     * List all admin staff members
     */
    public function staff(Request $request): JsonResponse
    {
        $query = User::role(['super_admin', 'verification_officer', 'support_agent', 'content_manager', 'finance_manager', 'job_moderator'])
            ->with('roles');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role !== 'all') {
            $query->role($request->role);
        }

        $staff = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $staff->items(),
            'meta' => [
                'current_page' => $staff->currentPage(),
                'last_page' => $staff->lastPage(),
                'per_page' => $staff->perPage(),
                'total' => $staff->total(),
            ],
        ]);
    }

    /**
     * Get a single staff member
     */
    public function showStaff(int $id): JsonResponse
    {
        $user = User::with(['roles', 'permissions'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
                'direct_permissions' => $user->getDirectPermissions()->pluck('name'),
                'last_login_at' => $user->last_login_at,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Create a new staff member
     */
    public function createStaff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)->mixedCase()->numbers()],
            'roles' => 'required|array|min:1',
            'roles.*' => 'string|exists:roles,name',
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            $user->syncRoles($validated['roles']);

            return $user;
        });

        return response()->json([
            'success' => true,
            'message' => 'Staff member created successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
            ],
        ], 201);
    }

    /**
     * Update a staff member's roles
     */
    public function updateStaff(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent self-demotion from super_admin
        if ($user->id === auth()->id() && $user->hasRole('super_admin')) {
            if (!in_array('super_admin', $request->roles ?? [])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove your own Super Admin role',
                ], 403);
            }
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => ['sometimes', Password::min(8)->mixedCase()->numbers()],
            'roles' => 'sometimes|array',
            'roles.*' => 'string|exists:roles,name',
        ]);

        DB::transaction(function () use ($user, $validated) {
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }
            if (isset($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }
            $user->save();

            if (isset($validated['roles'])) {
                $user->syncRoles($validated['roles']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Staff member updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->fresh()->roles->pluck('name'),
            ],
        ]);
    }

    /**
     * Delete a staff member
     */
    public function deleteStaff(int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account',
            ], 403);
        }

        // Prevent deleting the last super_admin
        if ($user->hasRole('super_admin')) {
            $superAdminCount = User::role('super_admin')->count();
            if ($superAdminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete the last Super Admin',
                ], 403);
            }
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff member deleted successfully',
        ]);
    }

    // =========================================
    // HELPERS
    // =========================================

    /**
     * Format role name for display
     */
    private function formatRoleName(string $name): string
    {
        return ucwords(str_replace('_', ' ', $name));
    }

    /**
     * Get module name from permission
     */
    private function getModuleFromPermission(string $permission): string
    {
        $moduleKeywords = [
            'dashboard' => 'dashboard',
            'analytics' => 'dashboard',
            'reports' => 'dashboard',
            'employers' => 'employers',
            'employer' => 'employers',
            'seekers' => 'seekers',
            'seeker' => 'seekers',
            'jobs' => 'jobs',
            'job' => 'jobs',
            'staff' => 'staff',
            'roles' => 'access_control',
            'permissions' => 'access_control',
            'plans' => 'finance',
            'subscriptions' => 'finance',
            'transactions' => 'finance',
            'refunds' => 'finance',
            'form' => 'configuration',
            'schemas' => 'configuration',
            'settings' => 'configuration',
            'skills' => 'configuration',
            'industries' => 'configuration',
            'titles' => 'configuration',
            'assessments' => 'assessments',
            'assessment' => 'assessments',
            'audit' => 'logs',
            'activity' => 'logs',
            'logs' => 'logs',
            'impersonate' => 'impersonation',
            'impersonation' => 'impersonation',
            // Check admin last to avoid categorizing all permissions as system
            'system' => 'system',
            'cache' => 'system',
            'maintenance' => 'system',
            'health' => 'system',
            'admin' => 'system',
        ];

        foreach ($moduleKeywords as $keyword => $module) {
            if (str_contains($permission, $keyword)) {
                return $module;
            }
        }

        return 'other';
    }
}
