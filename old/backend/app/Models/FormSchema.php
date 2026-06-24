<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSchema extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'module',
        'section',
        'name',
        'description',
        'fields',
        'sort_order',
        'is_active',
        'is_required',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fields' => 'array',
        'is_active' => 'boolean',
        'is_required' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Available modules for form schemas.
     */
    public const MODULES = [
        'seeker_profile' => 'Seeker Profile',
        'job_post' => 'Job Posting',
        'employer_profile' => 'Employer Profile',
    ];

    /**
     * Available field types.
     */
    public const FIELD_TYPES = [
        'text' => 'Text Input',
        'textarea' => 'Text Area',
        'number' => 'Number',
        'email' => 'Email',
        'phone' => 'Phone',
        'date' => 'Date',
        'select' => 'Dropdown Select',
        'multiselect' => 'Multi Select',
        'checkbox' => 'Checkbox',
        'radio' => 'Radio Buttons',
        'file' => 'File Upload',
        'image' => 'Image Upload',
        'url' => 'URL',
        'rich_text' => 'Rich Text Editor',
    ];

    /**
     * Scope to get only active schemas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get schemas by module.
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Get schemas for a specific module, ordered by sort_order.
     */
    public static function getForModule(string $module): \Illuminate\Database\Eloquent\Collection
    {
        return static::byModule($module)
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Validate field structure.
     */
    public static function validateFieldStructure(array $field): array
    {
        $errors = [];
        
        if (empty($field['key'])) {
            $errors[] = 'Field key is required';
        }
        
        if (empty($field['label'])) {
            $errors[] = 'Field label is required';
        }
        
        if (empty($field['type']) || !array_key_exists($field['type'], self::FIELD_TYPES)) {
            $errors[] = 'Valid field type is required';
        }
        
        // Validate options for select/multiselect/radio
        if (in_array($field['type'] ?? '', ['select', 'multiselect', 'radio'])) {
            if (empty($field['options']) || !is_array($field['options'])) {
                $errors[] = 'Options are required for select/multiselect/radio fields';
            }
        }
        
        return $errors;
    }

    /**
     * Get default field structure.
     */
    public static function getDefaultField(): array
    {
        return [
            'key' => '',
            'label' => '',
            'label_ar' => '',
            'type' => 'text',
            'required' => false,
            'placeholder' => '',
            'placeholder_ar' => '',
            'help_text' => '',
            'help_text_ar' => '',
            'options' => [],
            'validation' => [],
            'default_value' => null,
            'visible' => true,
            'order' => 0,
        ];
    }
}
