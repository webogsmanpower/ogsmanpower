<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeekerDocumentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'document_type_display_name' => $this->document_type_display_name,
            'file_path' => $this->file_path,
            'public_url' => $this->public_url,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_size_human' => $this->getFileSizeHuman(),
            'verification_status' => $this->verification_status,
            'verification_status_display' => $this->getVerificationStatusDisplay(),
            'rejection_reason' => $this->rejection_reason,
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),
            'verified_by' => $this->when($this->verifier, [
                'id' => $this->verifier->id,
                'name' => $this->verifier->name,
                'email' => $this->verifier->email,
            ]),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Computed properties for UI
            'is_verified' => $this->isVerified(),
            'is_pending' => $this->isPending(),
            'is_rejected' => $this->isRejected(),
            'can_be_deleted' => $this->canBeDeleted(),
            'verification_badge' => $this->getVerificationBadge(),
        ];
    }

    /**
     * Get human-readable file size
     */
    private function getFileSizeHuman(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get verification status display text
     */
    private function getVerificationStatusDisplay(): string
    {
        return match($this->verification_status) {
            'pending' => 'Pending Verification',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * Get verification badge data for UI
     */
    private function getVerificationBadge(): array
    {
        return match($this->verification_status) {
            'pending' => [
                'text' => 'Pending',
                'color' => 'yellow',
                'bg_color' => 'bg-yellow-100',
                'text_color' => 'text-yellow-800',
                'icon' => 'clock',
            ],
            'verified' => [
                'text' => 'Verified',
                'color' => 'green',
                'bg_color' => 'bg-green-100',
                'text_color' => 'text-green-800',
                'icon' => 'check-circle',
            ],
            'rejected' => [
                'text' => 'Rejected',
                'color' => 'red',
                'bg_color' => 'bg-red-100',
                'text_color' => 'text-red-800',
                'icon' => 'x-circle',
            ],
            default => [
                'text' => 'Unknown',
                'color' => 'gray',
                'bg_color' => 'bg-gray-100',
                'text_color' => 'text-gray-800',
                'icon' => 'help-circle',
            ],
        };
    }

    /**
     * Check if document can be deleted
     */
    private function canBeDeleted(): bool
    {
        // Verified documents cannot be deleted (for audit trail)
        // Rejected documents can be re-uploaded, so they can be deleted
        return $this->verification_status !== 'verified';
    }
}
