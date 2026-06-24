<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyAttribute;
use Illuminate\Http\Request;

class CompanyInputController extends Controller
{
    public function renderDynamicInput($attribute)
    {
        $company=Company::with('attributes')->where('id',$attribute->company_id)->first();

        return view('backend.company.dynamic-inputs', compact('attribute','company'))->render();
    }
        public function store(Request $request)
        {
            $validated = $request->validate([
                'label' => 'required|string|max:255',
                'type' => 'required|string',
                'required' => 'required|boolean',
                'active' => 'required|boolean',
            ]);

            $attribute = new CompanyAttribute();
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
            $attribute = CompanyAttribute::find($id);

            if ($attribute) {
                $attribute->delete();
                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false]);
        }

        // Toggle active/inactive state
        public function toggleActive(Request $request)
        {
            $attribute = CompanyAttribute::find($request->id);

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
            $attribute = CompanyAttribute::find($request->id);

            if ($attribute) {
                $attribute->is_required = $request->is_required;
                $attribute->save();

                return response()->json(['success' => true]);
            }

            return response()->json(['success' => false]);
        }
}
