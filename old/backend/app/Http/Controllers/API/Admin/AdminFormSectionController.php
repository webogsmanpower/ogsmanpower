<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormSection;
use App\Models\FormField;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class AdminFormSectionController extends Controller
{
    /**
     * Get all sections for a module with their fields.
     */
    public function index(Request $request): JsonResponse
    {
        $module = $request->get('module', 'seeker_profile');
        
        $sections = FormSection::forModule($module)
            ->active()
            ->ordered()
            ->with(['fields' => function($query) {
                $query->ordered();
            }])
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $sections,
        ]);
    }

    /**
     * Get metadata (modules, field types).
     */
    public function metadata(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'modules' => [
                    'seeker_profile' => 'Seeker Profile',
                    'employer_profile' => 'Employer Profile',
                ],
                'field_types' => FormField::FIELD_TYPES,
            ],
        ]);
    }

    /**
     * Get a specific section with fields.
     */
    public function show(int $id): JsonResponse
    {
        $section = FormSection::with(['fields' => function($query) {
            $query->ordered();
        }])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $section,
        ]);
    }

    /**
     * Get fields for a specific section.
     */
    public function getFields(int $sectionId): JsonResponse
    {
        $section = FormSection::findOrFail($sectionId);
        $fields = $section->fields()->ordered()->get();
        
        return response()->json([
            'success' => true,
            'data' => $fields,
        ]);
    }

    /**
     * Create a new section.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => 'required|string|in:seeker_profile,employer_profile',
            'title' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Check for duplicate
        $exists = FormSection::where('module', $validated['module'])
            ->where('key', $validated['key'])
            ->exists();
            
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A section with this key already exists for this module',
            ], 422);
        }

        $section = FormSection::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Section created successfully',
            'data' => $section,
        ], 201);
    }

    /**
     * Update a section.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $section = FormSection::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'sometimes|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $section->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully',
            'data' => $section->fresh(),
        ]);
    }

    /**
     * Delete a section (and its fields).
     */
    public function destroy(int $id): JsonResponse
    {
        $section = FormSection::findOrFail($id);
        
        // Prevent deleting system sections
        if ($section->fields()->where('is_system', true)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete section with system fields',
            ], 422);
        }
        
        $section->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Section deleted successfully',
        ]);
    }

    /**
     * Toggle section active status.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $section = FormSection::findOrFail($id);
        $section->is_active = !$section->is_active;
        $section->save();
        
        return response()->json([
            'success' => true,
            'message' => "Section " . ($section->is_active ? 'activated' : 'deactivated') . " successfully",
            'data' => $section,
        ]);
    }

    /**
     * Add a field to a section.
     */
    public function addField(Request $request, int $sectionId): JsonResponse
    {
        $section = FormSection::findOrFail($sectionId);
        
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::in(array_keys(FormField::FIELD_TYPES))],
            'required' => 'boolean',
            'options' => 'nullable|array',
            'options_source' => 'nullable|string|in:skills,job_titles,countries,industries,locations',
            'placeholder' => 'nullable|string',
            'help_text' => 'nullable|string',
            'validation_rules' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // If options_source is provided, ensure options is null or empty array
        if (isset($validated['options_source']) && $validated['options_source']) {
            $validated['options'] = null;
        }

        // Check for duplicate field name in section
        $exists = FormField::where('section_id', $sectionId)
            ->where('name', $validated['name'])
            ->exists();
            
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A field with this name already exists in this section',
            ], 422);
        }

        $validated['section_id'] = $sectionId;
        $validated['sort_order'] = $section->fields()->count() + 1;
        
        $field = FormField::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Field added successfully',
            'data' => $field,
        ], 201);
    }

    /**
     * Update a field.
     */
    public function updateField(Request $request, int $sectionId, int $fieldId): JsonResponse
    {
        $field = FormField::where('section_id', $sectionId)
            ->findOrFail($fieldId);
        
        // Prevent editing system fields
        if ($field->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit system fields',
            ], 422);
        }
        
        $validated = $request->validate([
            'label' => 'sometimes|string|max:255',
            'type' => ['sometimes', 'string', Rule::in(array_keys(FormField::FIELD_TYPES))],
            'required' => 'boolean',
            'options' => 'nullable|array',
            'options_source' => 'nullable|string|in:skills,job_titles,countries,industries,locations',
            'placeholder' => 'nullable|string',
            'help_text' => 'nullable|string',
            'validation_rules' => 'nullable|string',
            'is_active' => 'boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        // If options_source is provided, ensure options is null or empty array
        if (isset($validated['options_source']) && $validated['options_source']) {
            $validated['options'] = null;
        }

        $field->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Field updated successfully',
            'data' => $field->fresh(),
        ]);
    }

    /**
     * Delete a field.
     */
    public function deleteField(int $sectionId, int $fieldId): JsonResponse
    {
        $field = FormField::where('section_id', $sectionId)
            ->findOrFail($fieldId);
        
        // Prevent deleting system fields
        if ($field->is_system) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete system fields',
            ], 422);
        }
        
        $field->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Field deleted successfully',
        ]);
    }

    /**
     * Reorder fields within a section.
     */
    public function reorderFields(Request $request, int $sectionId): JsonResponse
    {
        $section = FormSection::findOrFail($sectionId);
        
        $validated = $request->validate([
            'fields' => 'required|array',
            'fields.*.id' => 'required|integer|exists:form_fields,id',
            'fields.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['fields'] as $fieldData) {
            FormField::where('id', $fieldData['id'])
                ->where('section_id', $sectionId)
                ->update(['sort_order' => $fieldData['sort_order']]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Fields reordered successfully',
        ]);
    }
}
