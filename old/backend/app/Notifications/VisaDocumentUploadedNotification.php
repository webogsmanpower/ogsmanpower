<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class VisaDocumentUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public $visaStatus,
        public $visaStep,
        public $document,
        public $seeker
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'visa_document_uploaded',
            'title' => 'Document Uploaded',
            'message' => $this->seeker->user->name . ' uploaded a document for ' . $this->visaStep->label,
            'visa_status_id' => $this->visaStatus->id,
            'visa_step_id' => $this->visaStep->id,
            'document_id' => $this->document->id,
            'document_filename' => $this->document->filename,
            'seeker_name' => $this->seeker->user->name,
            'action_url' => '/employer/visa/' . $this->visaStatus->id,
        ];
    }
}
