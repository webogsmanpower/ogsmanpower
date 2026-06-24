<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Employer;
use App\Models\JobPosting;
use App\Models\Seeker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * AdminSearchController
 * 
 * Global search functionality for Admin Module.
 * Searches across Users, Employers, Jobs by ID/Name/Email.
 */
class AdminSearchController extends Controller
{
    /**
     * Global search across all entities.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all'); // all, users, employers, jobs
        $limit = min($request->get('limit', 20), 50);

        if (strlen($query) < 2) {
            return response()->json([
                'results' => [],
                'message' => 'Query must be at least 2 characters',
            ]);
        }

        $results = [];

        // Search Users (Seekers)
        if ($type === 'all' || $type === 'users' || $type === 'seekers') {
            $users = User::where('role', 'seeker')
                ->where(function ($q) use ($query) {
                    $q->where('id', $query)
                      ->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%");
                })
                ->with('seeker:id,user_id,first_name,last_name,profile_image_path')
                ->limit($limit)
                ->get()
                ->map(function ($user) {
                    return [
                        'type' => 'seeker',
                        'id' => $user->id,
                        'title' => $user->name,
                        'subtitle' => $user->email,
                        'avatar' => $user->seeker?->profile_image_path 
                            ? Storage::url($user->seeker->profile_image_path) 
                            : null,
                        'link' => "/admin/seekers/{$user->id}",
                        'meta' => [
                            'role' => $user->role,
                            'created_at' => $user->created_at->toIso8601String(),
                        ],
                    ];
                });

            $results = array_merge($results, $users->toArray());
        }

        // Search Employers
        if ($type === 'all' || $type === 'employers') {
            $employers = Employer::where(function ($q) use ($query) {
                    $q->where('id', $query)
                      ->orWhere('company_name', 'like', "%{$query}%")
                      ->orWhere('email', 'like', "%{$query}%")
                      ->orWhere('trade_license_number', 'like', "%{$query}%");
                })
                ->with('user:id,name,email')
                ->limit($limit)
                ->get()
                ->map(function ($employer) {
                    return [
                        'type' => 'employer',
                        'id' => $employer->id,
                        'title' => $employer->company_name,
                        'subtitle' => $employer->email ?? $employer->user?->email,
                        'avatar' => $employer->logo_path 
                            ? Storage::url($employer->logo_path) 
                            : null,
                        'link' => "/admin/employers/{$employer->id}",
                        'meta' => [
                            'verification_status' => $employer->verification_status,
                            'industry' => $employer->industry,
                            'created_at' => $employer->created_at->toIso8601String(),
                        ],
                    ];
                });

            $results = array_merge($results, $employers->toArray());
        }

        // Search Jobs
        if ($type === 'all' || $type === 'jobs') {
            $jobs = JobPosting::where(function ($q) use ($query) {
                    $q->where('id', $query)
                      ->orWhere('title', 'like', "%{$query}%")
                      ->orWhere('reference_number', 'like', "%{$query}%");
                })
                ->with('employer:id,company_name,logo_path')
                ->limit($limit)
                ->get()
                ->map(function ($job) {
                    return [
                        'type' => 'job',
                        'id' => $job->id,
                        'title' => $job->title,
                        'subtitle' => $job->employer?->company_name ?? 'Unknown Employer',
                        'avatar' => $job->employer?->logo_path 
                            ? Storage::url($job->employer->logo_path) 
                            : null,
                        'link' => "/admin/jobs/{$job->id}",
                        'meta' => [
                            'status' => $job->status,
                            'location' => $job->location,
                            'created_at' => $job->created_at->toIso8601String(),
                        ],
                    ];
                });

            $results = array_merge($results, $jobs->toArray());
        }

        // Sort by relevance (exact ID matches first)
        usort($results, function ($a, $b) use ($query) {
            $aExact = (string)$a['id'] === $query ? 0 : 1;
            $bExact = (string)$b['id'] === $query ? 0 : 1;
            return $aExact - $bExact;
        });

        return response()->json([
            'results' => array_slice($results, 0, $limit),
            'total' => count($results),
            'query' => $query,
            'type' => $type,
        ]);
    }

    /**
     * Quick search suggestions (for autocomplete).
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggestions(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = [];

        // Get top 5 matching users
        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'email', 'role']);

        foreach ($users as $user) {
            $suggestions[] = [
                'type' => $user->role,
                'id' => $user->id,
                'label' => $user->name,
                'sublabel' => $user->email,
            ];
        }

        // Get top 5 matching employers
        $employers = Employer::where('company_name', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'company_name', 'email']);

        foreach ($employers as $employer) {
            $suggestions[] = [
                'type' => 'employer',
                'id' => $employer->id,
                'label' => $employer->company_name,
                'sublabel' => $employer->email,
            ];
        }

        // Get top 5 matching jobs
        $jobs = JobPosting::where('title', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'title', 'status']);

        foreach ($jobs as $job) {
            $suggestions[] = [
                'type' => 'job',
                'id' => $job->id,
                'label' => $job->title,
                'sublabel' => "Status: {$job->status}",
            ];
        }

        return response()->json([
            'suggestions' => array_slice($suggestions, 0, 10),
        ]);
    }
}
