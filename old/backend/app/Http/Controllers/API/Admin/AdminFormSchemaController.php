<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormSchema;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class AdminFormSchemaController extends Controller
{
    /**
     * Display a listing of form schemas.
     */
    public function index(Request $request): JsonResponse
    {
        $query = FormSchema::query();
        
        // Filter by module
        if ($request->has('module')) {
            $query->where('module', $request->module);
        }
        
        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        
        $schemas = $query->orderBy('module')
            ->orderBy('sort_order')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $schemas,
        ]);
    }

    /**
     * Get available modules and field types.
     */
    public function metadata(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'modules' => FormSchema::MODULES,
                'field_types' => FormSchema::FIELD_TYPES,
                'default_field' => FormSchema::getDefaultField(),
            ],
        ]);
    }

    /**
     * Store a newly created form schema.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'module' => ['required', 'string', Rule::in(array_keys(FormSchema::MODULES))],
            'section' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'fields' => 'required|array|min:1',
            'fields.*.key' => 'required|string|max:255',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.type' => ['required', 'string', Rule::in(array_keys(FormSchema::FIELD_TYPES))],
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable|array',
            'fields.*.validation' => 'nullable|array',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
            'is_required' => 'boolean',
        ]);

        // Check for duplicate module+section
        $exists = FormSchema::where('module', $validated['module'])
            ->where('section', $validated['section'])
            ->exists();
            
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A schema for this module and section already exists',
            ], 422);
        }

        // Validate field structures
        foreach ($validated['fields'] as $index => $field) {
            $errors = FormSchema::validateFieldStructure($field);
            if (!empty($errors)) {
                return response()->json([
                    'success' => false,
                    'message' => "Field {$index} validation failed",
                    'errors' => $errors,
                ], 422);
            }
        }

        $schema = FormSchema::create($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Form schema created successfully',
            'data' => $schema,
        ], 201);
    }

    /**
     * Display the specified form schema.
     */
    public function show(int $id): JsonResponse
    {
        $schema = FormSchema::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $schema,
        ]);
    }

    /**
     * Update the specified form schema.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $schema = FormSchema::findOrFail($id);
        
        $validated = $request->validate([
            'module' => ['sometimes', 'string', Rule::in(array_keys(FormSchema::MODULES))],
            'section' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:1000',
            'fields' => 'sometimes|array|min:1',
            'fields.*.key' => 'required_with:fields|string|max:255',
            'fields.*.label' => 'required_with:fields|string|max:255',
            'fields.*.type' => ['required_with:fields', 'string', Rule::in(array_keys(FormSchema::FIELD_TYPES))],
            'fields.*.required' => 'boolean',
            'fields.*.options' => 'nullable|array',
            'fields.*.validation' => 'nullable|array',
            'sort_order' => 'sometimes|integer|min:0',
            'is_active' => 'boolean',
            'is_required' => 'boolean',
        ]);

        // Check for duplicate module+section if changing
        if (isset($validated['module']) || isset($validated['section'])) {
            $module = $validated['module'] ?? $schema->module;
            $section = $validated['section'] ?? $schema->section;
            
            $exists = FormSchema::where('module', $module)
                ->where('section', $section)
                ->where('id', '!=', $id)
                ->exists();
                
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'A schema for this module and section already exists',
                ], 422);
            }
        }

        // Validate field structures if fields are being updated
        if (isset($validated['fields'])) {
            foreach ($validated['fields'] as $index => $field) {
                $errors = FormSchema::validateFieldStructure($field);
                if (!empty($errors)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Field {$index} validation failed",
                        'errors' => $errors,
                    ], 422);
                }
            }
        }

        $schema->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Form schema updated successfully',
            'data' => $schema->fresh(),
        ]);
    }

    /**
     * Remove the specified form schema.
     */
    public function destroy(int $id): JsonResponse
    {
        $schema = FormSchema::findOrFail($id);
        $schema->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Form schema deleted successfully',
        ]);
    }

    /**
     * Toggle schema active status.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $schema = FormSchema::findOrFail($id);
        $schema->is_active = !$schema->is_active;
        $schema->save();
        
        return response()->json([
            'success' => true,
            'message' => "Schema " . ($schema->is_active ? 'activated' : 'deactivated') . " successfully",
            'data' => $schema,
        ]);
    }

    /**
     * Get schemas for a specific module (public endpoint for frontend forms).
     */
    public function getByModule(string $module): JsonResponse
    {
        if (!array_key_exists($module, FormSchema::MODULES)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid module',
            ], 400);
        }
        
        $schemas = FormSchema::getForModule($module);
        
        return response()->json([
            'success' => true,
            'data' => $schemas,
        ]);
    }

    /**
     * Duplicate a schema.
     */
    public function duplicate(int $id): JsonResponse
    {
        $original = FormSchema::findOrFail($id);
        
        $newSchema = $original->replicate();
        $newSchema->section = $original->section . '_copy';
        $newSchema->name = $original->name . ' (Copy)';
        $newSchema->is_active = false;
        $newSchema->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Schema duplicated successfully',
            'data' => $newSchema,
        ], 201);
    }

    /**
     * Reorder schemas within a module.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|integer|exists:form_schemas,id',
            'orders.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['orders'] as $order) {
            FormSchema::where('id', $order['id'])
                ->update(['sort_order' => $order['sort_order']]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Schemas reordered successfully',
        ]);
    }
}
