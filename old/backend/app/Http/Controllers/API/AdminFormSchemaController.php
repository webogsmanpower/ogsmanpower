<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FormSection;
use App\Models\FormField;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class AdminFormSchemaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('admin');
    }

    /**
     * Get form schema for a specific module
     */
    public function getSchema(Request $request, string $module): JsonResponse
    {
        $sections = FormSection::forModule($module)
            ->active()
            ->ordered()
            ->with(['activeFields' => function ($query) {
                $query->ordered();
            }])
            ->get();

        return response()->json([
            'module' => $module,
            'sections' => $sections,
            'field_types' => FormField::FIELD_TYPES,
        ]);
    }

    /**
     * Get all modules with their sections
     */
    public function getModules(): JsonResponse
    {
        $modules = FormSection::select('module')
            ->distinct()
            ->pluck('module')
            ->map(function ($module) {
                return [
                    'key' => $module,
                    'name' => ucfirst(str_replace('_', ' ', $module)),
                    'sections_count' => FormSection::forModule($module)->active()->count(),
                ];
            });

        return response()->json($modules);
    }

    // SECTION MANAGEMENT

    /**
     * Create a new section
     */
    public function createSection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => 'required|string',
            'title' => 'required|string|max:255',
            'key' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        // Ensure unique key within module
        $request->validate([
            'key' => [
                Rule::unique('form_sections')->where(function ($query) use ($validated) {
                    return $query->where('module', $validated['module']);
                })
            ]
        ]);

        $section = FormSection::create($validated);

        return response()->json($section, 201);
    }

    /**
     * Update a section
     */
    public function updateSection(Request $request, FormSection $section): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'icon' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $section->update($validated);

        return response()->json($section);
    }

    /**
     * Delete a section
     */
    public function deleteSection(FormSection $section): JsonResponse
    {
        // Check if section has system fields
        $systemFieldsCount = $section->fields()->system()->count();
        if ($systemFieldsCount > 0) {
            return response()->json([
                'message' => 'Cannot delete section with system fields',
                'system_fields_count' => $systemFieldsCount,
            ], 422);
        }

        $section->delete();

        return response()->json(['message' => 'Section deleted successfully']);
    }

    // FIELD MANAGEMENT

    /**
     * Create a new field
     */
    public function createField(Request $request, FormSection $section): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(FormField::FIELD_TYPES))],
            'required' => 'boolean',
            'options' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
            'is_system' => 'boolean',
            'placeholder' => 'nullable|string|max:255',
            'help_text' => 'nullable|string',
            'validation_rules' => 'nullable|string|max:255',
        ]);

        // Ensure unique name within section
        $request->validate([
            'name' => Rule::unique('form_fields')->where(function ($query) use ($section) {
                return $query->where('section_id', $section->id);
            })
        ]);

        $validated['section_id'] = $section->id;
        
        $field = FormField::create($validated);

        return response()->json($field, 201);
    }

    /**
     * Update a field
     */
    public function updateField(Request $request, FormField $field): JsonResponse
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(FormField::FIELD_TYPES))],
            'required' => 'boolean',
            'options' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'placeholder' => 'nullable|string|max:255',
            'help_text' => 'nullable|string',
            'validation_rules' => 'nullable|string|max:255',
        ]);

        // Prevent changing system field name
        if ($field->is_system && isset($validated['name']) && $validated['name'] !== $field->name) {
            return response()->json([
                'message' => 'Cannot change system field name',
            ], 422);
        }

        $field->update($validated);

        return response()->json($field);
    }

    /**
     * Delete a field
     */
    public function deleteField(FormField $field): JsonResponse
    {
        if ($field->is_system) {
            return response()->json([
                'message' => 'Cannot delete system fields',
            ], 422);
        }

        $field->delete();

        return response()->json(['message' => 'Field deleted successfully']);
    }

    /**
     * Reorder fields within a section
     */
    public function reorderFields(Request $request, FormSection $section): JsonResponse
    {
        $validated = $request->validate([
            'field_ids' => 'required|array',
            'field_ids.*' => 'exists:form_fields,id',
        ]);

        foreach ($validated['field_ids'] as $index => $fieldId) {
            FormField::where('id', $fieldId)
                ->where('section_id', $section->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['message' => 'Fields reordered successfully']);
    }

    /**
     * Reorder sections within a module
     */
    public function reorderSections(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => 'required|string',
            'section_ids' => 'required|array',
            'section_ids.*' => 'exists:form_sections,id',
        ]);

        foreach ($validated['section_ids'] as $index => $sectionId) {
            FormSection::where('id', $sectionId)
                ->where('module', $validated['module'])
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['message' => 'Sections reordered successfully']);
    }

    /**
     * Get metadata for form builder
     */
    public function getMetadata(): JsonResponse
    {
        return response()->json([
            'field_types' => FormField::FIELD_TYPES,
            'modules' => FormSection::select('module')->distinct()->pluck('module'),
            'validation_rules' => [
                'required' => 'Required',
                'email' => 'Email',
                'numeric' => 'Numeric',
                'min:0' => 'Minimum value',
                'max:255' => 'Maximum length',
                'date' => 'Date format',
                'url' => 'URL format',
            ],
        ]);
    }
}
