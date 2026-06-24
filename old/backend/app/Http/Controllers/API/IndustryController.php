<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class IndustryController extends Controller
{
    /**
     * Get list of custom industries from database only
     */
    public function index(Request $request): JsonResponse
    {
        $industries = Industry::orderBy('name', 'asc')
            ->get(['id', 'name', 'created_by']);
        
        return response()->json([
            'success' => true,
            'data' => $industries,
        ]);
    }

    /**
     * Create a new custom industry
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:100|unique:industries,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $industry = Industry::findOrCreateByName(
            $request->input('name'),
            auth()->id()
        );

        return response()->json([
            'success' => true,
            'message' => $industry->wasRecentlyCreated ? 'Industry created successfully' : 'Industry already exists',
            'data' => $industry,
        ], $industry->wasRecentlyCreated ? 201 : 200);
    }
}
