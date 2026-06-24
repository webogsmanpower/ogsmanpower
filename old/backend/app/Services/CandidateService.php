<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\JobApplication;
use App\Models\Seeker;
use App\Models\SeekerResume;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * CandidateService
 * 
 * Business logic for candidate browsing, searching, and manual creation by employers.
 * Provides access to all seekers with complete profiles for recruitment.
 */
class CandidateService
{
    /**
     * Get all candidates (seekers with complete profiles).
     * 
     * @param array $filters Filter options
     * @param int $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAllCandidates(array $filters = [], int $perPage = 15)
    {
        $query = Seeker::with(['user', 'resume'])
            ->whereHas('user', function ($q) {
                $q->where('role', 'seeker');
            })
            ->where('is_profile_complete', true);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('current_location', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($subQ) use ($search) {
                        $subQ->where('email', 'like', "%{$search}%");
                    });
            });
        }

        // Skills filter
        if (!empty($filters['skills'])) {
            $skills = is_array($filters['skills']) 
                ? $filters['skills'] 
                : explode(',', $filters['skills']);
            
            $query->where(function ($q) use ($skills) {
                foreach ($skills as $skill) {
                    $skill = trim($skill);
                    $q->orWhereJsonContains('skills', $skill);
                }
            });
        }

        // Location filter
        if (!empty($filters['location'])) {
            $query->where('current_location', 'like', "%{$filters['location']}%");
        }

        // Experience range filter
        if (!empty($filters['experience_min'])) {
            $query->where('experience_years', '>=', (int) $filters['experience_min']);
        }

        if (!empty($filters['experience_max'])) {
            $query->where('experience_years', '<=', (int) $filters['experience_max']);
        }

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }

    /**
     * Search candidates with advanced filters.
     * 
     * @param array $filters All filter options
     * @param int $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function searchCandidates(array $filters = [], int $perPage = 15)
    {
        $query = Seeker::with(['user', 'resume'])
            ->whereHas('user', function ($q) {
                $q->where('role', 'seeker');
            });

        // Basic search
        if (!empty($filters['q']) || !empty($filters['search'])) {
            $search = $filters['q'] ?? $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('headline', 'like', "%{$search}%")
                    ->orWhere('current_location', 'like', "%{$search}%")
                    ->orWhereHas('resume', function ($subQ) use ($search) {
                        $subQ->whereRaw("JSON_SEARCH(skills, 'one', ?) IS NOT NULL", ["%{$search}%"]);
                    });
            });
        }

        // Skills filter (array or comma-separated)
        if (!empty($filters['skills'])) {
            $skills = is_array($filters['skills']) 
                ? $filters['skills'] 
                : explode(',', $filters['skills']);
            
            $query->whereHas('resume', function ($q) use ($skills) {
                $q->where(function ($subQ) use ($skills) {
                    foreach ($skills as $skill) {
                        $skill = trim($skill);
                        $subQ->orWhereRaw("JSON_SEARCH(skills, 'one', ?) IS NOT NULL", ["%{$skill}%"]);
                    }
                });
            });
        }

        // Location filter
        if (!empty($filters['location'])) {
            $query->where('current_location', 'like', "%{$filters['location']}%");
        }

        // Country filter
        if (!empty($filters['country'])) {
            $query->whereHas('resume', function ($q) use ($filters) {
                $q->whereJsonContains('basic_information->country', $filters['country']);
            });
        }

        // Experience range
        if (!empty($filters['experience_min'])) {
            $query->where('experience_years', '>=', (int) $filters['experience_min']);
        }

        if (!empty($filters['experience_max'])) {
            $query->where('experience_years', '<=', (int) $filters['experience_max']);
        }

        // Profile completeness filter
        if (isset($filters['profile_complete'])) {
            $query->where('is_profile_complete', filter_var($filters['profile_complete'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }

    /**
     * Get candidates eligible for contract creation.
     * Returns seekers who have applied to this employer's jobs or been shortlisted.
     * 
     * @param Employer $employer
     * @return \Illuminate\Support\Collection
     */
    public function getCandidatesForContract(Employer $employer)
    {
        // Get all seekers who have applied to this employer's jobs
        $candidates = JobApplication::where('employer_id', $employer->id)
            ->whereIn('status', ['applied', 'shortlisted', 'interviewed', 'offered'])
            ->with(['seeker.user', 'seeker.resume', 'jobPosting'])
            ->get()
            ->map(function ($application) {
                $seeker = $application->seeker;
                return [
                    'id' => $seeker->id,
                    'application_id' => $application->id,
                    'job_posting_id' => $application->job_posting_id,
                    'job_title' => $application->jobPosting?->title,
                    'first_name' => $seeker->first_name,
                    'last_name' => $seeker->last_name,
                    'full_name' => trim($seeker->first_name . ' ' . $seeker->last_name),
                    'email' => $seeker->user?->email,
                    'phone' => $seeker->user?->mobile ?? $seeker->phone,
                    'status' => $application->status,
                    'applied_at' => $application->created_at?->toIso8601String(),
                ];
            })
            ->unique('id')
            ->values();

        return $candidates;
    }

    /**
     * Create a new candidate manually.
     */
    public function createCandidate(Employer $employer, User $createdBy, array $data): array
    {
        $basicInfo = $data['basic_information'];
        $email = $basicInfo['email'];

        // Check if user with this email already exists
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            // Check if they already have a seeker profile
            if ($existingUser->seeker) {
                // User exists with seeker profile - just create application if job provided
                $seeker = $existingUser->seeker;
                $application = null;

                if (!empty($data['job_posting_id'])) {
                    $application = $this->createApplicationForSeeker($employer, $seeker, $data['job_posting_id'], $createdBy);
                }

                return [
                    'success' => true,
                    'message' => 'Existing candidate linked',
                    'seeker' => $seeker,
                    'application' => $application,
                    'is_existing' => true,
                ];
            }
        }

        return DB::transaction(function () use ($employer, $createdBy, $data, $basicInfo, $email, $existingUser) {
            // Create user if doesn't exist
            if (!$existingUser) {
                $user = User::create([
                    'name' => trim($basicInfo['first_name'] . ' ' . $basicInfo['last_name']),
                    'email' => $email,
                    'password' => Hash::make(Str::random(16)), // Random password - user can reset
                    'mobile' => $basicInfo['phone'] ?? null,
                    'role' => 'seeker',
                ]);
            } else {
                $user = $existingUser;
            }

            // Create seeker profile
            $seeker = Seeker::create([
                'user_id' => $user->id,
                'first_name' => $basicInfo['first_name'],
                'last_name' => $basicInfo['last_name'],
                'date_of_birth' => $basicInfo['date_of_birth'] ?? null,
                'current_location' => $basicInfo['city'] ?? null,
                'is_profile_complete' => false,
            ]);

            // Create seeker resume with all sections
            $resumeData = [
                'user_id' => $user->id,
                'seeker_id' => $seeker->id,
                'basic_information' => $this->formatBasicInformation($basicInfo),
                'documents' => $data['documents'] ?? null,
                'professional_summary' => $data['professional_summary'] ?? null,
                'work_experience' => $data['work_experience'] ?? null,
                'education' => $data['education'] ?? null,
                'skills' => $data['skills'] ?? null,
                'languages' => $data['languages'] ?? null,
                'certifications' => $data['certifications'] ?? null,
                'references' => $data['references'] ?? null,
                'job_preferences' => $data['job_preferences'] ?? null,
                'availability' => $data['availability'] ?? null,
            ];

            $resume = SeekerResume::create($resumeData);
            $resume->calculateProfileCompletion();

            // Create application if job provided
            $application = null;
            if (!empty($data['job_posting_id'])) {
                $application = $this->createApplicationForSeeker($employer, $seeker, $data['job_posting_id'], $createdBy);
            }

            return [
                'success' => true,
                'message' => 'Candidate created successfully',
                'seeker' => $seeker->fresh(['resume', 'user']),
                'application' => $application,
                'is_existing' => false,
            ];
        });
    }

    /**
     * Update an existing candidate.
     */
    public function updateCandidate(Employer $employer, int $seekerId, array $data): array
    {
        // Verify employer has access to this candidate (through an application)
        $hasAccess = JobApplication::where('employer_id', $employer->id)
            ->where('seeker_id', $seekerId)
            ->exists();

        if (!$hasAccess) {
            return [
                'success' => false,
                'message' => 'Candidate not found or access denied',
                'code' => 404,
            ];
        }

        $seeker = Seeker::find($seekerId);
        if (!$seeker) {
            return [
                'success' => false,
                'message' => 'Candidate not found',
                'code' => 404,
            ];
        }

        // Update seeker basic info
        if (!empty($data['basic_information'])) {
            $basicInfo = $data['basic_information'];
            $seeker->update([
                'first_name' => $basicInfo['first_name'] ?? $seeker->first_name,
                'last_name' => $basicInfo['last_name'] ?? $seeker->last_name,
                'date_of_birth' => $basicInfo['date_of_birth'] ?? $seeker->date_of_birth,
                'current_location' => $basicInfo['city'] ?? $seeker->current_location,
            ]);
        }

        // Update resume
        $resume = $seeker->resume;
        if ($resume) {
            $updateData = [];

            if (!empty($data['basic_information'])) {
                $updateData['basic_information'] = $this->formatBasicInformation($data['basic_information']);
            }

            $sections = ['documents', 'professional_summary', 'work_experience', 'education', 
                        'skills', 'languages', 'certifications', 'references', 
                        'job_preferences', 'availability'];

            foreach ($sections as $section) {
                if (isset($data[$section])) {
                    $updateData[$section] = $data[$section];
                }
            }

            if (!empty($updateData)) {
                $resume->update($updateData);
                $resume->calculateProfileCompletion();
            }
        }

        return [
            'success' => true,
            'message' => 'Candidate updated successfully',
            'seeker' => $seeker->fresh(['resume', 'user']),
        ];
    }

    /**
     * Get a candidate for an employer.
     * Employers can view any candidate who has a complete profile.
     */
    public function getCandidateForEmployer(Employer $employer, int $seekerId): ?Seeker
    {
        return Seeker::with(['resume', 'user'])
            ->with(['applications' => function ($query) use ($employer) {
                $query->where('employer_id', $employer->id)
                      ->with('contract')
                      ->latest()
                      ->limit(1);
            }])
            ->where('is_profile_complete', true)
            ->find($seekerId);
    }

    /**
     * Create application for a seeker.
     */
    protected function createApplicationForSeeker(Employer $employer, Seeker $seeker, int $jobPostingId, User $createdBy): ?JobApplication
    {
        // Check if job belongs to employer
        $job = $employer->jobPostings()->find($jobPostingId);
        if (!$job) {
            return null;
        }

        // Check if application already exists
        $existingApplication = JobApplication::where('job_posting_id', $jobPostingId)
            ->where('seeker_id', $seeker->id)
            ->first();

        if ($existingApplication) {
            return $existingApplication;
        }

        // Create resume snapshot
        $resumeSnapshot = $seeker->resume ? $seeker->resume->toArray() : null;

        $application = JobApplication::create([
            'job_posting_id' => $jobPostingId,
            'seeker_id' => $seeker->id,
            'employer_id' => $employer->id,
            'resume_snapshot' => $resumeSnapshot,
            'status' => 'applied',
            'source' => 'imported',
            'status_changed_at' => now(),
            'status_changed_by' => $createdBy->id,
        ]);

        // Increment job application count
        $job->incrementApplications();

        return $application;
    }

    /**
     * Get seeker profile for a user.
     */
    public function getSeekerForUser(User $user): ?Seeker
    {
        if ($user->role !== 'seeker') {
            return null;
        }

        return $user->seeker;
    }

    /**
     * Format basic information for storage.
     */
    protected function formatBasicInformation(array $basicInfo): array
    {
        return [
            'first_name' => $basicInfo['first_name'] ?? null,
            'last_name' => $basicInfo['last_name'] ?? null,
            'full_name' => trim(($basicInfo['first_name'] ?? '') . ' ' . ($basicInfo['last_name'] ?? '')),
            'email' => $basicInfo['email'] ?? null,
            'phone' => $basicInfo['phone'] ?? null,
            'date_of_birth' => $basicInfo['date_of_birth'] ?? null,
            'father_name' => $basicInfo['father_name'] ?? null,
            'mother_name' => $basicInfo['mother_name'] ?? null,
            'marital_status' => $basicInfo['marital_status'] ?? null,
            'nationality' => $basicInfo['nationality'] ?? null,
            'whatsapp_number' => $basicInfo['whatsapp_number'] ?? null,
            'emergency_contact_name' => $basicInfo['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $basicInfo['emergency_contact_phone'] ?? null,
            'address' => $basicInfo['address'] ?? null,
            'city' => $basicInfo['city'] ?? null,
            'state_province' => $basicInfo['state_province'] ?? null,
            'country' => $basicInfo['country'] ?? null,
            'zip_code' => $basicInfo['zip_code'] ?? null,
        ];
    }
}
