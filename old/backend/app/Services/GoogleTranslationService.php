<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * GoogleTranslationService
 * 
 * Handles translation of model fields using Google Cloud Translation API.
 * Implements caching strategy to minimize API calls:
 * 
 * 1. Check if translation exists in model's translations JSON column
 * 2. Check if translation is stale (model updated after translation)
 * 3. If fresh, return cached translation
 * 4. If stale/missing, call Google API and cache result
 * 
 * Supported languages: ar, ur, hi, fr, es, zh, bn, tl
 */
class GoogleTranslationService
{
    /**
     * Google Cloud Translation API endpoint
     */
    protected string $apiEndpoint = 'https://translation.googleapis.com/language/translate/v2';

    /**
     * API Key from environment
     */
    protected ?string $apiKey;

    /**
     * Supported target languages
     */
    protected array $supportedLanguages = ['ar', 'ur', 'hi', 'fr', 'es', 'zh', 'bn', 'tl'];

    /**
     * Field mappings for different model types
     * Maps model class to translatable fields
     */
    protected array $translatableFields = [
        'seeker' => ['bio', 'headline'],
        'seeker_resume' => [
            'professional_summary' => ['professional_summary', 'career_objective', 'industry_experience'],
            'work_experience' => ['job_title', 'role_title', 'company_name', 'location', 'description', 'responsibilities'],
            'education' => ['degree', 'degree_title', 'institution', 'institution_name', 'field_of_study', 'description'],
            'skills' => ['name', 'skill_name', 'primary_skill', 'description'],
            'languages' => ['language', 'language_name'],
            'certifications' => ['certification_name', 'issuer', 'description'],
            'references' => ['name', 'job_title', 'company_name', 'relationship'],
        ],
    ];

    public function __construct()
    {
        $this->apiKey = config('services.google.translation_api_key') 
            ?? env('GOOGLE_CLOUD_TRANSLATION_API_KEY');
    }

    /**
     * Translate a model's fields to target language
     * 
     * @param Model $model The model to translate
     * @param string $targetLocale Target language code (e.g., 'ar', 'fr')
     * @param array $fields Fields to translate
     * @return array Translated data
     */
    public function translateModel(Model $model, string $targetLocale, array $fields = []): array
    {
        // Validate target locale
        if (!in_array($targetLocale, $this->supportedLanguages)) {
            Log::warning("GoogleTranslationService: Unsupported language {$targetLocale}");
            return [];
        }

        // Check cache first
        $cachedTranslation = $this->getCachedTranslation($model, $targetLocale);
        
        if ($cachedTranslation && !$this->isTranslationStale($model, $targetLocale)) {
            Log::info("GoogleTranslationService: Using cached translation for {$targetLocale}");
            return $cachedTranslation;
        }

        // Need fresh translation
        Log::info("GoogleTranslationService: Fetching fresh translation for {$targetLocale}");
        
        // Collect texts to translate
        $textsToTranslate = $this->collectTextsFromModel($model, $fields);
        
        if (empty($textsToTranslate)) {
            Log::info("GoogleTranslationService: No texts to translate");
            return [];
        }

        // Call Google API
        $translations = $this->callGoogleTranslateAPI($textsToTranslate, $targetLocale);
        
        if (empty($translations)) {
            Log::error("GoogleTranslationService: API returned empty translations");
            return [];
        }

        // Map translations back to fields
        $translatedData = $this->mapTranslationsToFields($textsToTranslate, $translations, $targetLocale);

        // Cache the translation
        $this->cacheTranslation($model, $targetLocale, $translatedData);

        return $translatedData;
    }

    /**
     * Translate a SeekerResume with all its nested JSON fields
     * 
     * @param \App\Models\SeekerResume $resume
     * @param string $targetLocale
     * @return array Complete translated resume data
     */
    public function translateResume(\App\Models\SeekerResume $resume, string $targetLocale): array
    {
        // Validate target locale
        if (!in_array($targetLocale, $this->supportedLanguages)) {
            Log::warning("GoogleTranslationService: Unsupported language {$targetLocale}");
            return [];
        }

        // Check cache first
        $cachedTranslation = $this->getCachedTranslation($resume, $targetLocale);
        
        if ($cachedTranslation && !$this->isTranslationStale($resume, $targetLocale)) {
            Log::info("GoogleTranslationService: Using cached resume translation for {$targetLocale}");
            return $cachedTranslation;
        }

        Log::info("GoogleTranslationService: Translating resume ID {$resume->id} to {$targetLocale}");

        $translatedData = [];
        $allTextsToTranslate = [];
        $fieldMapping = []; // Maps index to field path

        // Collect all texts from resume sections
        $sections = [
            'professional_summary' => $resume->professional_summary,
            'work_experience' => $resume->work_experience,
            'education' => $resume->education,
            'skills' => $resume->skills,
            'languages' => $resume->languages,
            'certifications' => $resume->certifications,
            'references' => $resume->references,
            'basic_information' => $resume->basic_information,
        ];

        foreach ($sections as $sectionName => $sectionData) {
            if (empty($sectionData)) continue;

            $sectionFields = $this->translatableFields['seeker_resume'][$sectionName] ?? [];
            
            // Handle both object and array formats
            if (is_array($sectionData)) {
                // Check if it's an array of items or a single object
                if ($this->isAssociativeArray($sectionData)) {
                    // Single object
                    $this->collectTextsFromSection(
                        $sectionData, 
                        $sectionFields, 
                        $sectionName, 
                        null, 
                        $allTextsToTranslate, 
                        $fieldMapping
                    );
                } else {
                    // Array of items
                    foreach ($sectionData as $index => $item) {
                        if (is_array($item)) {
                            $this->collectTextsFromSection(
                                $item, 
                                $sectionFields, 
                                $sectionName, 
                                $index, 
                                $allTextsToTranslate, 
                                $fieldMapping
                            );
                        }
                    }
                }
            }
        }

        // Also translate basic_information fields
        $basicInfo = $resume->basic_information ?? [];
        $basicInfoFields = ['full_name', 'job_title', 'professional_summary'];
        foreach ($basicInfoFields as $field) {
            if (!empty($basicInfo[$field]) && is_string($basicInfo[$field])) {
                $allTextsToTranslate[] = $basicInfo[$field];
                $fieldMapping[] = ['section' => 'basic_information', 'index' => null, 'field' => $field];
            }
        }

        if (empty($allTextsToTranslate)) {
            Log::info("GoogleTranslationService: No texts to translate in resume");
            return [];
        }

        // Batch translate all texts
        $translations = $this->callGoogleTranslateAPI($allTextsToTranslate, $targetLocale);

        if (empty($translations)) {
            Log::error("GoogleTranslationService: API returned empty translations for resume");
            return [];
        }

        // Map translations back to structure
        $translatedData = $this->mapTranslationsToResumeStructure($fieldMapping, $translations, $sections, $targetLocale);

        // Cache the translation
        $this->cacheTranslation($resume, $targetLocale, $translatedData);

        return $translatedData;
    }

    /**
     * Collect texts from a section for batch translation
     */
    protected function collectTextsFromSection(
        array $data, 
        array $fields, 
        string $sectionName, 
        ?int $index, 
        array &$allTexts, 
        array &$fieldMapping
    ): void {
        foreach ($fields as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $allTexts[] = $data[$field];
                $fieldMapping[] = [
                    'section' => $sectionName,
                    'index' => $index,
                    'field' => $field,
                ];
            }
        }
    }

    /**
     * Map translations back to resume structure
     */
    protected function mapTranslationsToResumeStructure(
        array $fieldMapping, 
        array $translations, 
        array $originalSections,
        string $targetLocale
    ): array {
        $result = [];
        $suffix = '_' . $targetLocale;

        foreach ($fieldMapping as $i => $mapping) {
            if (!isset($translations[$i])) continue;

            $section = $mapping['section'];
            $index = $mapping['index'];
            $field = $mapping['field'];
            $translatedField = $field . $suffix; // Append language suffix for translated fields

            if ($index === null) {
                // Single object section
                if (!isset($result[$section])) {
                    $result[$section] = [];
                }
                $result[$section][$translatedField] = $translations[$i];
            } else {
                // Array section
                if (!isset($result[$section])) {
                    $result[$section] = [];
                }
                if (!isset($result[$section][$index])) {
                    $result[$section][$index] = [];
                }
                $result[$section][$index][$translatedField] = $translations[$i];
            }
        }

        return $result;
    }

    /**
     * Check if array is associative (object-like) or sequential
     */
    protected function isAssociativeArray(array $arr): bool
    {
        if (empty($arr)) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Get cached translation from model
     */
    protected function getCachedTranslation(Model $model, string $locale): ?array
    {
        $translations = $model->translations ?? [];
        return $translations[$locale] ?? null;
    }

    /**
     * Check if cached translation is stale
     * Translation is stale if model was updated after translation was created
     */
    protected function isTranslationStale(Model $model, string $locale): bool
    {
        $translations = $model->translations ?? [];
        $localeData = $translations[$locale] ?? null;

        if (!$localeData || !isset($localeData['translated_at'])) {
            return true; // No translation or no timestamp = stale
        }

        $translatedAt = Carbon::parse($localeData['translated_at']);
        $updatedAt = $model->updated_at;

        return $updatedAt->gt($translatedAt);
    }

    /**
     * Cache translation in model's translations column
     */
    protected function cacheTranslation(Model $model, string $locale, array $translatedData): void
    {
        $translations = $model->translations ?? [];
        
        $translations[$locale] = array_merge($translatedData, [
            'translated_at' => Carbon::now()->toDateTimeString(),
        ]);

        $model->translations = $translations;
        $model->saveQuietly(); // Save without triggering observers
    }

    /**
     * Collect texts from model fields
     */
    protected function collectTextsFromModel(Model $model, array $fields): array
    {
        $texts = [];
        
        foreach ($fields as $field) {
            $value = $model->getAttribute($field);
            if (!empty($value) && is_string($value)) {
                $texts[$field] = $value;
            }
        }

        return $texts;
    }

    /**
     * Call Google Cloud Translation API
     * 
     * @param array $texts Array of texts to translate
     * @param string $targetLanguage Target language code
     * @return array Translated texts in same order
     */
    protected function callGoogleTranslateAPI(array $texts, string $targetLanguage): array
    {
        if (empty($this->apiKey)) {
            Log::error("GoogleTranslationService: API key not configured");
            return [];
        }

        // Handle both associative and sequential arrays
        $textValues = array_values($texts);
        
        if (empty($textValues)) {
            return [];
        }

        try {
            // Batch texts in chunks of 128 (Google API limit)
            $chunks = array_chunk($textValues, 128);
            $allTranslations = [];

            foreach ($chunks as $chunk) {
                // Add timeout configuration
                $response = Http::timeout(30)->post($this->apiEndpoint, [
                    'key' => $this->apiKey,
                    'q' => $chunk,
                    'target' => $targetLanguage,
                    'source' => 'en',
                    'format' => 'text',
                ]);

                if (!$response->successful()) {
                    Log::error("GoogleTranslationService: API error", [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    continue;
                }

                $data = $response->json();
                $translations = $data['data']['translations'] ?? [];

                foreach ($translations as $translation) {
                    $allTranslations[] = $translation['translatedText'] ?? '';
                }
            }

            return $allTranslations;

        } catch (\Exception $e) {
            Log::error("GoogleTranslationService: Exception during API call", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    /**
     * Map translations back to field names
     */
    protected function mapTranslationsToFields(array $originalTexts, array $translations, string $targetLocale): array
    {
        $result = [];
        $keys = array_keys($originalTexts);
        $suffix = '_' . $targetLocale;

        foreach ($keys as $index => $field) {
            if (isset($translations[$index])) {
                $result[$field . $suffix] = $translations[$index];
            }
        }

        return $result;
    }

    /**
     * Invalidate translations for a model
     * Called when model is updated to clear stale translations
     */
    public function invalidateTranslations(Model $model): void
    {
        $model->translations = null;
        $model->saveQuietly();
        
        Log::info("GoogleTranslationService: Invalidated translations for " . get_class($model) . " ID {$model->id}");
    }

    /**
     * Invalidate specific locale translation
     */
    public function invalidateLocale(Model $model, string $locale): void
    {
        $translations = $model->translations ?? [];
        
        if (isset($translations[$locale])) {
            unset($translations[$locale]);
            $model->translations = $translations;
            $model->saveQuietly();
            
            Log::info("GoogleTranslationService: Invalidated {$locale} translation for " . get_class($model) . " ID {$model->id}");
        }
    }

    /**
     * Check if API is configured and available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }
}
