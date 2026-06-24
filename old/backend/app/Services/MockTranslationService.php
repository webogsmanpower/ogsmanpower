<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

/**
 * MockTranslationService
 * 
 * Mock translation service for testing when Google Translation API is unavailable.
 * Simulates translation behavior with realistic mock translations for supported languages.
 * 
 * This service:
 * 1. Provides mock translations for common CV fields
 * 2. Maintains the same interface as GoogleTranslationService
 * 3. Supports caching strategy identical to the real service
 * 4. Returns realistic mock translations for testing purposes
 */
class MockTranslationService
{
    /**
     * Supported target languages (same as Google service)
     */
    protected array $supportedLanguages = ['ar', 'ur', 'hi', 'fr', 'es', 'zh', 'bn', 'tl'];

    /**
     * Mock translation dictionary for common CV terms
     * Key: english text, Value: array of translations by language code
     */
    protected array $mockTranslations = [
        // Professional terms
        'Software Engineer' => [
            'ar' => 'مهندس برمجيات',
            'ur' => 'سافٹ ویئر انجینئر',
            'hi' => 'सॉफ्टवेयर इंजीनियर',
            'fr' => 'Ingénieur logiciel',
            'es' => 'Ingeniero de software',
            'zh' => '软件工程师',
            'bn' => 'সফ্টওয়্যার ইঞ্জিনিয়ার',
            'tl' => 'Software Engineer',
        ],
        'Project Manager' => [
            'ar' => 'مدير مشروع',
            'ur' => 'پروجیکٹ مینیجر',
            'hi' => 'प्रोजेक्ट मैनेजर',
            'fr' => 'Chef de projet',
            'es' => 'Gerente de proyecto',
            'zh' => '项目经理',
            'bn' => 'প্রকল্প ব্যবস্থাপক',
            'tl' => 'Project Manager',
        ],
        'Marketing Manager' => [
            'ar' => 'مدير التسويق',
            'ur' => 'مارکیٹنگ مینیجر',
            'hi' => 'मार्केटिंग मैनेजर',
            'fr' => 'Responsable marketing',
            'es' => 'Gerente de marketing',
            'zh' => '营销经理',
            'bn' => 'মার্কেটিং ব্যবস্থাপক',
            'tl' => 'Marketing Manager',
        ],
        'Accountant' => [
            'ar' => 'محاسب',
            'ur' => 'اکاؤنٹنٹ',
            'hi' => 'लेखाकार',
            'fr' => 'Comptable',
            'es' => 'Contador',
            'zh' => '会计师',
            'bn' => 'হিসাবরক্ষক',
            'tl' => 'Accountant',
        ],
        'Teacher' => [
            'ar' => 'معلم',
            'ur' => 'استاد',
            'hi' => 'शिक्षक',
            'fr' => 'Enseignant',
            'es' => 'Profesor',
            'zh' => '教师',
            'bn' => 'শিক্ষক',
            'tl' => 'Guro',
        ],
        'Driver' => [
            'ar' => 'سائق',
            'ur' => 'ڈرائیور',
            'hi' => 'ड्राइवर',
            'fr' => 'Chauffeur',
            'es' => 'Conductor',
            'zh' => '司机',
            'bn' => 'চালক',
            'tl' => 'Driver',
        ],
        'Security Guard' => [
            'ar' => 'حارس أمن',
            'ur' => 'سیکیورٹی گارڈ',
            'hi' => 'सुरक्षा गार्ड',
            'fr' => 'Garde de sécurité',
            'es' => 'Guardia de seguridad',
            'zh' => '保安',
            'bn' => 'নিরাপত্তা প্রহরী',
            'tl' => 'Security Guard',
        ],
        'Domestic Worker' => [
            'ar' => 'عامل منزلي',
            'ur' => 'گھریلی ملازم',
            'hi' => 'घरेलू कार्यकर्ता',
            'fr' => 'Travailleur domestique',
            'es' => 'Trabajador doméstico',
            'zh' => '家政工人',
            'bn' => 'গার্হস্থ্য কর্মী',
            'tl' => 'Domestic Worker',
        ],
        
        // Skills
        'Communication' => [
            'ar' => 'التواصل',
            'ur' => 'مواصلات',
            'hi' => 'संचार',
            'fr' => 'Communication',
            'es' => 'Comunicación',
            'zh' => '沟通',
            'bn' => 'যোগাযোগ',
            'tl' => 'Komunikasyon',
        ],
        'Leadership' => [
            'ar' => 'القيادة',
            'ur' => 'قیادت',
            'hi' => 'नेतृत्व',
            'fr' => 'Leadership',
            'es' => 'Liderazgo',
            'zh' => '领导力',
            'bn' => 'নেতৃত্ব',
            'tl' => 'Pagiging lider',
        ],
        'Problem Solving' => [
            'ar' => 'حل المشكلات',
            'ur' => 'مسئلہ حل کرنا',
            'hi' => 'समस्या समाधान',
            'fr' => 'Résolution de problèmes',
            'es' => 'Resolución de problemas',
            'zh' => '解决问题',
            'bn' => 'সমস্যা সমাধান',
            'tl' => 'Problem Solving',
        ],
        'Team Work' => [
            'ar' => 'العمل الجماعي',
            'ur' => 'ٹیم ورک',
            'hi' => 'टीम वर्क',
            'fr' => 'Travail d\'équipe',
            'es' => 'Trabajo en equipo',
            'zh' => '团队合作',
            'bn' => 'দলবদ্ধতা',
            'tl' => 'Team Work',
        ],
        
        // Education
        'Bachelor' => [
            'ar' => 'بكالوريوس',
            'ur' => 'بیچلر',
            'hi' => 'स्नातक',
            'fr' => 'Licence',
            'es' => 'Licenciatura',
            'zh' => '学士',
            'bn' => 'স্নাতক',
            'tl' => 'Bachelor',
        ],
        'Master' => [
            'ar' => 'ماجستير',
            'ur' => 'ماسٹر',
            'hi' => 'स्नातकोत्तर',
            'fr' => 'Master',
            'es' => 'Maestría',
            'zh' => '硕士',
            'bn' => 'স্নাতকোত্তর',
            'tl' => 'Master',
        ],
        'PhD' => [
            'ar' => 'دكتوراه',
            'ur' => 'پی ایچ ڈی',
            'hi' => 'पीएचडी',
            'fr' => 'Doctorat',
            'es' => 'Doctorado',
            'zh' => '博士',
            'bn' => 'পিএইচডি',
            'tl' => 'PhD',
        ],
        'University' => [
            'ar' => 'جامعة',
            'ur' => 'یونیورسٹی',
            'hi' => 'विश्वविद्यालय',
            'fr' => 'Université',
            'es' => 'Universidad',
            'zh' => '大学',
            'bn' => 'বিশ্ববিদ্যালয়',
            'tl' => 'University',
        ],
        
        // Common descriptions
        'Experienced professional' => [
            'ar' => 'محترف ذو خبرة',
            'ur' => 'تجربہ کار پروفیشنل',
            'hi' => 'अनुभवी पेशेवर',
            'fr' => 'Professionnel expérimenté',
            'es' => 'Profesional experimentado',
            'zh' => '经验丰富的专业人士',
            'bn' => 'অভিজ্ঞ পেশাদার',
            'tl' => 'Experienced professional',
        ],
        'Dedicated and hardworking' => [
            'ar' => 'مكرس ومجتهد',
            'ur' => 'مخلص اور سخت محنت',
            'hi' => 'समर्पित और परिश्रमी',
            'fr' => 'Dédié et travailleur',
            'es' => 'Dedicado y trabajador',
            'zh' => '敬业和勤奋',
            'bn' => 'নিবেদিত এবং পরিশ্রমী',
            'tl' => 'Dedicated and hardworking',
        ],
        'Strong communication skills' => [
            'ar' => 'مهارات تواصل قوية',
            'ur' => 'مضبوط مواصلات کی مہارت',
            'hi' => 'मजबूत संचार कौशल',
            'fr' => 'Fortes compétences en communication',
            'es' => 'Fuertes habilidades de comunicación',
            'zh' => '强大的沟通技巧',
            'bn' => 'শক্তিশালী যোগাযোগ দক্ষতা',
            'tl' => 'Strong communication skills',
        ],
    ];

    /**
     * Mock translation for any text (fallback when not in dictionary)
     * This simulates translation by adding language-specific prefixes/suffixes
     */
    public function generateMockTranslation(string $text, string $language): string
    {
        $prefixes = [
            'ar' => '[AR] ',
            'ur' => '[UR] ',
            'hi' => '[HI] ',
            'fr' => '[FR] ',
            'es' => '[ES] ',
            'zh' => '[ZH] ',
            'bn' => '[BN] ',
            'tl' => '[TL] ',
        ];

        return ($prefixes[$language] ?? '[MOCK] ') . $text;
    }

    /**
     * Translate a model's fields to target language (mock implementation)
     */
    public function translateModel(Model $model, string $targetLocale, array $fields = []): array
    {
        // Validate target locale
        if (!in_array($targetLocale, $this->supportedLanguages)) {
            Log::warning("MockTranslationService: Unsupported language {$targetLocale}");
            return [];
        }

        // Check cache first (same logic as real service)
        $cachedTranslation = $this->getCachedTranslation($model, $targetLocale);
        
        if ($cachedTranslation && !$this->isTranslationStale($model, $targetLocale)) {
            Log::info("MockTranslationService: Using cached translation for {$targetLocale}");
            return $cachedTranslation;
        }

        // Need fresh translation
        Log::info("MockTranslationService: Generating mock translation for {$targetLocale}");
        
        // Collect texts to translate
        $textsToTranslate = $this->collectTextsFromModel($model, $fields);
        
        if (empty($textsToTranslate)) {
            Log::info("MockTranslationService: No texts to translate");
            return [];
        }

        // Generate mock translations
        $translations = $this->generateMockTranslations($textsToTranslate, $targetLocale);
        
        if (empty($translations)) {
            Log::error("MockTranslationService: Failed to generate translations");
            return [];
        }

        // Map translations back to fields
        $translatedData = $this->mapTranslationsToFields($textsToTranslate, $translations, $targetLocale);

        // Cache the translation
        $this->cacheTranslation($model, $targetLocale, $translatedData);

        return $translatedData;
    }

    /**
     * Translate a SeekerResume with all its nested JSON fields (mock implementation)
     */
    public function translateResume(\App\Models\SeekerResume $resume, string $targetLocale): array
    {
        // Validate target locale
        if (!in_array($targetLocale, $this->supportedLanguages)) {
            Log::warning("MockTranslationService: Unsupported language {$targetLocale}");
            return [];
        }

        // Check cache first
        $cachedTranslation = $this->getCachedTranslation($resume, $targetLocale);
        
        if ($cachedTranslation && !$this->isTranslationStale($resume, $targetLocale)) {
            Log::info("MockTranslationService: Using cached resume translation for {$targetLocale}");
            return $cachedTranslation;
        }

        Log::info("MockTranslationService: Generating mock resume translation ID {$resume->id} to {$targetLocale}");

        $translatedData = [];
        $allTextsToTranslate = [];
        $fieldMapping = []; // Maps index to field path

        // Collect all texts from resume sections (same as real service)
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

            $sectionFields = $this->getTranslatableFieldsForSection($sectionName);
            
            // Handle both object and array formats
            if (is_array($sectionData)) {
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
            Log::info("MockTranslationService: No texts to translate in resume");
            return [];
        }

        // Generate mock translations
        $translations = $this->generateMockTranslations($allTextsToTranslate, $targetLocale);

        if (empty($translations)) {
            Log::error("MockTranslationService: Failed to generate mock translations for resume");
            return [];
        }

        // Map translations back to structure
        $translatedData = $this->mapTranslationsToResumeStructure($fieldMapping, $translations, $sections, $targetLocale);

        // Cache the translation
        $this->cacheTranslation($resume, $targetLocale, $translatedData);

        return $translatedData;
    }

    /**
     * Generate mock translations for texts
     */
    protected function generateMockTranslations(array $texts, string $targetLanguage): array
    {
        $translations = [];
        
        foreach ($texts as $text) {
            if (isset($this->mockTranslations[$text][$targetLanguage])) {
                // Use predefined mock translation
                $translations[] = $this->mockTranslations[$text][$targetLanguage];
            } else {
                // Generate generic mock translation
                $translations[] = $this->generateMockTranslation($text, $targetLanguage);
            }
        }

        return $translations;
    }

    /**
     * Get translatable fields for a section (simplified version)
     */
    protected function getTranslatableFieldsForSection(string $sectionName): array
    {
        $fields = [
            'professional_summary' => ['professional_summary', 'career_objective', 'industry_experience'],
            'work_experience' => ['job_title', 'role_title', 'company_name', 'location', 'description', 'responsibilities'],
            'education' => ['degree', 'degree_title', 'institution', 'institution_name', 'field_of_study', 'description'],
            'skills' => ['name', 'skill_name', 'primary_skill', 'description'],
            'languages' => ['language', 'language_name'],
            'certifications' => ['certification_name', 'issuer', 'description'],
            'references' => ['name', 'job_title', 'company_name', 'relationship'],
        ];

        return $fields[$sectionName] ?? [];
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
     */
    public function invalidateTranslations(Model $model): void
    {
        $model->translations = null;
        $model->saveQuietly();
        
        Log::info("MockTranslationService: Invalidated translations for " . get_class($model) . " ID {$model->id}");
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
            
            Log::info("MockTranslationService: Invalidated {$locale} translation for " . get_class($model) . " ID {$model->id}");
        }
    }

    /**
     * Check if service is available (always true for mock)
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): array
    {
        return $this->supportedLanguages;
    }
}
