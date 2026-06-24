<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\ContractMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * SeekerContractController
 * 
 * Handles contract viewing, signing, and messaging for seekers.
 */
class SeekerContractController extends Controller
{
    /**
     * List all contracts for the seeker.
     */
    public function index(Request $request): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $contracts = Contract::where('seeker_id', $seeker->id)
            ->whereIn('status', ['sent', 'viewed', 'signed', 'rejected'])
            ->with(['employer', 'job'])
            ->orderBy('sent_at', 'desc')
            ->get();

        return response()->json([
            'data' => ContractResource::collection($contracts),
        ]);
    }

    /**
     * Get a specific contract.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $contract = Contract::where('seeker_id', $seeker->id)
            ->with(['employer', 'job', 'template', 'template.employer', 'seeker.user', 'messages.sender'])
            ->findOrFail($id);

        // Mark as viewed if first time viewing
        if ($contract->status === 'sent') {
            $contract->markViewed();
        }

        return response()->json([
            'data' => new ContractResource($contract),
            'content' => [
                'html_content' => $contract->content,
                'attachment_url' => $contract->attachment_path 
                    ? Storage::disk('public')->url($contract->attachment_path) 
                    : null,
                'template' => $contract->template ? [
                    'header_image_url' => $contract->template->header_image_path 
                        ? Storage::disk('public')->url($contract->template->header_image_path) 
                        : null,
                    'footer_text' => $contract->template->footer_text,
                    'content' => $contract->template->content,
                ] : null,
            ],
        ]);
    }

    /**
     * Sign a contract digitally.
     */
    public function sign(Request $request, int $id): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $contract = Contract::where('seeker_id', $seeker->id)->findOrFail($id);

        if (!in_array($contract->status, ['sent', 'viewed'])) {
            return response()->json(['message' => 'Contract cannot be signed in current status'], 422);
        }

        if ($contract->isExpired()) {
            return response()->json(['message' => 'Contract has expired'], 422);
        }

        $validator = Validator::make($request->all(), [
            'initials' => 'required|string|min:2|max:10',
            'terms_accepted' => 'required|boolean|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Capture signature metadata
        $signatureMetadata = [
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
            'initials' => $request->input('initials'),
            'user_agent' => $request->userAgent(),
            'method' => 'digital_acknowledgement',
        ];

        $contract->update([
            'status' => 'signed',
            'signed_at' => now(),
            'seeker_initials' => $request->input('initials'),
            'terms_accepted' => true,
            'seeker_ip_address' => $request->ip(),
            'seeker_user_agent' => $request->userAgent(),
            'signature_metadata' => $signatureMetadata,
        ]);

        return response()->json([
            'message' => 'Contract signed successfully',
            'data' => new ContractResource($contract->fresh()),
        ]);
    }

    /**
     * Upload signed PDF copy.
     */
    public function uploadSignedCopy(Request $request, int $id): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $contract = Contract::where('seeker_id', $seeker->id)->findOrFail($id);

        if (!in_array($contract->status, ['sent', 'viewed'])) {
            return response()->json(['message' => 'Contract cannot be signed in current status'], 422);
        }

        if ($contract->isExpired()) {
            return response()->json(['message' => 'Contract has expired'], 422);
        }

        $validator = Validator::make($request->all(), [
            'signed_pdf' => 'required|file|mimes:pdf|max:10240',
            'terms_accepted' => 'required|boolean|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Upload signed PDF
        $path = $request->file('signed_pdf')->store('contracts/signed', 'public');

        // Capture signature metadata
        $signatureMetadata = [
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
            'user_agent' => $request->userAgent(),
            'method' => 'uploaded_signed_copy',
            'file_path' => $path,
        ];

        $contract->update([
            'status' => 'signed',
            'signed_at' => now(),
            'signed_pdf_path' => $path,
            'terms_accepted' => true,
            'seeker_ip_address' => $request->ip(),
            'seeker_user_agent' => $request->userAgent(),
            'signature_metadata' => $signatureMetadata,
        ]);

        return response()->json([
            'message' => 'Signed contract uploaded successfully',
            'data' => new ContractResource($contract->fresh()),
        ]);
    }

    /**
     * Reject a contract.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $contract = Contract::where('seeker_id', $seeker->id)->findOrFail($id);

        if (!in_array($contract->status, ['sent', 'viewed'])) {
            return response()->json(['message' => 'Contract cannot be rejected in current status'], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $contract->reject($request->input('reason'));

        return response()->json([
            'message' => 'Contract rejected',
            'data' => new ContractResource($contract->fresh()),
        ]);
    }

    /**
     * Download contract PDF.
     */
    public function download(Request $request, int $id)
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $contract = Contract::where('seeker_id', $seeker->id)
            ->with(['employer', 'template'])
            ->findOrFail($id);

        // If there's an attachment PDF, return that
        if ($contract->attachment_path && Storage::disk('public')->exists($contract->attachment_path)) {
            return Storage::disk('public')->download(
                $contract->attachment_path,
                "contract-{$contract->contract_number}.pdf"
            );
        }

        // If there's a signed PDF, return that
        if ($contract->signed_pdf_path && Storage::disk('public')->exists($contract->signed_pdf_path)) {
            return Storage::disk('public')->download(
                $contract->signed_pdf_path,
                "contract-{$contract->contract_number}-signed.pdf"
            );
        }

        // Generate PDF from HTML content using DomPDF
        if (!$contract->html_content) {
            return response()->json(['message' => 'No contract content available'], 404);
        }

        try {
            // Build complete HTML document with styling
            $html = $this->buildContractPdfHtml($contract);
            
            // Generate PDF using DomPDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            
            $filename = "contract-{$contract->contract_number}.pdf";
            
            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Contract PDF generation failed', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Failed to generate PDF',
                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
            ], 500);
        }
    }

    /**
     * Build complete HTML document for contract PDF.
     */
    private function buildContractPdfHtml(Contract $contract): string
    {
        $headerLogo = '';
        if ($contract->header_logo_path && Storage::disk('public')->exists($contract->header_logo_path)) {
            $logoPath = Storage::disk('public')->path($contract->header_logo_path);
            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoMime = mime_content_type($logoPath);
                $headerLogo = "<img src=\"data:{$logoMime};base64,{$logoData}\" style=\"max-height: 60px; max-width: 200px;\" />";
            }
        }

        $companyName = $contract->employer?->company_name ?? 'Company';
        $companyAddress = $contract->company_address ?? $contract->employer?->address ?? '';
        $companyPhone = $contract->company_phone ?? '';
        $companyEmail = $contract->company_email ?? '';

        $signatorySection = '';
        if ($contract->status === 'signed') {
            $signatorySection = "
                <div style=\"margin-top: 40px; border-top: 1px solid #e5e7eb; padding-top: 20px;\">
                    <h3 style=\"font-size: 14px; margin-bottom: 15px;\">Signatures</h3>
                    <div style=\"display: flex; justify-content: space-between;\">
                        <div style=\"width: 45%;\">
                            <p style=\"font-weight: 600;\">Employer Representative</p>
                            <p>{$contract->signatory_name}</p>
                            <p style=\"font-size: 12px; color: #6b7280;\">{$contract->signatory_title}</p>
                        </div>
                        <div style=\"width: 45%;\">
                            <p style=\"font-weight: 600;\">Employee</p>
                            <p>Signed digitally: {$contract->seeker_initials}</p>
                            <p style=\"font-size: 12px; color: #6b7280;\">Date: " . ($contract->signed_at ? $contract->signed_at->format('F j, Y') : '') . "</p>
                        </div>
                    </div>
                </div>
            ";
        }

        return "
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset=\"UTF-8\">
                <title>Employment Contract - {$contract->contract_number}</title>
                <style>
                    body {
                        font-family: 'DejaVu Sans', Arial, sans-serif;
                        font-size: 12px;
                        line-height: 1.6;
                        color: #1f2937;
                        margin: 0;
                        padding: 40px;
                    }
                    .header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-bottom: 2px solid #4f46e5;
                        padding-bottom: 20px;
                        margin-bottom: 30px;
                    }
                    .company-info {
                        text-align: right;
                        font-size: 11px;
                        color: #6b7280;
                    }
                    .contract-title {
                        font-size: 24px;
                        font-weight: bold;
                        color: #1f2937;
                        text-align: center;
                        margin-bottom: 30px;
                    }
                    .contract-number {
                        font-size: 11px;
                        color: #6b7280;
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .content {
                        margin-bottom: 30px;
                    }
                    .content h2 {
                        font-size: 14px;
                        color: #4f46e5;
                        margin-top: 20px;
                        margin-bottom: 10px;
                    }
                    .content p {
                        margin-bottom: 10px;
                    }
                    .footer {
                        margin-top: 40px;
                        padding-top: 20px;
                        border-top: 1px solid #e5e7eb;
                        font-size: 10px;
                        color: #9ca3af;
                        text-align: center;
                    }
                </style>
            </head>
            <body>
                <div class=\"header\">
                    <div>{$headerLogo}</div>
                    <div class=\"company-info\">
                        <strong>{$companyName}</strong><br>
                        {$companyAddress}<br>
                        {$companyPhone}<br>
                        {$companyEmail}
                    </div>
                </div>
                
                <div class=\"contract-title\">EMPLOYMENT CONTRACT</div>
                <div class=\"contract-number\">Contract No: {$contract->contract_number}</div>
                
                <div class=\"content\">
                    {$contract->html_content}
                </div>
                
                {$signatorySection}
                
                <div class=\"footer\">
                    This document was generated on " . now()->format('F j, Y') . " | Contract ID: {$contract->id}
                </div>
            </body>
            </html>
        ";
    }

    /**
     * Get contract messages.
     */
    public function getMessages(Request $request, int $id): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $contract = Contract::where('seeker_id', $seeker->id)->findOrFail($id);

        $messages = $contract->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) use ($request) {
                // Mark as read if not sender
                if ($message->sender_id !== $request->user()->id && !$message->read_at) {
                    $message->markAsRead();
                }

                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                    ],
                    'is_mine' => $message->sender_id === $request->user()->id,
                    'read_at' => $message->read_at?->toIso8601String(),
                    'created_at' => $message->created_at->toIso8601String(),
                ];
            });

        return response()->json(['data' => $messages]);
    }

    /**
     * Send a message about the contract.
     */
    public function sendMessage(Request $request, int $id): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $contract = Contract::where('seeker_id', $seeker->id)->findOrFail($id);

        // Only allow messaging for active contracts
        if (!in_array($contract->status, ['sent', 'viewed', 'signed'])) {
            return response()->json(['message' => 'Cannot send messages for this contract'], 422);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $message = ContractMessage::create([
            'contract_id' => $contract->id,
            'sender_id' => $request->user()->id,
            'content' => $request->input('content'),
        ]);

        return response()->json([
            'message' => 'Message sent successfully',
            'data' => [
                'id' => $message->id,
                'content' => $message->content,
                'sender' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                ],
                'is_mine' => true,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Get unread contract count for badge notification.
     */
    public function getUnreadCount(Request $request): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['count' => 0]);
        }

        // Count contracts that are sent but not yet viewed/signed
        $count = Contract::where('seeker_id', $seeker->id)
            ->whereIn('status', ['sent', 'viewed'])
            ->where(function ($query) {
                $query->whereNull('viewed_at')
                      ->orWhere('status', 'sent');
            })
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Debug endpoint to check contract content.
     */
    public function debugContract(Request $request, int $id): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $contract = Contract::where('seeker_id', $seeker->id)
            ->with(['employer', 'template', 'seeker.user'])
            ->findOrFail($id);

        return response()->json([
            'debug' => [
                'contract_id' => $contract->id,
                'html_content' => [
                    'exists' => !empty($contract->html_content),
                    'length' => strlen($contract->html_content ?? ''),
                    'preview' => substr($contract->html_content ?? '', 0, 200),
                ],
                'template' => $contract->template ? [
                    'id' => $contract->template->id,
                    'name' => $contract->template->name,
                    'has_content' => !empty($contract->template->content),
                    'content_length' => strlen($contract->template->content ?? ''),
                    'content_preview' => substr($contract->template->content ?? '', 0, 200),
                ] : null,
                'terms' => [
                    'exists' => !empty($contract->terms),
                    'length' => strlen($contract->terms ?? ''),
                    'preview' => substr($contract->terms ?? '', 0, 200),
                ],
                'content_method' => [
                    'result' => $contract->content,
                    'length' => strlen($contract->content ?? ''),
                    'preview' => substr($contract->content ?? '', 0, 200),
                ]
            ]
        ]);
    }

    /**
     * Upload document for visa step.
     */
    public function uploadVisaDocument(Request $request, int $visaStatusId, int $stepId): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        // Verify this visa status belongs to the seeker
        $visaStatus = \App\Models\VisaStatus::where('id', $visaStatusId)
            ->where('seeker_id', $seeker->id)
            ->firstOrFail();

        // Verify the step belongs to this visa status
        $visaStep = \App\Models\VisaStep::where('id', $stepId)
            ->where('visa_status_id', $visaStatusId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max for performance
            'requirement_name' => 'nullable|string|max:255', // Optional - will use filename if not provided
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Upload the document
        $file = $request->file('document');
        $requirementName = $request->input('requirement_name');
        
        // Generate requirement_name from filename if not provided
        if (!$requirementName) {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $requirementName = strtolower(str_replace([' ', '-'], '_', $originalName));
        }
        
        $path = $file->store("visa-documents/{$visaStatusId}", 'public');
        
        // Find existing document to delete old file
        $existingDoc = \App\Models\VisaStepDocument::where('visa_step_id', $visaStep->id)
            ->where('seeker_id', $seeker->id)
            ->where('requirement_name', $requirementName)
            ->first();
        
        // Delete old file if exists
        if ($existingDoc && $existingDoc->path && Storage::disk('public')->exists($existingDoc->path)) {
            Storage::disk('public')->delete($existingDoc->path);
        }
        
        // Use updateOrCreate for atomic operation (prevents race conditions)
        $document = \App\Models\VisaStepDocument::updateOrCreate(
            [
                'visa_step_id' => $visaStep->id,
                'seeker_id' => $seeker->id,
                'requirement_name' => $requirementName,
            ],
            [
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'status' => 'uploaded',
                'rejection_reason' => null,
            ]
        );
        
        \Log::info('Document created', [
            'document_id' => $document->id,
            'visa_step_id' => $visaStep->id,
            'seeker_id' => $document->seeker_id,
            'requirement_name' => $document->requirement_name,
            'filename' => $document->filename
        ]);
        
        // If step was pending, mark it as in_progress
        if ($visaStep->status === 'pending') {
            $visaStep->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        // Notify employer about new document
        if ($visaStatus->employer && $visaStatus->employer->user) {
            $visaStatus->employer->user->notify(
                new \App\Notifications\VisaDocumentUploadedNotification(
                    $visaStatus,
                    $visaStep,
                    $document,
                    $seeker
                )
            );
        }

        // Reload step with ALL documents for frontend cache update
        \Log::info('Loading documents for step', [
            'step_id' => $visaStep->id,
            'seeker_id' => $seeker->id,
            'visa_status_id' => $visaStatusId
        ]);

        // Get documents directly for this seeker
        $documents = \App\Models\VisaStepDocument::where('visa_step_id', $visaStep->id)
            ->where('seeker_id', $seeker->id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        \Log::info('Documents loaded for step', [
            'step_id' => $visaStep->id,
            'seeker_id' => $seeker->id,
            'documents_count' => $documents->count(),
            'documents' => $documents->toArray()
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'data' => [
                'document' => [
                    'id' => $document->id,
                    'filename' => $document->filename,
                    'requirement_name' => $document->requirement_name,
                    'status' => $document->status,
                    'rejection_reason' => $document->rejection_reason,
                    'url' => $document->getUrl(),
                    'uploaded_at' => $document->created_at->toIso8601String(),
                ],
                'step' => [
                    'id' => $visaStep->id,
                    'step_name' => $visaStep->step_name,
                    'step_order' => $visaStep->step_order,
                    'status' => $visaStep->status,
                    'started_at' => $visaStep->started_at?->toIso8601String(),
                    'completed_at' => $visaStep->completed_at?->toIso8601String(),
                    'documents' => $documents->map(function($doc) {
                        return [
                            'id' => $doc->id,
                            'filename' => $doc->filename,
                            'requirement_name' => $doc->requirement_name,
                            'status' => $doc->status,
                            'rejection_reason' => $doc->rejection_reason,
                            'url' => $doc->getUrl(),
                            'uploaded_at' => $doc->created_at->toIso8601String(),
                        ];
                    })->toArray(),
                ],
            ],
        ]);
    }

    /**
     * Upload document for a custom process step.
     */
    public function uploadVisaProcessDocument(Request $request, int $visaStatusId, int $processStepId): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $visaStatus = \App\Models\VisaStatus::where('id', $visaStatusId)
            ->where('seeker_id', $seeker->id)
            ->firstOrFail();

        $processStep = \App\Models\VisaProcessStep::where('id', $processStepId)
            ->where('visa_status_id', $visaStatusId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'requirement_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $file = $request->file('document');
        $requirementName = $request->input('requirement_name') ?? $processStep->name;
        $path = $file->store("visa-documents/{$visaStatusId}", 'public');

        // Find existing document to delete old file
        $existingDoc = \App\Models\VisaStepDocument::where('visa_process_step_id', $processStep->id)
            ->where('seeker_id', $seeker->id)
            ->first();
        
        // Delete old file if exists
        if ($existingDoc && $existingDoc->path && Storage::disk('public')->exists($existingDoc->path)) {
            Storage::disk('public')->delete($existingDoc->path);
        }
        
        // Use updateOrCreate for atomic operation (one document per process step per seeker)
        $document = \App\Models\VisaStepDocument::updateOrCreate(
            [
                'visa_process_step_id' => $processStep->id,
                'seeker_id' => $seeker->id,
            ],
            [
                'visa_step_id' => null,
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'requirement_name' => $requirementName,
                'status' => 'uploaded',
                'rejection_reason' => null,
            ]
        );

        if ($processStep->status === 'pending') {
            $processStep->update([
                'status' => 'in_progress',
            ]);
        }

        if ($visaStatus->employer && $visaStatus->employer->user) {
            $visaStatus->employer->user->notify(
                new \App\Notifications\VisaDocumentUploadedNotification(
                    $visaStatus,
                    $processStep,
                    $document,
                    $seeker
                )
            );
        }

        // Get documents directly for this seeker
        $documents = \App\Models\VisaStepDocument::where('visa_process_step_id', $processStep->id)
            ->where('seeker_id', $seeker->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => 'Document uploaded successfully',
            'data' => [
                'document' => [
                    'id' => $document->id,
                    'filename' => $document->filename,
                    'requirement_name' => $document->requirement_name,
                    'status' => $document->status,
                    'rejection_reason' => $document->rejection_reason,
                    'url' => $document->getUrl(),
                    'uploaded_at' => $document->created_at->toIso8601String(),
                ],
                'processStep' => [
                    'id' => $processStep->id,
                    'name' => $processStep->name,
                    'label' => $processStep->label,
                    'status' => $processStep->status,
                    'is_custom' => $processStep->is_custom,
                    'created_at' => $processStep->created_at->toIso8601String(),
                    'documents' => $documents->map(function($doc) {
                        return [
                            'id' => $doc->id,
                            'filename' => $doc->filename,
                            'requirement_name' => $doc->requirement_name,
                            'status' => $doc->status,
                            'rejection_reason' => $doc->rejection_reason,
                            'url' => $doc->getUrl(),
                            'uploaded_at' => $doc->created_at->toIso8601String(),
                        ];
                    })->toArray(),
                ],
            ],
        ]);
    }

    /**
     * Remove a document uploaded for a visa step.
     */
    public function deleteVisaDocument(Request $request, int $visaStatusId, int $stepId, int $documentId): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $visaStatus = \App\Models\VisaStatus::where('id', $visaStatusId)
            ->where('seeker_id', $seeker->id)
            ->firstOrFail();

        $visaStep = \App\Models\VisaStep::where('id', $stepId)
            ->where('visa_status_id', $visaStatusId)
            ->firstOrFail();

        \Log::info('Delete attempt', [
            'documentId' => $documentId,
            'stepId' => $stepId,
            'seekerId' => $seeker->id,
            'visaStatusId' => $visaStatusId
        ]);

        $document = \App\Models\VisaStepDocument::where('id', $documentId)
            ->where('visa_step_id', $stepId)
            ->where('seeker_id', $seeker->id)
            ->firstOrFail();

        if ($document->status === 'verified') {
            return response()->json(['message' => 'Verified documents cannot be removed'], 422);
        }

        if ($document->path && Storage::disk('public')->exists($document->path)) {
            Storage::disk('public')->delete($document->path);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document removed successfully',
        ]);
    }

    /**
     * Remove a document uploaded for a custom process step.
     */
    public function deleteVisaProcessDocument(Request $request, int $visaStatusId, int $processStepId, int $documentId): JsonResponse
    {
        $seeker = $request->user()->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $visaStatus = \App\Models\VisaStatus::where('id', $visaStatusId)
            ->where('seeker_id', $seeker->id)
            ->firstOrFail();

        $processStep = \App\Models\VisaProcessStep::where('id', $processStepId)
            ->where('visa_status_id', $visaStatusId)
            ->firstOrFail();

        $document = \App\Models\VisaStepDocument::where('id', $documentId)
            ->where('visa_process_step_id', $processStepId)
            ->where('seeker_id', $seeker->id)
            ->firstOrFail();

        if ($document->status === 'verified') {
            return response()->json(['message' => 'Verified documents cannot be removed'], 422);
        }

        if ($document->path && Storage::disk('public')->exists($document->path)) {
            Storage::disk('public')->delete($document->path);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document removed successfully',
        ]);
    }

    /**
     * Get seeker's visa status.
     */
    public function getVisaStatus(Request $request): JsonResponse
    {
        try {
            $seeker = $request->user()->seeker;

            if (!$seeker) {
                return response()->json(['message' => 'Seeker profile not found'], 404);
            }

            // Get visa statuses for this seeker - OPTIMIZED WITH EAGER LOADING
            $visaStatuses = \App\Models\VisaStatus::where('seeker_id', $seeker->id)
                ->select(['id', 'visa_type', 'destination_country', 'current_step', 'documents_required', 'contract_id', 'employer_id', 'created_at', 'updated_at'])
                ->with(['employer:id,company_name,logo_path'])
                ->with(['contract:id,contract_number,status'])
                ->with(['steps' => function($query) {
                    $query->select('id', 'visa_status_id', 'step_name', 'step_order', 'status', 'started_at', 'completed_at')
                          ->orderBy('step_order');
                }])
                ->with(['processSteps' => function($query) {
                    $query->select('id', 'visa_status_id', 'name', 'label', 'status', 'is_custom', 'created_at')
                          ->orderBy('created_at');
                }])
                ->orderBy('created_at', 'desc')
                ->limit(3) // Reduced limit to prevent timeout
                ->get();

            // Get all documents for this seeker in one query to avoid N+1
            $allDocuments = \App\Models\VisaStepDocument::where('seeker_id', $seeker->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Group documents by step_id and process_step_id
            $stepDocumentsMap = [];
            $processDocumentsMap = [];
            
            foreach ($allDocuments as $doc) {
                if ($doc->visa_step_id) {
                    $stepDocumentsMap[$doc->visa_step_id][] = $doc;
                }
                if ($doc->visa_process_step_id) {
                    $processDocumentsMap[$doc->visa_process_step_id][] = $doc;
                }
            }

            // Assign documents to steps (no additional queries)
            foreach ($visaStatuses as $visaStatus) {
                foreach ($visaStatus->steps as $step) {
                    $step->documents = collect($stepDocumentsMap[$step->id] ?? []);
                }
                
                foreach ($visaStatus->processSteps as $processStep) {
                    $processStep->documents = collect($processDocumentsMap[$processStep->id] ?? []);
                }
            }

            return response()->json([
                'data' => \App\Http\Resources\VisaStatusResource::collection($visaStatuses),
            ]);

        } catch (\Exception $e) {
            // Log the actual error for debugging
            \Log::error('Visa Status API Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Internal server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
