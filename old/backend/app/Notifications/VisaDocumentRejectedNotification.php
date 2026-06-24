<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class VisaDocumentRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $visaStatus,
        public $visaStep,
        public $document,
        public $employer,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'visa_document_rejected',
            'title' => 'Document Rejected',
            'message' => 'Your document was rejected: ' . $this->reason,
            'visa_status_id' => $this->visaStatus->id,
            'visa_step_id' => $this->visaStep->id,
            'document_id' => $this->document->id,
            'document_filename' => $this->document->filename,
            'rejection_reason' => $this->reason,
            'employer_name' => $this->employer->company_name,
            'action_url' => '/seeker/visa-status',
        ];
    }
}
