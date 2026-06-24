<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\SeekerDocumentResource;
use App\Models\SeekerDocument;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SeekerDocumentController extends Controller
{
    /**
     * Get all documents for the authenticated seeker
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $documents = $user->seekerDocuments()
            ->with('verifier:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => SeekerDocumentResource::collection($documents),
            'stats' => [
                'total' => $documents->count(),
                'pending' => $documents->where('verification_status', SeekerDocument::STATUS_PENDING)->count(),
                'verified' => $documents->where('verification_status', SeekerDocument::STATUS_VERIFIED)->count(),
                'rejected' => $documents->where('verification_status', SeekerDocument::STATUS_REJECTED)->count(),
            ]
        ]);
    }

    /**
     * Upload a new document
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'document_type' => ['required', Rule::in(array_keys(SeekerDocument::DOCUMENT_TYPES))],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,mp4,mov,avi'],
        ]);

        $user = Auth::user();
        $file = $request->file('file');
        $documentType = $request->document_type;

        // Check if user already has a document of this type (allow multiple for some types)
        $existingDocument = $user->seekerDocuments()
            ->where('document_type', $documentType)
            ->first();

        if ($existingDocument && !in_array($documentType, ['degree_certificate', 'transcript', 'experience_letter', 'other'])) {
            return response()->json([
                'message' => 'You have already uploaded this document type. Please delete the existing one first.'
            ], 422);
        }

        // Store the file
        $path = $file->store('documents/' . $user->id, 'public');

        // Create document record
        $document = SeekerDocument::create([
            'user_id' => $user->id,
            'document_type' => $documentType,
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'verification_status' => SeekerDocument::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Document uploaded successfully',
            'data' => new SeekerDocumentResource($document)
        ], 201);
    }

    /**
     * Get a specific document
     */
    public function show(SeekerDocument $document): JsonResponse
    {
        // Ensure user can only access their own documents
        if ($document->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'data' => new SeekerDocumentResource($document)
        ]);
    }

    /**
     * Update document metadata (not file itself)
     */
    public function update(Request $request, SeekerDocument $document): JsonResponse
    {
        // Ensure user can only update their own documents
        if ($document->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'metadata' => ['nullable', 'array'],
        ]);

        $document->update($request->only('metadata'));

        return response()->json([
            'message' => 'Document updated successfully',
            'data' => new SeekerDocumentResource($document)
        ]);
    }

    /**
     * Delete a document
     */
    public function destroy(SeekerDocument $document): JsonResponse
    {
        // Ensure user can only delete their own documents
        if ($document->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        // Delete database record
        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully'
        ]);
    }

    /**
     * Get document download URL
     */
    public function download(SeekerDocument $document): JsonResponse
    {
        // Ensure user can only download their own documents
        if ($document->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->json([
            'download_url' => Storage::disk('public')->url($document->file_path),
            'filename' => $document->original_filename,
            'mime_type' => $document->mime_type
        ]);
    }
}
