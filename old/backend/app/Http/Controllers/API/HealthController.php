<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Health Check Controller
 * 
 * Provides endpoints for monitoring application health and status.
 */
class HealthController extends Controller
{
    /**
     * Basic health check endpoint.
     * 
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'service' => 'OGS Manpower API',
        ]);
    }

    /**
     * Detailed health check with database connectivity.
     * 
     * @return JsonResponse
     */
    public function detailed(): JsonResponse
    {
        $dbStatus = 'ok';
        $dbMessage = 'Connected';
        
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'error';
            $dbMessage = 'Database connection failed';
        }

        return response()->json([
            'status' => $dbStatus === 'ok' ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => [
                'database' => [
                    'status' => $dbStatus,
                    'message' => $dbMessage,
                ],
                'storage' => [
                    'status' => is_writable(storage_path('app')) ? 'ok' : 'error',
                    'message' => is_writable(storage_path('app')) ? 'Writable' : 'Not writable',
                ],
            ],
        ]);
    }
}
