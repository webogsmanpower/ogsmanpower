<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployerResource;
use App\Mail\EmailVerificationCode;
use App\Models\Employer;
use App\Models\EmailVerification;
use App\Models\User;
use App\Services\EmployerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

/**
 * EmployerController
 * 
 * Handles employer profile management.
 */
class EmployerController extends Controller
{
    public function __construct(
        protected EmployerService $employerService
    ) {}

    /**
     * Register a new employer (PUBLIC endpoint).
     * 
     * Creates User + Employer in one atomic transaction.
     * Accepts FormData with logo and registration document uploads.
     * 
     * STRICT ROLE ENFORCEMENT:
     * - Email must be unique across ALL roles (seeker, employer, agent, etc.)
     * - One email = One role. No switching allowed.
     * 
     * 3-Step Wizard Flow:
     * Step 1: Account Credentials (email, password) - NO username
     * Step 2: Company Details (company_name, company_email, country, phone, industry, size, website)
     * Step 3: Legal/Verification (registration_number, license_number, registration_document, logo)
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Step 1: Account Credentials (Login)
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            
            // Step 2: Company Details
            'company_name' => 'required|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:30|unique:employers,company_phone',
            'country' => 'required|string|max:100',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|in:1-10,11-50,51-200,201-500,500+',
            'website' => 'nullable|url|max:255',
            
            // Step 3: Legal/Verification
            'registration_number' => 'nullable|string|max:100',
            'license_number' => 'nullable|string|max:100',
            'registration_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'email.unique' => 'This email is already registered. Please use a different email or sign in.',
            'company_phone.unique' => 'This phone number is already registered. Please use a different phone number.',
        ]);

        if ($validator->fails()) {
            Log::info('Employer registration validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);
            
            return response()->json([
                'message' => 'Please correct the errors below.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create User (no username - login via email only)
            // Note: Password is auto-hashed by User model cast, don't use Hash::make()
            $user = User::create([
                'name' => $request->company_name, // Use company name as display name
                'email' => $request->email,
                'password' => $request->password,
                'role' => 'employer',
                'is_onboarding_completed' => true,
            ]);

            // Assign Spatie role for permissions
            $user->assignRole('employer_owner');

            // Handle logo upload
            $logoPath = null;
            if ($request->hasFile('company_logo')) {
                $logoPath = $request->file('company_logo')->store('employers/logos', 'public');
            }

            // Handle registration document upload
            $documentPath = null;
            if ($request->hasFile('registration_document')) {
                $documentPath = $request->file('registration_document')->store('employers/documents', 'public');
            }

            // Create Employer profile with all verification fields
            $employer = Employer::create([
                'user_id' => $user->id,
                'company_name' => $request->company_name,
                'company_email' => $request->company_email,
                'company_phone' => $request->company_phone,
                'country' => $request->country,
                'industry' => $request->industry,
                'company_size' => $request->company_size,
                'website' => $request->website,
                'registration_number' => $request->registration_number,
                'license_number' => $request->license_number,
                'registration_document_path' => $documentPath,
                'logo_path' => $logoPath,
                'is_verified' => false, // Requires admin verification
            ]);

            DB::commit();

            // Generate auth token
            $token = $user->createToken('auth_token')->plainTextToken;

            // Create and send email verification
            $verification = EmailVerification::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                'expires_at' => now()->addMinutes(30),
            ]);

            // Queue email to prevent blocking
            dispatch(function () use ($user, $verification) {
                Mail::to($user->email)->send(new EmailVerificationCode($verification));
            });

            // Build URLs for immediate frontend use
            $logoUrl = $logoPath 
                ? config('app.url') . Storage::url($logoPath)
                : null;

            return response()->json([
                'message' => 'Employer registered successfully. Please check your email for verification code.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'email_verified_at' => $user->email_verified_at,
                    ],
                    'employer' => new EmployerResource($employer),
                    'logo_url' => $logoUrl,
                    'requires_verification' => !$user->hasVerifiedEmail(),
                ],
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Log the actual error for debugging
            Log::error('Employer registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['password', 'password_confirmation'])
            ]);
            
            // Return a more specific error message if possible
            $errorMessage = 'We could not create your account right now. Please review the details and try again.';
            
            // Check for common database errors
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                if (str_contains($e->getMessage(), 'email')) {
                    $errorMessage = 'This email is already registered. Please use a different email or sign in.';
                } elseif (str_contains($e->getMessage(), 'company_phone')) {
                    $errorMessage = 'This phone number is already registered. Please use a different phone number.';
                }
            }
            
            // Clean up uploaded files if transaction failed
            if (isset($logoPath) && $logoPath) {
                Storage::disk('public')->delete($logoPath);
            }
            if (isset($documentPath) && $documentPath) {
                Storage::disk('public')->delete($documentPath);
            }
            
            return response()->json([
                'message' => $errorMessage,
                'error' => config('app.debug') ? $e->getMessage() : null,
                'debug_trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    /**
     * Get current employer profile.
     */
    public function show(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json([
                'message' => 'Employer profile not found',
            ], 404);
        }

        return response()->json([
            'data' => new EmployerResource($employer->load(['teamMembers.user'])),
        ]);
    }

    /**
     * Update employer profile.
     */
    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'sometimes|string|max:255',
            'company_name_ar' => 'nullable|string|max:255',
            'trade_license_number' => 'nullable|string|max:100',
            'company_type' => 'nullable|in:sole_proprietor,partnership,corporation,government,ngo,other',
            'industry' => 'nullable|string|max:100',
            'company_size' => 'nullable|in:1-10,11-50,51-200,201-500,500+',
            'country' => 'sometimes|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:2000',
            'description_ar' => 'nullable|string|max:2000',
            'social_links' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json([
                'message' => 'Employer profile not found',
            ], 404);
        }

        $employer = $this->employerService->updateEmployer($employer, $validator->validated());

        return response()->json([
            'message' => 'Employer profile updated successfully',
            'data' => new EmployerResource($employer),
        ]);
    }

    /**
     * Upload employer logo.
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json([
                'message' => 'Employer profile not found',
            ], 404);
        }

        $logoPath = $this->employerService->uploadLogo($employer, $request->file('logo'));

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'data' => [
                'logo_path' => $logoPath,
                'logo_url' => $employer->fresh()->logo_url,
            ],
        ]);
    }

    /**
     * Get dashboard statistics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $employer = $this->employerService->getEmployerForUser($request->user());

        if (!$employer) {
            return response()->json([
                'message' => 'Employer profile not found',
            ], 404);
        }

        $stats = $this->employerService->getDashboardStats($employer);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Check if email is available for registration.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid email format',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            return response()->json([
                'available' => false,
                'message' => 'Email already registered',
                'role' => $user->role,
            ], 200);
        }

        return response()->json([
            'available' => true,
            'message' => 'Email available for registration',
        ]);
    }
}
