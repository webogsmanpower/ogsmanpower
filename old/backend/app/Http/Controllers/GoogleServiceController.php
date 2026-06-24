<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleServiceController extends Controller
{
    // ==========================================
    // 1. TRANSLATION FUNCTION (Text -> Text)
    // ==========================================
    public function translate(Request $request) 
    {
        $request->validate([
            'text' => 'required|string',
            'target_lang' => 'nullable|string|size:2'
        ]);

        $apiKey = env('GOOGLE_TRANSLATE_API_KEY');
        $targetLang = $request->input('target_lang', 'en');

        $response = Http::post("https://translation.googleapis.com/language/translate/v2?key={$apiKey}", [
            'q' => $request->input('text'),
            'target' => $targetLang
        ]);

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'translated_text' => $response->json('data.translations.0.translatedText')
            ]);
        }

        return response()->json(['error' => 'Translation failed'], 500);
    }

    // ==========================================
    // 2. VISION FUNCTION (Image -> Data)
    // ==========================================
    public function analyzeImage(Request $request)
    {
        // Validate that an image file was uploaded
        $request->validate([
            'image' => 'required|image|max:5120', // Max 5MB
        ]);

        try {
            // Encode image to Base64 (Required for API Key method)
            $imagePath = $request->file('image')->getRealPath();
            $imageContent = file_get_contents($imagePath);
            $base64Image = base64_encode($imageContent);

            // Get Key and Prepare URL
            $apiKey = env('GOOGLE_VISION_API_KEY'); // Make sure this is in your .env
            $url = "https://vision.googleapis.com/v1/images:annotate?key={$apiKey}";

            // Send Request to Google
            $response = Http::post($url, [
                "requests" => [
                    [
                        "image" => [
                            "content" => $base64Image
                        ],
                        "features" => [
                            [
                                "type" => "LABEL_DETECTION", // Change to TEXT_DETECTION to read text from images
                                "maxResults" => 10
                            ]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('responses.0.labelAnnotations')
                ]);
            }

            return response()->json(['error' => 'Vision API failed', 'details' => $response->json()], 500);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}