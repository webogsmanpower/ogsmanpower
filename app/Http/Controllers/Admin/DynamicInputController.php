<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CandidateAttribute;
use App\Models\Candidate;

class DynamicInputController extends Controller
{
    public function renderDynamicInput($attribute)
{
    $candidate=Candidate::with('attributes')->where('id',$attribute->candidate_id)->first();

    return view('backend.candidate.dynamic-inputs', compact('attribute','candidate'))->render();
}
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'type' => 'required|string',
            'required' => 'required|boolean',
            'active' => 'required|boolean',
            'candidate_id' => 'required|exists:candidates,id', // Ensure candidate ID is valid
        ]);

        $attribute = new CandidateAttribute();
        $attribute->candidate_id = $validated['candidate_id']; // Associate with candidate
        $attribute->attribute_name = $validated['label'];
        $attribute->input_type = $validated['type'];
        $attribute->is_required = $validated['required'];
        $attribute->is_active = $validated['active'];
        $attribute->save();

        // return response()->json(['success' => true, 'html' => $this->renderDynamicInput($attribute)]);
        return response()->json([
            'success' => true,
            'attribute' => $attribute
        ]);
    }

   
    // Delete a dynamic input
    public function destroy($id)
    {
        $attribute = CandidateAttribute::find($id);

        if ($attribute) {
            $attribute->delete();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    // Toggle active/inactive state
    public function toggleActive(Request $request)
    {
        $attribute = CandidateAttribute::find($request->id);

        if ($attribute) {
            $attribute->is_active = $request->is_active;
            $attribute->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }

    // Toggle required/optional state
    public function toggleRequired(Request $request)
    {
        $attribute = CandidateAttribute::find($request->id);

        if ($attribute) {
            $attribute->is_required = $request->is_required;
            $attribute->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false]);
    }
}
