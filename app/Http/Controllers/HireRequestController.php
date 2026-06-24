<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\HireRequest;
use Illuminate\Http\Request;

class HireRequestController extends Controller
{
    public function hire_request(Request $request)
    {
        $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
        ]);

        $employer = auth()->user(); // Assumes the employer is logged in
        $candidate = Candidate::findOrFail($request->candidate_id);

        $message = "Employer {$employer->name} wants to hire Candidate {$candidate->name}.";

        // Save the request to the database
        HireRequest::create([
            'candidate_id' => $candidate->id,
            'employer_id' => $employer->id,
            'message' => $message,
        ]);

        // Send notification to admin (optional)
        // Notification logic goes here

        return back()->with('success', 'Your request has been sent to the admin.');
    }
}


