<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'type',
        'name',
        'content',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'content' => 'json',
        'is_active' => 'boolean',
    ];

    /**
     * Get the page that owns the content block
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * Get content value by key
     */
    public function getContentValue(string $key, $default = null)
    {
        return data_get($this->content, $key, $default);
    }

    /**
     * Set content value by key
     */
    public function setContentValue(string $key, $value)
    {
        $content = $this->content ?? [];
        data_set($content, $key, $value);
        $this->content = $content;
    }

    /**
     * Scope to get active blocks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get blocks by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Available block types
     */
    public static function getAvailableTypes(): array
    {
        return [
            'hero' => 'Hero Section',
            'features' => 'Features Grid',
            'text' => 'Text Content',
            'image' => 'Image',
            'cta' => 'Call to Action',
            'testimonials' => 'Testimonials',
            'stats' => 'Statistics',
            'how_it_works' => 'How It Works',
            'pricing' => 'Pricing Plans',
            'faq' => 'FAQ Section',
            'contact' => 'Contact Form',
            'gallery' => 'Image Gallery',
            'video' => 'Video Section',
        ];
    }

    /**
     * Get default content structure for block type
     */
    public static function getDefaultContent(string $type): array
    {
        return match ($type) {
            'hero' => [
                'headline' => 'Welcome to Our Platform',
                'subheadline' => 'Discover amazing opportunities',
                'primary_cta_text' => 'Get Started',
                'primary_cta_url' => '/register',
                'secondary_cta_text' => 'Learn More',
                'secondary_cta_url' => '/about',
                'background_image' => null,
                'background_color' => '#ffffff',
            ],
            'features' => [
                'title' => 'Our Features',
                'subtitle' => 'Discover what makes us special',
                'features' => [
                    [
                        'icon' => 'star',
                        'title' => 'Feature 1',
                        'description' => 'Description of feature 1',
                    ],
                    [
                        'icon' => 'shield',
                        'title' => 'Feature 2',
                        'description' => 'Description of feature 2',
                    ],
                    [
                        'icon' => 'zap',
                        'title' => 'Feature 3',
                        'description' => 'Description of feature 3',
                    ],
                ],
            ],
            'text' => [
                'title' => 'Title',
                'content' => 'Your content goes here...',
                'alignment' => 'left',
            ],
            'image' => [
                'url' => null,
                'alt' => 'Image description',
                'caption' => '',
                'alignment' => 'center',
            ],
            'cta' => [
                'title' => 'Ready to get started?',
                'description' => 'Join us today and transform your future',
                'button_text' => 'Get Started',
                'button_url' => '/register',
                'background_color' => '#3b82f6',
                'text_color' => '#ffffff',
            ],
            'testimonials' => [
                'title' => 'What Our Users Say',
                'testimonials' => [
                    [
                        'name' => 'John Doe',
                        'role' => 'CEO',
                        'company' => 'Company Inc',
                        'content' => 'Amazing platform!',
                        'avatar' => null,
                    ],
                ],
            ],
            'stats' => [
                'title' => 'Our Impact',
                'stats' => [
                    ['number' => '1000+', 'label' => 'Users'],
                    ['number' => '50+', 'label' => 'Countries'],
                    ['number' => '99%', 'label' => 'Satisfaction'],
                ],
            ],
            'how_it_works' => [
                'title' => 'How It Works',
                'steps' => [
                    [
                        'step' => 1,
                        'title' => 'Step 1',
                        'description' => 'Description of step 1',
                        'icon' => 'user',
                    ],
                    [
                        'step' => 2,
                        'title' => 'Step 2',
                        'description' => 'Description of step 2',
                        'icon' => 'settings',
                    ],
                    [
                        'step' => 3,
                        'title' => 'Step 3',
                        'description' => 'Description of step 3',
                        'icon' => 'check',
                    ],
                ],
            ],
            default => [],
        };
    }
}
