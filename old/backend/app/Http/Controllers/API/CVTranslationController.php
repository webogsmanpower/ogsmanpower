<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Seeker;
use App\Models\SeekerResume;
use App\Services\GoogleTranslationService;
use App\Services\MockTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * CVTranslationController
 * 
 * Handles CV translation requests for bilingual CV generation.
 * Integrates with GoogleTranslationService or MockTranslationService for server-side translations
 * with caching to minimize API costs.
 * 
 * Endpoint: POST /api/seeker/cv/translate
 * Payload: { "target_language": "ar" }
 */
class CVTranslationController extends Controller
{
    protected GoogleTranslationService $googleTranslationService;
    protected MockTranslationService $mockTranslationService;

    public function __construct(
        GoogleTranslationService $googleTranslationService,
        MockTranslationService $mockTranslationService
    ) {
        $this->googleTranslationService = $googleTranslationService;
        $this->mockTranslationService = $mockTranslationService;
    }

    /**
     * Get the appropriate translation service based on configuration
     */
    protected function getTranslationService()
    {
        // Use mock service if Google API is not available or if explicitly configured
        if (!$this->googleTranslationService->isAvailable() || config('services.translation.use_mock', false)) {
            Log::info("CVTranslationController: Using MockTranslationService");
            return $this->mockTranslationService;
        }
        
        Log::info("CVTranslationController: Using GoogleTranslationService");
        return $this->googleTranslationService;
    }

    /**
     * Translate CV data for bilingual generation
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function translate(Request $request): JsonResponse
    {
        $request->validate([
            'target_language' => 'required|string|in:ar,ur,hi,fr,es,zh,bn,tl',
        ]);

        $targetLanguage = $request->input('target_language');
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Get the appropriate translation service
        $translationService = $this->getTranslationService();

        // Check if translation service is available
        if (!$translationService->isAvailable()) {
            Log::warning("CVTranslationController: Translation service not available");
            return response()->json([
                'success' => false,
                'message' => 'Translation service is not configured. Please contact support.',
                'error_code' => 'TRANSLATION_SERVICE_UNAVAILABLE',
            ], 503);
        }

        try {
            // Get seeker and resume data
            $seeker = Seeker::where('user_id', $user->id)->first();
            $resume = SeekerResume::where('user_id', $user->id)->first();

            if (!$seeker && !$resume) {
                return response()->json([
                    'success' => false,
                    'message' => 'No profile data found. Please complete your profile first.',
                ], 404);
            }

            $translatedData = [
                'target_language' => $targetLanguage,
                'seeker' => null,
                'resume' => null,
            ];

            // Translate seeker profile if exists
            if ($seeker) {
                $seekerTranslations = $translationService->translateModel(
                    $seeker,
                    $targetLanguage,
                    ['bio', 'headline']
                );
                $translatedData['seeker'] = $seekerTranslations;
            }

            // Translate resume if exists
            if ($resume) {
                $resumeTranslations = $translationService->translateResume(
                    $resume,
                    $targetLanguage
                );
                $translatedData['resume'] = $resumeTranslations;
            }

            // Build complete translated profile for frontend
            $completeProfile = $this->buildTranslatedProfile(
                $seeker,
                $resume,
                $translatedData,
                $targetLanguage
            );

            return response()->json([
                'success' => true,
                'message' => 'Translation completed successfully',
                'data' => $completeProfile,
                'meta' => [
                    'target_language' => $targetLanguage,
                    'cached' => $this->wasFromCache($seeker, $resume, $targetLanguage),
                    'translated_at' => now()->toDateTimeString(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error("CVTranslationController: Translation failed", [
                'user_id' => $user->id,
                'target_language' => $targetLanguage,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Translation failed. Please try again later.',
                'error_code' => 'TRANSLATION_FAILED',
            ], 500);
        }
    }

    /**
     * Build complete translated profile merging original and translated data
     * 
     * @param Seeker|null $seeker
     * @param SeekerResume|null $resume
     * @param array $translatedData
     * @param string $targetLanguage
     * @return array
     */
    protected function buildTranslatedProfile(
        ?Seeker $seeker,
        ?SeekerResume $resume,
        array $translatedData,
        string $targetLanguage
    ): array {
        $profile = [
            'original' => [],
            'translated' => [],
            'merged' => [], // Combined data with _ar suffixed fields
        ];

        // Build original data
        if ($seeker) {
            $profile['original']['seeker'] = [
                'bio' => $seeker->bio,
                'headline' => $seeker->headline,
                'first_name' => $seeker->first_name,
                'last_name' => $seeker->last_name,
            ];
        }

        if ($resume) {
            $profile['original']['basic_information'] = $resume->basic_information;
            $profile['original']['professional_summary'] = $resume->professional_summary;
            $profile['original']['work_experience'] = $resume->work_experience;
            $profile['original']['education'] = $resume->education;
            $profile['original']['skills'] = $resume->skills;
            $profile['original']['languages'] = $resume->languages;
            $profile['original']['certifications'] = $resume->certifications;
            $profile['original']['references'] = $resume->references;
        }

        // Add translated data
        $profile['translated'] = $translatedData;

        // Build merged data (original + translated fields with _ar suffix)
        $profile['merged'] = $this->mergeTranslations(
            $profile['original'],
            $translatedData,
            $targetLanguage
        );

        return $profile;
    }

    /**
     * Merge original data with translations
     * Adds translated fields with language suffix (e.g., bio_ar)
     */
    protected function mergeTranslations(array $original, array $translated, string $lang): array
    {
        $merged = $original;
        $suffix = '_' . $lang;

        // Merge seeker translations
        if (!empty($translated['seeker']) && isset($merged['seeker'])) {
            foreach ($translated['seeker'] as $key => $value) {
                // Key already has _ar suffix from service
                $merged['seeker'][$key] = $value;
            }
        }

        // Merge resume translations
        if (!empty($translated['resume'])) {
            foreach ($translated['resume'] as $section => $sectionData) {
                if (!isset($merged[$section])) {
                    $merged[$section] = [];
                }

                if (is_array($sectionData)) {
                    // Check if it's indexed array (multiple items) or associative (single object)
                    if ($this->isSequentialArray($sectionData)) {
                        // Array of items (work_experience, education, etc.)
                        foreach ($sectionData as $index => $itemTranslations) {
                            if (is_array($merged[$section]) && isset($merged[$section][$index])) {
                                $merged[$section][$index] = array_merge(
                                    $merged[$section][$index],
                                    $itemTranslations
                                );
                            }
                        }
                    } else {
                        // Single object (professional_summary, basic_information)
                        $merged[$section] = array_merge(
                            $merged[$section] ?? [],
                            $sectionData
                        );
                    }
                }
            }
        }

        return $merged;
    }

    /**
     * Check if array is sequential (indexed)
     */
    protected function isSequentialArray(array $arr): bool
    {
        if (empty($arr)) return false;
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * Check if translation was served from cache
     */
    protected function wasFromCache(?Seeker $seeker, ?SeekerResume $resume, string $locale): bool
    {
        $seekerCached = false;
        $resumeCached = false;

        if ($seeker) {
            $translations = $seeker->translations ?? [];
            $seekerCached = isset($translations[$locale]['translated_at']);
        }

        if ($resume) {
            $translations = $resume->translations ?? [];
            $resumeCached = isset($translations[$locale]['translated_at']);
        }

        return $seekerCached || $resumeCached;
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): JsonResponse
    {
        $translationService = $this->getTranslationService();
        
        return response()->json([
            'success' => true,
            'data' => [
                'languages' => $translationService->getSupportedLanguages(),
                'service_available' => $translationService->isAvailable(),
                'service_type' => $translationService instanceof MockTranslationService ? 'mock' : 'google',
            ],
        ]);
    }

    /**
     * Invalidate cached translations (force re-translation)
     */
    public function invalidateCache(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $locale = $request->input('locale'); // Optional: specific locale to invalidate

        try {
            $seeker = Seeker::where('user_id', $user->id)->first();
            $resume = SeekerResume::where('user_id', $user->id)->first();
            
            $translationService = $this->getTranslationService();

            if ($seeker) {
                if ($locale) {
                    $translationService->invalidateLocale($seeker, $locale);
                } else {
                    $translationService->invalidateTranslations($seeker);
                }
            }

            if ($resume) {
                if ($locale) {
                    $translationService->invalidateLocale($resume, $locale);
                } else {
                    $translationService->invalidateTranslations($resume);
                }
            }

            return response()->json([
                'success' => true,
                'message' => $locale 
                    ? "Translations for '{$locale}' invalidated successfully"
                    : 'All translations invalidated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error("CVTranslationController: Cache invalidation failed", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to invalidate cache',
            ], 500);
        }
    }
}
