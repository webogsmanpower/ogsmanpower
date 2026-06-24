<?php

namespace App\Services;

use App\Models\Employer;
use App\Models\VisaStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * VisaService
 * 
 * Business logic for visa status management.
 */
class VisaService
{
    /**
     * Get visa statuses for an employer with filters.
     */
    public function getVisaStatusesForEmployer(Employer $employer, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $employer->visaStatuses()->with([
            'seeker.user', 
            'contract', 
            'steps.uploadedDocuments',
            'processSteps.documents'
        ]);

        if (!empty($filters['current_step'])) {
            $query->where('current_step', $filters['current_step']);
        }

        if (!empty($filters['destination_country'])) {
            $query->where('destination_country', $filters['destination_country']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get a visa status by ID for an employer.
     */
    public function getVisaStatusById(Employer $employer, int $id): ?VisaStatus
    {
        return $employer->visaStatuses()
            ->with(['seeker.user', 'contract', 'steps.uploadedDocuments', 'processSteps.documents'])
            ->find($id);
    }

    /**
     * Update visa status details.
     */
    public function updateVisaStatus(VisaStatus $visaStatus, array $data, ?User $updatedBy = null): VisaStatus
    {
        if ($updatedBy) {
            $data['last_updated_by'] = $updatedBy->id;
        }

        $visaStatus->update($data);
        return $visaStatus->fresh();
    }

    /**
     * Get visa statistics for employer.
     */
    public function getVisaStats(Employer $employer): array
    {
        $steps = VisaStatus::STEPS;
        $stats = [];

        foreach ($steps as $step) {
            $stats[$step] = $employer->visaStatuses()->where('current_step', $step)->count();
        }

        $stats['total'] = array_sum($stats);
        $stats['active'] = $stats['total'] - ($stats['completed'] ?? 0) - ($stats['visa_rejected'] ?? 0);

        return $stats;
    }
}
