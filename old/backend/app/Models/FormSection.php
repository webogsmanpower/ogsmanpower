<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSection extends Model
{
    protected $fillable = [
        'module',
        'title',
        'key',
        'icon',
        'sort_order',
        'is_active',
        'is_multi_entry', // Support for multiple entries
        'add_new_label', // Label for add new button
        'entry_title_template', // Template for entry titles
        'style_variant', // Style variant (sectioned-card, etc.)
        'description', // Section description
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_multi_entry' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'section_id')->orderBy('sort_order');
    }

    public function activeFields(): HasMany
    {
        return $this->hasMany(FormField::class, 'section_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function scopeForModule($query, $module)
    {
        return $query->where('module', $module);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function fieldsBySubsection(): array
    {
        $fields = $this->activeFields;
        $grouped = [];

        foreach ($fields as $field) {
            $subsection = $field->section ?? 'default';
            if (!isset($grouped[$subsection])) {
                $grouped[$subsection] = [];
            }
            $grouped[$subsection][] = $field;
        }

        return $grouped;
    }

    public function getAddNewLabel(): string
    {
        return $this->add_new_label ?? 'Add Entry';
    }

    public function getEntryTitleTemplate(): string
    {
        return $this->entry_title_template ?? '{index}';
    }

    public function formatEntryTitle(array $entry, int $index): string
    {
        $template = $this->getEntryTitleTemplate();
        
        // Replace placeholders
        $title = str_replace('{index}', $index + 1, $template);
        
        // Replace field placeholders like {field_name}
        foreach ($entry as $key => $value) {
            $title = str_replace('{' . $key . '}', $value, $title);
        }

        return $title;
    }

    public function getStyleVariant(): string
    {
        return $this->style_variant ?? 'default';
    }
}
