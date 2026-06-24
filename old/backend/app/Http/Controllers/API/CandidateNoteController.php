<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CandidateNote;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CandidateNoteController extends Controller
{
    /**
     * Get all notes for a candidate.
     */
    public function index(Request $request, $seekerId): JsonResponse
    {
        $employer = $request->user()->employer;
        
        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $notes = CandidateNote::fromEmployer($employer->id)
            ->forSeeker($seekerId)
            ->with('createdBy')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($note) {
                return [
                    'id' => $note->id,
                    'content' => $note->content,
                    'created_by_name' => $note->createdBy?->name ?? 'Unknown',
                    'created_at' => $note->created_at,
                ];
            });

        return response()->json(['data' => $notes]);
    }

    /**
     * Add a note for a candidate.
     */
    public function store(Request $request, $seekerId): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $employer = $request->user()->employer;
        
        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $note = CandidateNote::create([
            'employer_id' => $employer->id,
            'seeker_id' => $seekerId,
            'created_by' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        return response()->json([
            'message' => 'Note added successfully',
            'data' => [
                'id' => $note->id,
                'content' => $note->content,
                'created_by_name' => $request->user()->name,
                'created_at' => $note->created_at,
            ],
        ], 201);
    }

    /**
     * Delete a note.
     */
    public function destroy(Request $request, $seekerId, $noteId): JsonResponse
    {
        $employer = $request->user()->employer;
        
        if (!$employer) {
            return response()->json(['message' => 'Employer profile not found'], 404);
        }

        $note = CandidateNote::fromEmployer($employer->id)
            ->forSeeker($seekerId)
            ->findOrFail($noteId);

        $note->delete();

        return response()->json(['message' => 'Note deleted successfully']);
    }
}
