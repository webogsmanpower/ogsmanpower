<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Webhook\StripeWebhookController;

Route::get('/', function () {
    return view('welcome');
});

// Add login route for authentication middleware
Route::get('/login', function () {
    return response()->json(['message' => 'Authentication required'], 401);
})->name('login');

// Route to serve storage images with CORS headers
Route::get('/storage/resume_uploads/{filename}', function ($filename) {
    $path = storage_path('app/public/resume_uploads/' . $filename);
    
    if (!file_exists($path)) {
        abort(404);
    }
    
    $file = file_get_contents($path);
    $mimeType = mime_content_type($path);
    
    return response($file)
        ->header('Content-Type', $mimeType)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET')
        ->header('Access-Control-Allow-Headers', 'Content-Type');
})->where('filename', '.*');

// Also handle general storage files with nested paths
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    
    if (!file_exists($fullPath)) {
        abort(404);
    }
    
    $file = file_get_contents($fullPath);
    $mimeType = mime_content_type($fullPath);
    
    return response($file)
        ->header('Content-Type', $mimeType)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET')
        ->header('Access-Control-Allow-Headers', 'Content-Type');
})->where('path', '.*');

// Stripe webhook endpoint (must be accessible from Stripe)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
