<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeekerDocumentResource;
use App\Models\SeekerDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AdminDocumentVerificationController extends Controller
{
    /**
     * Get all documents pending verification
     */
    public function pending(Request $request): JsonResponse
    {
        $documents = SeekerDocument::with(['user:id,name,email', 'verifier:id,name'])
            ->pending()
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json([
            'data' => SeekerDocumentResource::collection($documents->items()),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ]
        ]);
    }

    /**
     * Get documents by status
     */
    public function byStatus(Request $request, string $status): JsonResponse
    {
        if (!in_array($status, ['pending', 'verified', 'rejected'])) {
            return response()->json(['message' => 'Invalid status'], 422);
        }

        $documents = SeekerDocument::with(['user:id,name,email', 'verifier:id,name'])
            ->byStatus($status)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => SeekerDocumentResource::collection($documents->items()),
            'pagination' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ]
        ]);
    }

    /**
     * Get verification statistics
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_documents' => SeekerDocument::count(),
            'pending' => SeekerDocument::pending()->count(),
            'verified' => SeekerDocument::verified()->count(),
            'rejected' => SeekerDocument::rejected()->count(),
            'by_type' => SeekerDocument::selectRaw('document_type, COUNT(*) as count')
                ->groupBy('document_type')
                ->pluck('count', 'document_type')
                ->toArray(),
            'recent_verifications' => SeekerDocument::with('verifier:id,name')
                ->whereNotNull('verified_at')
                ->orderBy('verified_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'document_type' => $doc->document_type_display_name,
                        'user_name' => $doc->user->name,
                        'verifier_name' => $doc->verifier->name,
                        'verified_at' => $doc->verified_at->format('Y-m-d H:i:s'),
                    ];
                }),
        ];

        return response()->json($stats);
    }

    /**
     * Verify a document
     */
    public function verify(Request $request, SeekerDocument $document): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $admin = Auth::user();
        
        // Check if admin has permission to verify documents
        if (!$admin->hasRole('admin') && !$admin->hasPermission('verify_documents')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($document->verification_status !== SeekerDocument::STATUS_PENDING) {
            return response()->json(['message' => 'Document is not pending verification'], 422);
        }

        $document->verify($admin->id);

        // Add notes to metadata if provided
        if ($request->notes) {
            $metadata = $document->metadata ?? [];
            $metadata['verification_notes'] = $request->notes;
            $document->metadata = $metadata;
            $document->save();
        }

        // Create activity log (you might want to implement this)
        // ActivityLog::create([
        //     'user_id' => $admin->id,
        //     'action' => 'document_verified',
        //     'subject_type' => SeekerDocument::class,
        //     'subject_id' => $document->id,
        //     'description' => "Verified {$document->document_type_display_name} for {$document->user->name}",
        // ]);

        return response()->json([
            'message' => 'Document verified successfully',
            'data' => new SeekerDocumentResource($document->fresh(['verifier:id,name']))
        ]);
    }

    /**
     * Reject a document
     */
    public function reject(Request $request, SeekerDocument $document): JsonResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $admin = Auth::user();
        
        // Check if admin has permission to verify documents
        if (!$admin->hasRole('admin') && !$admin->hasPermission('verify_documents')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($document->verification_status !== SeekerDocument::STATUS_PENDING) {
            return response()->json(['message' => 'Document is not pending verification'], 422);
        }

        $document->reject($request->reason, $admin->id);

        // Create activity log (you might want to implement this)
        // ActivityLog::create([
        //     'user_id' => $admin->id,
        //     'action' => 'document_rejected',
        //     'subject_type' => SeekerDocument::class,
        //     'subject_id' => $document->id,
        //     'description' => "Rejected {$document->document_type_display_name} for {$document->user->name}: {$request->reason}",
        // ]);

        return response()->json([
            'message' => 'Document rejected successfully',
            'data' => new SeekerDocumentResource($document->fresh(['verifier:id,name']))
        ]);
    }

    /**
     * Reset document to pending status
     */
    public function reset(SeekerDocument $document): JsonResponse
    {
        $admin = Auth::user();
        
        // Check if admin has permission to verify documents
        if (!$admin->hasRole('admin') && !$admin->hasPermission('verify_documents')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $document->resetToPending();

        return response()->json([
            'message' => 'Document reset to pending successfully',
            'data' => new SeekerDocumentResource($document->fresh(['verifier:id,name']))
        ]);
    }

    /**
     * Get document details for admin review
     */
    public function show(SeekerDocument $document): JsonResponse
    {
        $admin = Auth::user();
        
        // Check if admin has permission to verify documents
        if (!$admin->hasRole('admin') && !$admin->hasPermission('verify_documents')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $document->load(['user:id,name,email,phone', 'verifier:id,name']);

        return response()->json([
            'data' => new SeekerDocumentResource($document),
            'user_documents_count' => $document->user->seekerDocuments()->count(),
            'user_verified_count' => $document->user->seekerDocuments()->verified()->count(),
            'user_pending_count' => $document->user->seekerDocuments()->pending()->count(),
        ]);
    }
}
