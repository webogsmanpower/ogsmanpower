<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * AdminVerificationController
 * 
 * Handles employer verification workflow for admins.
 * Core functionality for the Admin Module gatekeeping feature.
 */
class AdminVerificationController extends Controller
{
    /**
     * Get verification queue (pending employers).
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function queue(Request $request)
    {
        $query = Employer::with(['user:id,name,email,created_at'])
            ->where('verification_status', 'pending')
            ->orderBy('created_at', 'asc');

        // Optional search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $employers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $employers->items(),
            'meta' => [
                'current_page' => $employers->currentPage(),
                'last_page' => $employers->lastPage(),
                'per_page' => $employers->perPage(),
                'total' => $employers->total(),
            ],
        ]);
    }

    /**
     * Get all employers with optional status filter.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Employer::with([
            'user:id,name,email,created_at',
            'verifiedBy:id,name',
        ]);

        // Filter by verification status
        if ($request->has('status') && in_array($request->status, ['pending', 'verified', 'rejected'])) {
            $query->where('verification_status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('trade_license_number', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $employers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $employers->items(),
            'meta' => [
                'current_page' => $employers->currentPage(),
                'last_page' => $employers->lastPage(),
                'per_page' => $employers->perPage(),
                'total' => $employers->total(),
            ],
        ]);
    }

    /**
     * Get employer details for review.
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $employer = Employer::with([
            'user.currentSubscription.plan', // Eager load subscription
            'verifiedBy:id,name',
            'jobPostings' => function ($q) {
                $q->select('id', 'employer_id', 'title', 'status', 'created_at')
                    ->latest()
                    ->limit(5);
            },
        ])->findOrFail($id);

        // Build document URLs using getSafeImageUrl pattern
        $documents = [];
        
        if ($employer->registration_document_path) {
            $documents['registration_document'] = $this->getSafeDocumentUrl($employer->registration_document_path);
        }
        
        if ($employer->logo_path) {
            $documents['logo'] = $this->getSafeDocumentUrl($employer->logo_path);
        }

        // Get recent activity logs if Spatie Activitylog is installed
        $activityLogs = [];
        if (class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            $activityLogs = \Spatie\Activitylog\Models\Activity::where('causer_id', $employer->user_id)
                ->orWhere('subject_id', $employer->user_id)
                ->latest()
                ->take(10)
                ->get();
        }

        // Get available plans for upgrade dropdown
        $availablePlans = \App\Models\Plan::where('role_type', 'employer')
            ->where('is_active', true)
            ->where('is_addon', false)
            ->get();

        $availableAddons = \App\Models\Plan::where('role_type', 'employer')
            ->where('is_active', true)
            ->where('is_addon', true)
            ->get();

        return response()->json([
            'employer' => $employer,
            'documents' => $documents,
            'stats' => [
                'total_jobs' => $employer->jobPostings()->count(),
                'active_jobs' => $employer->jobPostings()->where('status', 'published')->count(),
                'total_applications' => $employer->applications()->count(),
            ],
            'activity_logs' => $activityLogs,
            'available_plans' => $availablePlans,
            'available_addons' => $availableAddons,
        ]);
    }

    /**
     * Verify (approve) an employer.
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request, $id)
    {
        $employer = Employer::findOrFail($id);

        if ($employer->verification_status === 'verified') {
            return response()->json([
                'message' => 'Employer is already verified.',
            ], 422);
        }

        DB::transaction(function () use ($employer, $request) {
            $employer->update([
                'is_verified' => true,
                'verification_status' => 'verified',
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
                'rejection_reason' => null,
                'rejection_date' => null,
                'rejected_by' => null,
            ]);

            // TODO: Send approval email notification to employer
            // Mail::to($employer->user->email)->send(new EmployerApprovedMail($employer));
        });

        return response()->json([
            'message' => 'Employer verified successfully.',
            'employer' => $employer->fresh(['user:id,name,email', 'verifiedBy:id,name']),
        ]);
    }

    /**
     * Reject an employer.
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $employer = Employer::findOrFail($id);

        if ($employer->verification_status === 'rejected') {
            return response()->json([
                'message' => 'Employer is already rejected.',
            ], 422);
        }

        DB::transaction(function () use ($employer, $request) {
            $employer->update([
                'is_verified' => false,
                'verification_status' => 'rejected',
                'rejection_reason' => $request->reason,
                'rejection_date' => now(),
                'rejected_by' => $request->user()->id,
                'verified_at' => null,
                'verified_by' => null,
            ]);

            // TODO: Send rejection email notification to employer
            // Mail::to($employer->user->email)->send(new EmployerRejectedMail($employer, $request->reason));
        });

        return response()->json([
            'message' => 'Employer rejected.',
            'employer' => $employer->fresh(['user:id,name,email']),
        ]);
    }

    /**
     * Reset employer to pending status (for re-review).
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetToPending($id)
    {
        $employer = Employer::findOrFail($id);

        $employer->update([
            'is_verified' => false,
            'verification_status' => 'pending',
            'rejection_reason' => null,
            'rejection_date' => null,
            'rejected_by' => null,
            'verified_at' => null,
            'verified_by' => null,
        ]);

        return response()->json([
            'message' => 'Employer reset to pending status.',
            'employer' => $employer->fresh(),
        ]);
    }

    /**
     * Get verification statistics.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $stats = [
            'pending' => Employer::where('verification_status', 'pending')->count(),
            'verified' => Employer::where('verification_status', 'verified')->count(),
            'rejected' => Employer::where('verification_status', 'rejected')->count(),
            'total' => Employer::count(),
        ];

        // Recent pending (last 7 days)
        $stats['recent_pending'] = Employer::where('verification_status', 'pending')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        return response()->json($stats);
    }

    /**
     * Build safe document URL.
     * 
     * @param string|null $path
     * @return string|null
     */
    private function getSafeDocumentUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return config('app.url') . '/storage/' . $path;
    }
}
