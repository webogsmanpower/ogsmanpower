<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\FormSection;
use App\Models\FormField;
use App\Models\Seeker;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FormSchemaController extends Controller
{
    /**
     * Get form schema for a specific module (public API)
     */
    public function getSchema(string $module): JsonResponse
    {
        $sections = FormSection::forModule($module)
            ->active()
            ->ordered()
            ->with(['activeFields' => function ($query) {
                $query->ordered();
            }])
            ->get();

        // Transform fields to include parsed options
        $sections->transform(function ($section) {
            $sectionArray = $section->toArray();
            
            // Map activeFields to fields array
            $sectionArray['fields'] = $section->activeFields->map(function ($field) {
                $fieldArray = $field->toArray();
                $fieldArray['options'] = $field->getResolvedOptions();
                $fieldArray['options_source'] = $field->options_source; // Ensure options_source is included
                $fieldArray['validation_rules'] = [];
                return $fieldArray;
            })->toArray();
            
            // Remove the active_fields relationship to avoid duplication
            unset($sectionArray['active_fields']);
            
            return $sectionArray;
        });

        return response()->json([
            'module' => $module,
            'sections' => $sections,
        ]);
    }

    /**
     * Get seeker profile data with custom attributes
     */
    public function getSeekerProfile(): JsonResponse
    {
        $user = Auth::user();
        $seeker = $user->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        // Get form schema to structure the data
        $schemaResponse = $this->getSchema('seeker_profile');
        $schema = $schemaResponse->getData(true);
        
        // Build structured data based on schema
        $profileData = [];
        
        foreach ($schema['sections'] as $section) {
            $sectionKey = $section['key'];
            $profileData[$sectionKey] = [];

            foreach ($section['fields'] as $field) {
                $fieldName = $field['name'];
                $value = null;

                // Check if it's a core field (real database column)
                if ($this->isCoreField($fieldName)) {
                    $value = $seeker->{$fieldName};
                } else {
                    // Check custom attributes
                    $value = $seeker->custom_attributes[$fieldName] ?? null;
                }

                $profileData[$sectionKey][$fieldName] = $value;
            }
        }

        return response()->json([
            'profile' => $profileData,
            'schema' => $schema,
        ]);
    }

    /**
     * Update seeker profile with hybrid data storage
     */
    public function updateSeekerProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        $seeker = $user->seeker;

        if (!$seeker) {
            return response()->json(['message' => 'Seeker profile not found'], 404);
        }

        $validated = $request->validate([
            'data' => 'required|array',
        ]);

        $data = $validated['data'];
        $coreData = [];
        $customAttributes = $seeker->custom_attributes ?? [];

        // Get schema to identify field types
        $schema = $this->getSchema('seeker_profile')->getData(true);

        foreach ($data as $sectionKey => $sectionData) {
            if (!is_array($sectionData)) {
                continue;
            }

            foreach ($sectionData as $fieldName => $value) {
                // Check if it's a core field
                if ($this->isCoreField($fieldName)) {
                    $coreData[$fieldName] = $value;
                } else {
                    // Store in custom attributes
                    $customAttributes[$fieldName] = $value;
                }
            }
        }

        // Update core fields
        if (!empty($coreData)) {
            $seeker->update($coreData);
        }

        // Update custom attributes
        $seeker->custom_attributes = $customAttributes;
        $seeker->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'profile' => $this->getSeekerProfile()->getData(true)['profile'],
        ]);
    }

    /**
     * Check if a field is a core database field
     */
    private function isCoreField(string $fieldName): bool
    {
        $coreFields = [
            // Users table fields
            'first_name', 'last_name', 'email', 'phone',
            // Seekers table fields
            'dob', 'gender', 'nationality', 'profession', 'bio',
            'profile_image_path', 'full_body_image_path', 'profile_completion',
            'job_titles', 'preferred_locations', 'expected_salary', 'notice_period',
            'profile_visibility', 'contact_visibility',
        ];

        return in_array($fieldName, $coreFields);
    }

    /**
     * Get field validation rules based on schema
     */
    public function getFieldValidationRules(string $module): JsonResponse
    {
        $schema = $this->getSchema($module)->getData(true);
        $rules = [];

        foreach ($schema['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                $fieldName = $field['name'];
                $fieldRules = [];

                if ($field['required']) {
                    $fieldRules[] = 'required';
                } else {
                    $fieldRules[] = 'nullable';
                }

                // Add type-specific rules
                switch ($field['type']) {
                    case 'email':
                        $fieldRules[] = 'email';
                        break;
                    case 'number':
                        $fieldRules[] = 'numeric';
                        break;
                    case 'date':
                        $fieldRules[] = 'date';
                        break;
                    case 'url':
                        $fieldRules[] = 'url';
                        break;
                    case 'file':
                        $fieldRules[] = 'file';
                        $fieldRules[] = 'max:10240'; // 10MB max
                        break;
                }

                // Add custom validation rules
                if (!empty($field['validation_rules'])) {
                    $fieldRules = array_merge($fieldRules, array_keys($field['validation_rules']));
                }

                $rules[$fieldName] = implode('|', $fieldRules);
            }
        }

        return response()->json(['rules' => $rules]);
    }
}
