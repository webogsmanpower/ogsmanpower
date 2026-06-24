<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    public const FIELD_TYPES = [
        'text' => 'Text Input',
        'email' => 'Email',
        'number' => 'Number',
        'date' => 'Date',
        'select' => 'Dropdown',
        'multi_select' => 'Multi-Select',
        'checkbox' => 'Checkbox',
        'radio' => 'Radio Button',
        'textarea' => 'Text Area',
        'rich_text' => 'Rich Text Editor',
        'file' => 'File Upload',
        'url' => 'URL',
        'tags' => 'Tags',
        'repeater' => 'Repeater Section',
        'phone' => 'Phone with Country Code',
        'custom' => 'Custom Component',
        'avatar' => 'Avatar Upload',
    ];

    protected $fillable = [
        'section_id',
        'label',
        'name',
        'type',
        'required',
        'options',
        'options_source',
        'sort_order',
        'is_system',
        'placeholder',
        'help_text',
        'validation_rules',
        'is_active',
        'section', // Subsection grouping
        'col_span', // Field width (1-2)
        'variant', // Field variant (avatar, etc.)
        'helper_text', // Additional help text
        'component', // Custom component name
        'validation_options', // Advanced validation options
        'min_validity_months', // For date fields
        'min_validity_message', // Custom validation message
        'country_code_options', // For phone fields
        'default_country_code', // For phone fields
    ];

    protected $casts = [
        'required' => 'boolean',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'options' => 'array',
        'validation_options' => 'array',
        'country_code_options' => 'array',
        'col_span' => 'integer',
        'min_validity_months' => 'integer',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'section_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    public function getOptionsArray(): array
    {
        $options = $this->options;
        
        // Handle case where options is stored as JSON string instead of array
        if (is_string($options)) {
            $decoded = json_decode($options, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return is_array($options) ? $options : [];
    }

    public function getResolvedOptions(): array
    {
        // If options_source is set, use predefined list
        if ($this->options_source) {
            return $this->getOptionsFromSource($this->options_source);
        }
        
        // Otherwise use manually defined options
        return $this->getOptionsArray();
    }

    private function getOptionsFromSource(string $source): array
    {
        switch ($source) {
            case 'skills':
                return \App\Models\Skill::orderBy('name')
                    ->pluck('name', 'id')
                    ->map(function ($name, $id) {
                        return [
                            'value' => $id,
                            'label' => $name
                        ];
                    })
                    ->values()
                    ->toArray();
                    
            case 'job_titles':
                return \App\Models\JobTitle::orderBy('name')
                    ->pluck('name', 'id')
                    ->map(function ($name, $id) {
                        return [
                            'value' => $id,
                            'label' => $name
                        ];
                    })
                    ->values()
                    ->toArray();
                    
            case 'countries':
                $countries = config('countries.countries', []);
                return collect($countries)
                    ->map(function ($name, $code) {
                        return [
                            'value' => $code,
                            'label' => $name
                        ];
                    })
                    ->values()
                    ->toArray();
                    
            case 'industries':
                return \App\Models\Industry::orderBy('name')
                    ->pluck('name', 'id')
                    ->map(function ($name, $id) {
                        return [
                            'value' => $id,
                            'label' => $name
                        ];
                    })
                    ->values()
                    ->toArray();
                    
            case 'locations':
                // For locations, we could use cities or states
                // For now, return empty array - can be implemented later
                return [];
                
            default:
                return [];
        }
    }

    public function getValidationRulesArray(): array
    {
        if (!$this->validation_rules) {
            return [];
        }

        // Parse Laravel validation rules string into array
        $rules = explode('|', $this->validation_rules);
        $parsed = [];

        foreach ($rules as $rule) {
            if (strpos($rule, ':') !== false) {
                [$key, $value] = explode(':', $rule, 2);
                $parsed[$key] = $value;
            } else {
                $parsed[$rule] = true;
            }
        }

        return $parsed;
    }

    public function isSelectType(): bool
    {
        return in_array($this->type, ['select', 'multi_select', 'radio']);
    }

    public function isFileType(): bool
    {
        return $this->type === 'file';
    }

    public function isTextType(): bool
    {
        return in_array($this->type, ['text', 'email', 'number', 'url', 'textarea']);
    }

    public function isDateType(): bool
    {
        return $this->type === 'date';
    }

    public function isRepeaterType(): bool
    {
        return $this->type === 'repeater';
    }

    public function isPhoneType(): bool
    {
        return $this->type === 'phone';
    }

    public function isCustomType(): bool
    {
        return $this->type === 'custom';
    }

    public function isAvatarType(): bool
    {
        return $this->type === 'avatar';
    }

    public function isTagsType(): bool
    {
        return $this->type === 'tags';
    }

    public function hasSubsection(): bool
    {
        return !empty($this->section);
    }

    public function getColSpanClass(): string
    {
        return match($this->col_span) {
            2 => 'col-span-2',
            default => 'col-span-1'
        };
    }

    public function getComponentClass(): ?string
    {
        if (!$this->isCustomType() || !$this->component) {
            return null;
        }

        return match($this->component) {
            'JobTitleSelector' => \App\Http\Resources\Seeker\JobTitleSelector::class,
            'DegreeSelector' => \App\Http\Resources\Seeker\DegreeSelector::class,
            'LanguageSelector' => \App\Http\Resources\Seeker\LanguageSelector::class,
            'JobTitleTagsSelector' => \App\Http\Resources\Seeker\JobTitleTagsSelector::class,
            'IndustryTagsSelector' => \App\Http\Resources\Seeker\IndustryTagsSelector::class,
            'LocationTagsSelector' => \App\Http\Resources\Seeker\LocationTagsSelector::class,
            'SkillSelector' => \App\Http\Resources\Seeker\SkillSelector::class,
            default => null
        };
    }
}
