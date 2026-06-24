<?php

/**
 * API Routes
 * 
 * All routes are prefixed with /api and use JSON responses.
 * Rate limiting is applied to prevent abuse:
 * - Auth routes: 10 requests/minute for login/register
 * - General API: 60 requests/minute for authenticated users
 * 
 * @package Routes
 */


use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ContractController;
use App\Http\Controllers\API\OnboardingController;
use App\Http\Controllers\API\ResumeController;
use App\Http\Controllers\API\EmployerController;
use App\Http\Controllers\API\EmployerTeamController;
use App\Http\Controllers\API\JobPostingController;
use App\Http\Controllers\API\PublicJobController;
use App\Http\Resources\UserResource;
use App\Http\Controllers\API\ApplicationController;
use App\Http\Controllers\API\SeekerApplicationController;
use App\Http\Controllers\API\InterviewController;
use App\Http\Controllers\API\VisaStatusController;
use App\Http\Controllers\API\VisaDocumentController;
use App\Http\Controllers\API\JobAnalyticsController;
use App\Http\Controllers\API\CandidateController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\ActionRequestController;
use App\Http\Controllers\API\Admin\AdminPlanController;
use App\Http\Controllers\API\Admin\AdminRBACController;
use App\Http\Controllers\API\CandidateNoteController;
use App\Http\Controllers\API\SeekerActionRequestController;
use App\Http\Controllers\API\AdminAuthController;
use App\Http\Controllers\API\AdminDashboardController;
use App\Http\Controllers\API\AdminVerificationController;
use App\Http\Controllers\API\AdminSeekerController;
use App\Http\Controllers\API\AdminJobController;
use App\Http\Controllers\API\AdminSearchController;
use App\Http\Controllers\API\AdminImpersonationController;
use App\Http\Controllers\API\ProfileImageController;
use App\Http\Controllers\API\SeekerProfileController;
use App\Http\Controllers\API\FormSchemaController;
use App\Http\Controllers\GoogleServiceController;
use App\Http\Controllers\API\CVTranslationController;
use App\Http\Controllers\API\SkillController;
use App\Http\Controllers\API\Admin\AdminSkillController;
use App\Http\Controllers\API\Admin\AdminAssessmentController;
use App\Http\Controllers\SendGridController;
use App\Http\Controllers\API\VisaWorkflowController;
use App\Http\Controllers\API\Admin\AdminSettingsController;
use App\Http\Controllers\API\Admin\AdminFormSchemaController;
use App\Http\Controllers\API\BillingController;
use App\Http\Controllers\API\Admin\AdminCreditController;
use App\Http\Controllers\API\Admin\AdminActivityLogController;
use App\Http\Controllers\API\Admin\AdminFormSectionController;
use App\Http\Controllers\API\Admin\AdminUserSubscriptionController;
use App\Http\Controllers\API\Admin\ContentManagementController;
use App\Http\Controllers\API\PublicContentController;
use App\Http\Controllers\API\ScreeningController;
use App\Http\Controllers\API\AssessmentController;
use App\Http\Controllers\API\SeekerAssessmentController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\SystemSettingsController;
use App\Http\Controllers\API\JobTitleController;
use App\Http\Controllers\API\IndustryController;
use App\Http\Controllers\API\BrandingController;
use App\Http\Controllers\API\ContractTemplateController;
use App\Http\Controllers\API\SeekerContractController;
use App\Http\Controllers\API\JobMatchingController;
use App\Http\Controllers\API\FastMessageController;
use App\Http\Controllers\API\JobAlertController;
use App\Http\Controllers\API\PlanController;
use App\Http\Controllers\API\HealthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;

// Debug route for profile image troubleshooting
require __DIR__.'/debug.php';

/**
 * Health Check Routes
 * Public endpoints for monitoring application status
 */
Route::get('/health', [HealthController::class, 'index'])->name('api.health');
Route::get('/health/detailed', [HealthController::class, 'detailed'])->name('api.health.detailed');

/**
 * Get authenticated user with seeker profile.
 * Uses eager loading to prevent N+1 query.
 */
Route::middleware(['auth:sanctum', 'throttle:120,1'])->get('/user', function (Request $request) {
    // Optimize: Only load essential relationships based on user role
    $user = $request->user();
    
    // Role-specific eager loading to reduce unnecessary queries
    if ($user->role === 'seeker') {
        $user->load(['seeker.resume']);
    } elseif ($user->role === 'employer') {
        $user->load(['employer']);
    }
    
    // Always load roles for RBAC
    $user->load(['roles']);
    
    // Ensure is_onboarding_completed is always present with a default value
    if ($user->is_onboarding_completed === null) {
        $user->is_onboarding_completed = false;
    }
    
    // Use API Resource for consistent response format
    return new UserResource($user);
});
// changed 


/**
 * Public auth routes with strict rate limiting
 */
Route::prefix('auth')->middleware('throttle:100,1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('verify-email', [AuthController::class, 'verifyEmail'])->name('api.auth.verify-email');
    Route::post('resend', [AuthController::class, 'resendVerification'])->name('api.auth.resend');
});
 
// Authenticated routes with standard rate limiting
Route::prefix('auth')->middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::put('password', [AuthController::class, 'updatePassword'])->name('api.auth.password.update');
    Route::get('sessions', [AuthController::class, 'sessions'])->name('api.auth.sessions');
    Route::get('login-history', [AuthController::class, 'loginHistory'])->name('api.auth.login-history');
});

// cnaghed

/**
 * Seeker Routes
 * 
 * All routes require authentication, seeker role, and have rate limiting.
 * Resume endpoints are cached for 5 minutes (see ResumeController).
 */
Route::middleware(['auth:sanctum', 'throttle:120,1', 'role.seeker'])->prefix('seeker')->group(function () {
    // Profile endpoints (using SeekerProfileService)
    Route::get('me', [SeekerProfileController::class, 'show'])->name('api.seeker.me');
    Route::put('profile', [SeekerProfileController::class, 'update'])->name('api.seeker.profile.update');
    Route::get('profile/completion', [SeekerProfileController::class, 'completion'])->name('api.seeker.profile.completion');
    Route::get('dashboard', [SeekerProfileController::class, 'dashboard'])->name('api.seeker.dashboard');
    Route::post('profile/avatar', [SeekerProfileController::class, 'uploadProfileImage'])->name('api.seeker.profile.avatar');
    Route::post('profile/full-body-image', [SeekerProfileController::class, 'uploadFullBodyImage'])->name('api.seeker.profile.full-body-image');
    
    // Dynamic Form Schema endpoints
    Route::get('form-schema/{module}', [FormSchemaController::class, 'getSchema'])->name('api.seeker.form-schema');
    Route::get('form-schema/seeker-profile/data', [FormSchemaController::class, 'getSeekerProfile'])->name('api.seeker.form-schema.data');
    Route::put('form-schema/seeker-profile/data', [FormSchemaController::class, 'updateSeekerProfile'])->name('api.seeker.form-schema.update');
    Route::get('form-schema/{module}/validation', [FormSchemaController::class, 'getFieldValidationRules'])->name('api.seeker.form-schema.validation');
    
    // Resume CRUD operations
    Route::get('resume', [ResumeController::class, 'show'])->name('api.seeker.resume.show');
    Route::get('resume/full', [ResumeController::class, 'full'])->name('api.seeker.resume.full'); // Aggregated endpoint
    Route::post('resume', [ResumeController::class, 'store'])->name('api.seeker.resume.store');
    Route::put('resume/section/{section}', [ResumeController::class, 'updateSection'])->name('api.seeker.resume.section.update');
    Route::patch('resume/section/{section}', [ResumeController::class, 'patchSection'])->name('api.seeker.resume.section.patch'); // Auto-save
    
    // File upload with stricter rate limit to prevent abuse
    Route::post('resume/upload', [ResumeController::class, 'upload'])
        ->middleware('throttle:30,1')
        ->name('api.seeker.resume.upload');
    
    // Role-specific field updates
    Route::post('resume/role-fields', [ResumeController::class, 'updateRoleSpecificFields'])->name('api.seeker.resume.role-fields.update');
    Route::post('resume/check-missing', [ResumeController::class, 'checkMissingFields'])->name('api.seeker.resume.check-missing');
    
    // Profile image management
    Route::post('profile/image', [ProfileImageController::class, 'upload'])->name('api.seeker.profile.image.upload');
    Route::delete('profile/image', [ProfileImageController::class, 'remove'])->name('api.seeker.profile.image.remove');
    Route::get('profile/image', [ProfileImageController::class, 'getCurrent'])->name('api.seeker.profile.image.current');
    
    // Driver Details - Just-in-Time data collection for Driver CV template
    Route::get('profile/driver-details', [ResumeController::class, 'getDriverDetails'])->name('api.seeker.profile.driver-details.show');
    Route::put('profile/driver-details', [ResumeController::class, 'updateDriverDetails'])->name('api.seeker.profile.driver-details.update');
    
    // Domestic Worker Details - Just-in-Time data collection for Domestic Worker CV template
    Route::get('profile/domestic-worker-details', [ResumeController::class, 'getDomesticWorkerDetails'])->name('api.seeker.profile.domestic-worker-details.show');
    Route::post('profile/domestic-worker-details', [ResumeController::class, 'updateDomesticWorkerDetails'])->name('api.seeker.profile.domestic-worker-details.update');
    
    // Security Guard Details - Just-in-Time data collection for Security Guard CV template
    Route::get('profile/security-guard-details', [ResumeController::class, 'getSecurityGuardDetails'])->name('api.seeker.profile.security-guard-details.show');
    Route::post('profile/security-guard-details', [ResumeController::class, 'updateSecurityGuardDetails'])->name('api.seeker.profile.security-guard-details.update');
    
    // Usage Limits & Billing
    Route::prefix('limits')->group(function () {
        Route::get('bilingual-cv', [\App\Http\Controllers\API\SeekerUsageController::class, 'getBilingualCVLimit'])->name('api.seeker.limits.bilingual-cv');
        Route::get('summary', [\App\Http\Controllers\API\SeekerUsageController::class, 'getUsageSummary'])->name('api.seeker.limits.summary');
        Route::get('{feature}', [\App\Http\Controllers\API\SeekerUsageController::class, 'checkLimit'])->name('api.seeker.limits.check');
    });
    
    // Saved Jobs
    Route::get('saved-jobs', [PublicJobController::class, 'savedJobs'])->name('api.seeker.saved-jobs.index');
    
    // Job Applications
    Route::prefix('applications')->group(function () {
        Route::get('/', [SeekerApplicationController::class, 'index'])->name('api.seeker.applications.index');
        Route::get('/activity', [SeekerApplicationController::class, 'activity'])->name('api.seeker.applications.activity');
        Route::get('/stats', [SeekerApplicationController::class, 'stats'])->name('api.seeker.applications.stats');
        Route::get('/{id}', [SeekerApplicationController::class, 'show'])->name('api.seeker.applications.show');
        Route::patch('/{id}/withdraw', [SeekerApplicationController::class, 'withdraw'])->name('api.seeker.applications.withdraw');
        Route::patch('/{id}/favorite', [SeekerApplicationController::class, 'toggleFavorite'])->name('api.seeker.applications.favorite');
    });
    
    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('api.seeker.notifications.index');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('api.seeker.notifications.unread-count');
        Route::get('/activities', [NotificationController::class, 'activities'])->name('api.seeker.notifications.activities');
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.seeker.notifications.read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('api.seeker.notifications.mark-all-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('api.seeker.notifications.destroy');
        Route::delete('/', [NotificationController::class, 'clearAll'])->name('api.seeker.notifications.clear-all');
    });
    
    // Action Requests (from Employers - Phase 4)
    Route::prefix('action-requests')->group(function () {
        Route::get('/', [SeekerActionRequestController::class, 'index'])->name('api.seeker.action-requests.index');
        Route::get('/pending-count', [SeekerActionRequestController::class, 'pendingCount'])->name('api.seeker.action-requests.pending-count');
        Route::get('/{id}', [SeekerActionRequestController::class, 'show'])->name('api.seeker.action-requests.show');
        Route::post('/{id}/respond', [SeekerActionRequestController::class, 'respond'])->name('api.seeker.action-requests.respond');
    });
    
    // CV Translation (Server-Side Google Translation for Bilingual CV)
    Route::prefix('cv')->group(function () {
        Route::post('/translate', [CVTranslationController::class, 'translate'])->name('api.seeker.cv.translate');
        Route::get('/languages', [CVTranslationController::class, 'getSupportedLanguages'])->name('api.seeker.cv.languages');
        Route::post('/invalidate-cache', [CVTranslationController::class, 'invalidateCache'])->name('api.seeker.cv.invalidate-cache');
    });

    // Assessments (Taking Tests)
    Route::prefix('assessments')->group(function () {
        Route::get('/job/{jobId}', [SeekerAssessmentController::class, 'forJob'])->name('api.seeker.assessments.for-job');
        Route::get('/my-attempts', [SeekerAssessmentController::class, 'myAttempts'])->name('api.seeker.assessments.my-attempts');
        Route::post('/{assessmentId}/start', [SeekerAssessmentController::class, 'start'])->name('api.seeker.assessments.start');
        Route::post('/attempts/{attemptId}/submit', [SeekerAssessmentController::class, 'submit'])->name('api.seeker.assessments.submit');
        Route::get('/attempts/{attemptId}/result', [SeekerAssessmentController::class, 'result'])->name('api.seeker.assessments.result');
    });

    // Messages (In-app messaging with Employers) - FAST VERSION
    Route::prefix('messages')->group(function () {
        Route::get('/', [FastMessageController::class, 'conversations'])->name('api.seeker.messages.index');
        Route::get('/unread-count', [FastMessageController::class, 'unreadCount'])->name('api.seeker.messages.unread-count');
        Route::post('/start', [MessageController::class, 'startConversation'])->name('api.seeker.messages.start');
        Route::get('/{uuid}', [MessageController::class, 'show'])->name('api.seeker.messages.show');
        Route::get('/{uuid}/messages', [MessageController::class, 'messages'])->name('api.seeker.messages.messages');
        Route::post('/{uuid}/send', [MessageController::class, 'sendMessage'])->name('api.seeker.messages.send');
        Route::post('/{uuid}/read', [FastMessageController::class, 'markAsRead'])->name('api.seeker.messages.read');
        Route::post('/{uuid}/archive', [MessageController::class, 'archive'])->name('api.seeker.messages.archive');
        Route::put('/message/{messageId}', [MessageController::class, 'editMessage'])->name('api.seeker.messages.edit');
        Route::delete('/message/{messageId}', [MessageController::class, 'deleteMessage'])->name('api.seeker.messages.delete');
    });

    // Contracts (Seeker viewing and signing)
    Route::prefix('contracts')->group(function () {
        Route::get('/', [SeekerContractController::class, 'index'])->name('api.seeker.contracts.index');
        Route::get('/unread-count', [SeekerContractController::class, 'getUnreadCount'])->name('api.seeker.contracts.unread-count');
        Route::get('/{id}', [SeekerContractController::class, 'show'])->name('api.seeker.contracts.show');
        Route::get('/{id}/debug', [SeekerContractController::class, 'debugContract'])->name('api.seeker.contracts.debug');
        Route::post('/{id}/sign', [SeekerContractController::class, 'sign'])->name('api.seeker.contracts.sign');
        Route::post('/{id}/upload-signed', [SeekerContractController::class, 'uploadSignedCopy'])->name('api.seeker.contracts.upload-signed');
        Route::post('/{id}/reject', [SeekerContractController::class, 'reject'])->name('api.seeker.contracts.reject');
        Route::get('/{id}/download', [SeekerContractController::class, 'download'])->name('api.seeker.contracts.download');
        Route::get('/{id}/messages', [SeekerContractController::class, 'getMessages'])->name('api.seeker.contracts.messages');
        Route::post('/{id}/messages', [SeekerContractController::class, 'sendMessage'])->name('api.seeker.contracts.send-message');
    });

    // Visa Status (Seeker read-only view)
    Route::get('visa-status', [SeekerContractController::class, 'getVisaStatus'])->name('api.seeker.visa-status');
    Route::post('visa-status/{visaStatusId}/steps/{stepId}/upload-document', [SeekerContractController::class, 'uploadVisaDocument'])->name('api.seeker.visa-status.upload-document');
    Route::delete('visa-status/{visaStatusId}/steps/{stepId}/documents/{documentId}', [SeekerContractController::class, 'deleteVisaDocument'])->name('api.seeker.visa-status.delete-document');
    Route::post('visa-status/{visaStatusId}/process-steps/{processStepId}/upload-document', [SeekerContractController::class, 'uploadVisaProcessDocument'])->name('api.seeker.visa-status.process.upload-document');
    Route::delete('visa-status/{visaStatusId}/process-steps/{processStepId}/documents/{documentId}', [SeekerContractController::class, 'deleteVisaProcessDocument'])->name('api.seeker.visa-status.process.delete-document');

    // Interviews (Seeker view only)
    Route::prefix('interviews')->group(function () {
        Route::get('/upcoming', [InterviewController::class, 'seekerUpcoming'])->name('api.seeker.interviews.upcoming');
        Route::get('/past', [InterviewController::class, 'seekerPast'])->name('api.seeker.interviews.past');
        Route::get('/{id}', [InterviewController::class, 'seekerShow'])->name('api.seeker.interviews.show');
        
        // Interview Reminders
        Route::post('/{id}/reminder', [InterviewController::class, 'setReminder'])->name('api.seeker.interviews.reminder.set');
        Route::get('/{id}/reminders', [InterviewController::class, 'getReminders'])->name('api.seeker.interviews.reminders');
        Route::delete('/{id}/reminders/{reminderId}', [InterviewController::class, 'cancelReminder'])->name('api.seeker.interviews.reminders.cancel');
    });

    // Job Alerts
    Route::prefix('alerts')->group(function () {
        Route::get('/', [JobAlertController::class, 'index'])->name('api.seeker.alerts.index');
        Route::post('/', [JobAlertController::class, 'store'])->name('api.seeker.alerts.store');
        Route::get('/{id}', [JobAlertController::class, 'show'])->name('api.seeker.alerts.show');
        Route::put('/{id}', [JobAlertController::class, 'update'])->name('api.seeker.alerts.update');
        Route::patch('/{id}/toggle', [JobAlertController::class, 'toggleActive'])->name('api.seeker.alerts.toggle');
        Route::delete('/{id}', [JobAlertController::class, 'destroy'])->name('api.seeker.alerts.destroy');
    });
});

/**
 * CV Preview & Download Routes (Accessible by authenticated users - employers viewing candidate CVs)
 */
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // JSON data for frontend rendering
    Route::get('resume/preview/{seekerId}', [ResumeController::class, 'preview'])->name('api.resume.preview');
    // HTML preview - opens in browser tab for debugging layout
    Route::get('resume/preview-html/{seekerId}', [ResumeController::class, 'previewHtml'])->name('api.resume.preview-html');
    // NOTE: PDF download route REMOVED - PDF generation is now client-side via @react-pdf/renderer
});

Route::middleware(['auth:sanctum', 'throttle:120,1', 'role.seeker'])->prefix('seeker')->group(function () {
    // Resume endpoints for seekers
    Route::get('resume', [ResumeController::class, 'getSeekerResume'])->name('api.seeker.resume');
    Route::get('resume/full', [ResumeController::class, 'getSeekerResumeFull'])->name('api.seeker.resume.full');
    
    // Debug endpoint (only in non-production)
    if (app()->environment('local', 'development')) {
        Route::get('debug', function (Request $request) {
            return response()->json([
                'user' => $request->user(),
                'seeker' => $request->user()->seeker,
                'has_resume' => $request->user()->seekerResume()->exists(),
                'resume' => $request->user()->seekerResume,
            ]);
        });
    }
});

/**
 * Onboarding Routes
 * 
 * All routes require authentication and handle the onboarding flow.
 */
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('onboarding')->group(function () {
    Route::get('status', [OnboardingController::class, 'getOnboardingStatus'])->name('api.onboarding.status');
    Route::post('complete', [OnboardingController::class, 'completeOnboarding'])->name('api.onboarding.complete');
});

/**
 * Health Check Endpoint
 * 
 * Public endpoint for monitoring API availability.
 * Rate limited to prevent abuse.
 */
Route::middleware('throttle:30,1')->get('test', function () {
    return response()->json([
        'message' => 'API is working',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
    ]);
});

/**
 * Skills API Routes
 * 
 * Fast autocomplete endpoints for skill search.
 * Cached responses for quick performance.
 */
Route::middleware('throttle:120,1')->prefix('skills')->group(function () {
    Route::get('/', [SkillController::class, 'index'])->name('api.skills.index');
    Route::get('/search', [SkillController::class, 'search'])->name('api.skills.search');
    Route::get('/popular', [SkillController::class, 'popular'])->name('api.skills.popular');
    Route::post('/', [SkillController::class, 'store'])->name('api.skills.store')->middleware('auth:sanctum');
});

/**
 * Industry API Routes
 * 
 * Fast autocomplete endpoints for industry search.
 * Available to both authenticated and unauthenticated users for form population.
 */
Route::middleware('throttle:120,1')->prefix('industries')->group(function () {
    // Get custom industries from database
    Route::get('/', [IndustryController::class, 'index'])->name('api.industries.index');
    
    // Create new industry (requires authentication to prevent spam)
    Route::post('/', [IndustryController::class, 'store'])->name('api.industries.store')->middleware('auth:sanctum');
});

/**
 * Public Seeker Profile Routes
 * 
 * Accessible without authentication for viewing seeker profiles.
 */
Route::middleware('throttle:60,1')->prefix('seekers')->group(function () {
    Route::get('/{id}', [ResumeController::class, 'publicProfile'])->name('api.seekers.public.profile');
});

/**
 * Public Job Routes
 * 
 * Public endpoints for browsing published jobs.
 * Available to both authenticated and unauthenticated users.
 * Rate limited to prevent abuse.
 */
Route::middleware('throttle:60,1')->prefix('jobs')->group(function () {
    Route::get('/', [PublicJobController::class, 'index'])->name('api.jobs.index')->middleware('auth:sanctum');
    Route::get('/stats', [PublicJobController::class, 'stats'])->name('api.jobs.stats');
    Route::get('/{identifier}', [PublicJobController::class, 'show'])->name('api.jobs.show');
    Route::post('/{identifier}/apply', [PublicJobController::class, 'apply'])->name('api.jobs.apply')->middleware('auth:sanctum');
    Route::post('/{identifier}/save', [PublicJobController::class, 'toggleSave'])->name('api.jobs.save')->middleware('auth:sanctum');
});

/**
 * Job Titles Routes
 * 
 * Public endpoints for job title autocomplete and management.
 * Rate limited to prevent abuse.
 */
Route::middleware('throttle:60,1')->prefix('job-titles')->group(function () {
    Route::get('/', [JobTitleController::class, 'index'])->name('api.job-titles.index');
    Route::get('/search', [JobTitleController::class, 'search'])->name('api.job-titles.search');
    Route::post('/', [JobTitleController::class, 'store'])->name('api.job-titles.store')->middleware('auth:sanctum');
});

/**
 * Employer Registration Route
 * 
 * This route is separate because it doesn't require the employer role yet.
 * Users register as employers through this endpoint.
 */
Route::middleware('throttle:100,1')->prefix('employer')->group(function () {
    Route::post('register', [EmployerController::class, 'register'])->name('api.employer.register');
    Route::post('check-email', [EmployerController::class, 'checkEmail'])->name('api.employer.check-email');
});

/**
 * Job Matching Routes
 * 
 * Routes for intelligent job-seeker matching and notifications.
 */
Route::middleware(['auth:sanctum', 'throttle:120,1', 'role.seeker'])->prefix('job-matching')->group(function () {
    Route::get('/matched-jobs', [JobMatchingController::class, 'getMatchedJobs'])->name('api.seeker.job-matching.matched-jobs');
    Route::get('/stats', [JobMatchingController::class, 'getMatchStats'])->name('api.seeker.job-matching.stats');
});

/**
 * Employer Routes (Protected by role.employer middleware)
 * 
 * All routes require authentication, employer role, and handle employer operations.
 * Includes: Profile, Jobs, Applications, Interviews, Contracts, Visa, Team
 */
Route::middleware(['auth:sanctum', 'throttle:60,1', 'role.employer'])->prefix('employer')->group(function () {
    // Employer Profile (accessible even if not verified - for viewing/updating profile)
    Route::get('profile', [EmployerController::class, 'show'])->name('api.employer.profile.show');
    Route::put('profile', [EmployerController::class, 'update'])->name('api.employer.profile.update');
    Route::post('profile/logo', [EmployerController::class, 'uploadLogo'])->name('api.employer.profile.logo');
    Route::get('dashboard', [EmployerController::class, 'dashboard'])->name('api.employer.dashboard');

    // ============================================================
    // VERIFIED EMPLOYER ROUTES (Require employer.verified middleware)
    // These routes are GATED - only verified employers can access
    // ============================================================
    Route::middleware('employer.verified')->group(function () {
        // Job Postings (GATED - requires verification)
        Route::prefix('jobs')->group(function () {
            Route::middleware('permission:employer.view_job_analytics')->group(function () {
                Route::get('/', [JobPostingController::class, 'index'])->name('api.employer.jobs.index');
                Route::get('/{id}', [JobPostingController::class, 'show'])->name('api.employer.jobs.show');
                Route::get('/{id}/applications', [ApplicationController::class, 'forJob'])->name('api.employer.jobs.applications');
            });
            
            Route::middleware('permission:employer.post_job')->group(function () {
                Route::post('/', [JobPostingController::class, 'store'])->name('api.employer.jobs.store');
            });
            
            Route::middleware('permission:employer.edit_job')->group(function () {
                Route::put('/{id}', [JobPostingController::class, 'update'])->name('api.employer.jobs.update');
                Route::patch('/{id}/status', [JobPostingController::class, 'updateStatus'])->name('api.employer.jobs.status');
            });
            
            Route::middleware('permission:employer.delete_job')->group(function () {
                Route::delete('/{id}', [JobPostingController::class, 'destroy'])->name('api.employer.jobs.destroy');
            });
        });

        // Applications (GATED - requires verification)
        Route::prefix('applications')->group(function () {
            Route::get('/', [ApplicationController::class, 'index'])->name('api.employer.applications.index');
            Route::get('/pipeline', [ApplicationController::class, 'pipelineStats'])->name('api.employer.applications.pipeline');
            Route::get('/{id}', [ApplicationController::class, 'show'])->name('api.employer.applications.show');
            Route::patch('/{id}/status', [ApplicationController::class, 'updateStatus'])->name('api.employer.applications.status');
            Route::patch('/{id}/favorite', [ApplicationController::class, 'toggleFavorite'])->name('api.employer.applications.favorite');
            Route::patch('/{id}/notes', [ApplicationController::class, 'updateNotes'])->name('api.employer.applications.notes');
            Route::patch('/{id}/rate', [ApplicationController::class, 'rate'])->name('api.employer.applications.rate');
        });

        // Candidates (GATED - requires verification for search)
        Route::prefix('candidates')->group(function () {
            Route::get('/', [CandidateController::class, 'index'])->name('api.employer.candidates.index');
            Route::get('/search', [CandidateController::class, 'search'])->name('api.employer.candidates.search');
            Route::get('/for-contract', [CandidateController::class, 'forContract'])->name('api.employer.candidates.for-contract');
            Route::post('/', [CandidateController::class, 'store'])->name('api.employer.candidates.store');
            Route::get('/{id}', [CandidateController::class, 'show'])->name('api.employer.candidates.show');
            Route::put('/{id}', [CandidateController::class, 'update'])->name('api.employer.candidates.update');
            
            // Candidate Notes (Internal Notes - Phase 5)
            Route::get('/{seekerId}/notes', [CandidateNoteController::class, 'index'])->name('api.employer.candidates.notes.index');
            Route::post('/{seekerId}/notes', [CandidateNoteController::class, 'store'])->name('api.employer.candidates.notes.store');
            Route::delete('/{seekerId}/notes/{noteId}', [CandidateNoteController::class, 'destroy'])->name('api.employer.candidates.notes.destroy');
        });

        // Action Requests (Employer -> Seeker - Phase 4)
        Route::prefix('action-requests')->group(function () {
            Route::get('/', [ActionRequestController::class, 'index'])->name('api.employer.action-requests.index');
            Route::post('/', [ActionRequestController::class, 'store'])->name('api.employer.action-requests.store');
            Route::get('/{id}', [ActionRequestController::class, 'show'])->name('api.employer.action-requests.show');
            Route::post('/{id}/cancel', [ActionRequestController::class, 'cancel'])->name('api.employer.action-requests.cancel');
        });

        // Interviews
        Route::prefix('interviews')->group(function () {
            Route::get('/', [InterviewController::class, 'index'])->name('api.employer.interviews.index');
            Route::get('/upcoming', [InterviewController::class, 'upcoming'])->name('api.employer.interviews.upcoming');
            Route::get('/today', [InterviewController::class, 'today'])->name('api.employer.interviews.today');
            Route::post('/', [InterviewController::class, 'store'])->name('api.employer.interviews.store');
            Route::get('/{id}', [InterviewController::class, 'show'])->name('api.employer.interviews.show');
            Route::put('/{id}', [InterviewController::class, 'update'])->name('api.employer.interviews.update');
            Route::post('/{id}/cancel', [InterviewController::class, 'cancel'])->name('api.employer.interviews.cancel');
            Route::post('/{id}/complete', [InterviewController::class, 'complete'])->name('api.employer.interviews.complete');
        });

        // Visa Status
        Route::prefix('visa')->group(function () {
            Route::get('/steps', [VisaStatusController::class, 'steps'])->name('api.employer.visa.steps');
            Route::get('/', [VisaStatusController::class, 'index'])->name('api.employer.visa.index');
            Route::get('/{id}', [VisaStatusController::class, 'show'])->name('api.employer.visa.show');
            Route::patch('/{id}/step', [VisaStatusController::class, 'updateStep'])->name('api.employer.visa.step');
            Route::put('/{id}', [VisaStatusController::class, 'update'])->name('api.employer.visa.update');
            Route::post('/{id}/request-documents', [VisaStatusController::class, 'requestDocuments'])->name('api.employer.visa.request-documents');

            // Workflow Management (existing VisaStep records)
            Route::prefix('/{visaStatusId}/workflow')->group(function () {
                Route::get('/', [VisaWorkflowController::class, 'index'])->name('api.employer.visa.workflow.index');
                Route::put('/{stepId}', [VisaWorkflowController::class, 'update'])->name('api.employer.visa.workflow.update');
                Route::delete('/{stepId}', [VisaWorkflowController::class, 'destroy'])->name('api.employer.visa.workflow.destroy');
                Route::post('/reorder', [VisaWorkflowController::class, 'reorder'])->name('api.employer.visa.workflow.reorder');
                Route::post('/{stepId}/complete', [VisaWorkflowController::class, 'completeStep'])->name('api.employer.visa.workflow.complete');
                Route::post('/{stepId}/request-documents', [VisaWorkflowController::class, 'requestDocuments'])->name('api.employer.visa.workflow.request-documents');
                Route::post('/{stepId}/questionnaires', [VisaWorkflowController::class, 'createQuestionnaire'])->name('api.employer.visa.workflow.questionnaires');
                Route::post('/{stepId}/upload', [VisaWorkflowController::class, 'uploadDocument'])->name('api.employer.visa.workflow.upload');
            });

            // Document Management
            Route::prefix('/{visaStatusId}/steps/{stepId}/documents')->group(function () {
                Route::get('/', [VisaDocumentController::class, 'index'])->name('api.employer.visa.documents.index');
                Route::patch('/{documentId}/verify', [VisaDocumentController::class, 'verify'])->name('api.employer.visa.documents.verify');
            });
            Route::prefix('/{visaStatusId}/process-steps/{processStepId}/documents')->group(function () {
                Route::get('/', [VisaDocumentController::class, 'processIndex'])->name('api.employer.visa.process.documents.index');
                Route::patch('/{documentId}/verify', [VisaDocumentController::class, 'processVerify'])->name('api.employer.visa.process.documents.verify');
            });
            Route::delete('/{visaStatusId}/process-steps/{processStepId}', [VisaDocumentController::class, 'deleteProcessStep'])->name('api.employer.visa.process.steps.delete');
            Route::post('/{visaStatusId}/custom-document-request', [VisaDocumentController::class, 'requestCustom'])->name('api.employer.visa.custom.request');
            Route::get('/{visaStatusId}/custom-steps', [VisaDocumentController::class, 'getCustomSteps'])->name('api.employer.visa.custom.steps');
        });

        // Contract Templates
        Route::prefix('contract-templates')->group(function () {
            Route::get('/', [ContractTemplateController::class, 'index'])->name('api.employer.contract-templates.index');
            Route::get('/placeholders', [ContractTemplateController::class, 'placeholders'])->name('api.employer.contract-templates.placeholders');
            Route::post('/', [ContractTemplateController::class, 'store'])->name('api.employer.contract-templates.store');
            Route::get('/{id}', [ContractTemplateController::class, 'show'])->name('api.employer.contract-templates.show');
            Route::put('/{id}', [ContractTemplateController::class, 'update'])->name('api.employer.contract-templates.update');
            Route::delete('/{id}', [ContractTemplateController::class, 'destroy'])->name('api.employer.contract-templates.destroy');
            Route::post('/{id}/duplicate', [ContractTemplateController::class, 'duplicate'])->name('api.employer.contract-templates.duplicate');
            Route::get('/{id}/preview', [ContractTemplateController::class, 'preview'])->name('api.employer.contract-templates.preview');
            Route::get('/{id}/debug', [ContractTemplateController::class, 'debug'])->name('api.employer.contract-templates.debug');
        });

        // Contracts
        Route::prefix('contracts')->group(function () {
            Route::get('/', [ContractController::class, 'index'])->name('api.employer.contracts.index');
            Route::get('/stats', [ContractController::class, 'stats'])->name('api.employer.contracts.stats');
            Route::get('/candidates', [ContractController::class, 'getCandidatesForContract'])->name('api.employer.contracts.candidates');
            Route::get('/pending-approvals', [ContractController::class, 'pendingApprovals'])->name('api.employer.contracts.pending-approvals');
            Route::post('/', [ContractController::class, 'store'])->name('api.employer.contracts.store');
            Route::get('/{id}', [ContractController::class, 'show'])->name('api.employer.contracts.show');
            Route::get('/{id}/preview', [ContractController::class, 'preview'])->name('api.employer.contracts.preview');
            Route::put('/{id}', [ContractController::class, 'update'])->name('api.employer.contracts.update');
            Route::delete('/{id}', [ContractController::class, 'destroy'])->name('api.employer.contracts.destroy');
            Route::post('/{id}/send', [ContractController::class, 'send'])->name('api.employer.contracts.send');
            Route::post('/{id}/submit-for-approval', [ContractController::class, 'submitForApproval'])->name('api.employer.contracts.submit-approval');
            Route::post('/{id}/approve', [ContractController::class, 'approve'])->name('api.employer.contracts.approve');
            Route::post('/{id}/reject-approval', [ContractController::class, 'rejectApproval'])->name('api.employer.contracts.reject-approval');
            Route::post('/{id}/attachment', [ContractController::class, 'uploadAttachment'])->name('api.employer.contracts.attachment');
            Route::post('/{id}/revision', [ContractController::class, 'createRevision'])->name('api.employer.contracts.revision');
            Route::post('/{id}/initiate-visa', [ContractController::class, 'initiateVisaProcess'])->name('api.employer.contracts.initiate-visa');
        });

        // Screening Questions (Per Job)
        Route::prefix('screening')->group(function () {
            Route::get('/job/{jobId}', [ScreeningController::class, 'index'])->name('api.employer.screening.index');
            Route::post('/job/{jobId}', [ScreeningController::class, 'store'])->name('api.employer.screening.store');
            Route::put('/{questionId}', [ScreeningController::class, 'update'])->name('api.employer.screening.update');
            Route::delete('/{questionId}', [ScreeningController::class, 'destroy'])->name('api.employer.screening.destroy');
            Route::post('/job/{jobId}/reorder', [ScreeningController::class, 'reorder'])->name('api.employer.screening.reorder');
        });

        // Assessments (Custom Tests + Admin Standard)
        Route::prefix('assessments')->group(function () {
            Route::get('/', [AssessmentController::class, 'index'])->name('api.employer.assessments.index');
            Route::get('/browse', [AssessmentController::class, 'browseStandard'])->name('api.employer.assessments.browse');
            Route::get('/limits', [AssessmentController::class, 'limits'])->name('api.employer.assessments.limits');
            Route::post('/', [AssessmentController::class, 'store'])->name('api.employer.assessments.store');
            Route::get('/{id}', [AssessmentController::class, 'show'])->name('api.employer.assessments.show');
            Route::put('/{id}', [AssessmentController::class, 'update'])->name('api.employer.assessments.update');
            Route::delete('/{id}', [AssessmentController::class, 'destroy'])->name('api.employer.assessments.destroy');
            Route::post('/job/{jobId}/attach', [AssessmentController::class, 'attachToJob'])->name('api.employer.assessments.attach');
            Route::delete('/job/{jobId}/{assessmentId}', [AssessmentController::class, 'detachFromJob'])->name('api.employer.assessments.detach');
            // Retry permission management
            Route::get('/job/{jobId}/failed-attempts', [AssessmentController::class, 'failedAttempts'])->name('api.employer.assessments.failed');
            Route::post('/attempts/{attemptId}/grant-retry', [AssessmentController::class, 'grantRetry'])->name('api.employer.assessments.grant-retry');
            Route::post('/attempts/{attemptId}/revoke-retry', [AssessmentController::class, 'revokeRetry'])->name('api.employer.assessments.revoke-retry');
        });

        // Messages (In-app messaging with Candidates) - FAST VERSION
        Route::prefix('messages')->group(function () {
            Route::get('/', [FastMessageController::class, 'conversations'])->name('api.employer.messages.index');
            Route::get('/unread-count', [FastMessageController::class, 'unreadCount'])->name('api.employer.messages.unread-count');
            Route::post('/start', [MessageController::class, 'startConversation'])->name('api.employer.messages.start');
            Route::get('/{uuid}', [MessageController::class, 'show'])->name('api.employer.messages.show');
            Route::get('/{uuid}/messages', [MessageController::class, 'messages'])->name('api.employer.messages.messages');
            Route::post('/{uuid}/send', [MessageController::class, 'sendMessage'])->name('api.employer.messages.send');
            Route::post('/{uuid}/read', [FastMessageController::class, 'markAsRead'])->name('api.employer.messages.read');
            Route::post('/{uuid}/archive', [MessageController::class, 'archive'])->name('api.employer.messages.archive');
            Route::put('/message/{messageId}', [MessageController::class, 'editMessage'])->name('api.employer.messages.edit');
            Route::delete('/message/{messageId}', [MessageController::class, 'deleteMessage'])->name('api.employer.messages.delete');
            Route::get('/search-users', [MessageController::class, 'searchUsers'])->name('api.employer.messages.search-users');
        });

        // Team Management (GATED - requires verification)
        Route::prefix('team')->group(function () {
            Route::get('/', [EmployerTeamController::class, 'index'])->name('api.employer.team.index');
            Route::post('/invite', [EmployerTeamController::class, 'invite'])->name('api.employer.team.invite');
            Route::put('/{teamMember}/role', [EmployerTeamController::class, 'updateRole'])->name('api.employer.team.update-role');
            Route::delete('/{teamMember}', [EmployerTeamController::class, 'remove'])->name('api.employer.team.remove');
            Route::get('/roles', [EmployerTeamController::class, 'getAvailableRoles'])->name('api.employer.team.roles');
        });

        }); // End of employer.verified middleware group
});

// Temporary debug route for analytics testing (outside auth groups)
Route::get('/debug/analytics/{jobId}', function ($jobId) {
    try {
        $stats = app(\App\Services\JobAnalyticsService::class)->getJobStats($jobId);
        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Serve uploaded files - Handle variable directory structures
Route::get('/storage/{path}', function ($path) {
    // Decode the path and construct full file path
    $fullPath = storage_path('app/public/' . $path);
    
    // Check if file exists first
    if (!File::exists($fullPath)) {
        abort(404, 'File not found');
    }
    
    // Security: Ensure the file is within the storage directory
    $realPath = realpath($fullPath);
    $storagePath = realpath(storage_path('app/public'));
    
    if (!$realPath || !str_starts_with($realPath, $storagePath)) {
        abort(403, 'Access denied');
    }
    
    // Get MIME type
    $mimeType = mime_content_type($fullPath);
    
    // Return file with proper headers
    return response()->file($fullPath, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000', // Cache for 1 year
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET',
        'Access-Control-Allow-Headers' => 'Content-Type',
    ]);
})->where('path', '.*');

/**
 * Google Services Routes
 * 
 * Public endpoints for Google Cloud services (Translation, Vision).
 * Rate limited to prevent abuse.
 */
Route::middleware('throttle:30,1')->prefix('google')->group(function () {
    Route::post('/translate', [GoogleServiceController::class, 'translate'])->name('api.google.translate');
    Route::post('/analyze-image', [GoogleServiceController::class, 'analyzeImage'])->name('api.google.analyze-image');
});

// Simple translation endpoint
Route::post('/translate-text', [GoogleServiceController::class, 'translate']);

/**
 * Admin Authentication Routes (Public)
 * 
 * Separate login flow for admins with strict rate limiting.
 */
Route::prefix('admin')->group(function () {
    Route::middleware('throttle:100,1')->group(function () {
        Route::post('login', [AdminAuthController::class, 'login'])->name('api.admin.login');
    });
});

/**
 * Admin Routes (Protected by permission-based middleware)
 * 
 * All routes require authentication and specific admin permissions.
 * Includes: Dashboard, Verification, Seeker Management, Job Moderation
 */
Route::middleware(['auth:sanctum', 'throttle:60,1', 'permission:admin.access_admin_panel'])->prefix('admin')->group(function () {
    // Auth
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('api.admin.logout');
    Route::get('me', [AdminAuthController::class, 'me'])->name('api.admin.me');

    // Dashboard - Requires view_dashboard permission
    Route::middleware('permission:admin.view_dashboard')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('api.admin.dashboard');
        Route::get('dashboard/activity', [AdminDashboardController::class, 'recentActivity'])->name('api.admin.dashboard.activity');
        Route::get('dashboard/charts', [AdminDashboardController::class, 'chartData'])->name('api.admin.dashboard.charts');
    });

    // Employer Verification (CRITICAL)
    Route::middleware('permission:admin.verify_employers')->prefix('verification')->group(function () {
        Route::get('/stats', [AdminVerificationController::class, 'stats'])->name('api.admin.verification.stats');
        Route::get('/queue', [AdminVerificationController::class, 'queue'])->name('api.admin.verification.queue');
        Route::get('/employers', [AdminVerificationController::class, 'index'])->name('api.admin.verification.employers');
        Route::get('/employers/{id}', [AdminVerificationController::class, 'show'])->name('api.admin.verification.show');
        Route::post('/employers/{id}/verify', [AdminVerificationController::class, 'verify'])->name('api.admin.verification.verify');
        Route::post('/employers/{id}/reject', [AdminVerificationController::class, 'reject'])->name('api.admin.verification.reject');
        Route::post('/employers/{id}/reset', [AdminVerificationController::class, 'resetToPending'])->name('api.admin.verification.reset');
    });

    // Seeker Management
    Route::middleware('permission:admin.view_seekers')->prefix('seekers')->group(function () {
        Route::get('/', [AdminSeekerController::class, 'index'])->name('api.admin.seekers.index');
        Route::get('/{id}', [AdminSeekerController::class, 'show'])->name('api.admin.seekers.show');
        Route::middleware('permission:admin.ban_seekers')->group(function () {
            Route::post('/{id}/ban', [AdminSeekerController::class, 'ban'])->name('api.admin.seekers.ban');
            Route::post('/{id}/unban', [AdminSeekerController::class, 'unban'])->name('api.admin.seekers.unban');
        });
    });

    // Job Moderation
    Route::middleware('permission:admin.view_jobs')->prefix('jobs')->group(function () {
        Route::get('/', [AdminJobController::class, 'index'])->name('api.admin.jobs.index');
        Route::get('/{id}', [AdminJobController::class, 'show'])->name('api.admin.jobs.show');
        Route::middleware('permission:admin.edit_jobs')->group(function () {
            Route::patch('/{id}/status', [AdminJobController::class, 'updateStatus'])->name('api.admin.jobs.status');
            Route::delete('/{id}', [AdminJobController::class, 'destroy'])->name('api.admin.jobs.destroy');
        });
    });

    // Global Search
    Route::prefix('search')->group(function () {
        Route::get('/', [AdminSearchController::class, 'search'])->name('api.admin.search');
        Route::get('/suggestions', [AdminSearchController::class, 'suggestions'])->name('api.admin.search.suggestions');
    });

    // Shadow Login (Impersonation)
    Route::prefix('impersonate')->group(function () {
        Route::post('/{userId}', [AdminImpersonationController::class, 'impersonate'])->name('api.admin.impersonate');
        Route::post('/stop', [AdminImpersonationController::class, 'stopImpersonation'])->name('api.admin.impersonate.stop');
        Route::get('/history', [AdminImpersonationController::class, 'history'])->name('api.admin.impersonate.history');
    });

    // Skills Management (Bulk Import TXT/CSV)
    Route::prefix('skills')->group(function () {
        Route::get('/', [AdminSkillController::class, 'index'])->name('api.admin.skills.index');
        Route::post('/', [AdminSkillController::class, 'store'])->name('api.admin.skills.store');
        Route::put('/{id}', [AdminSkillController::class, 'update'])->name('api.admin.skills.update');
        Route::delete('/{id}', [AdminSkillController::class, 'destroy'])->name('api.admin.skills.destroy');
        Route::post('/import', [AdminSkillController::class, 'import'])->name('api.admin.skills.import');
        Route::get('/export', [AdminSkillController::class, 'export'])->name('api.admin.skills.export');
        Route::get('/categories', [AdminSkillController::class, 'categories'])->name('api.admin.skills.categories');
    });

    // Assessment Management (Admin Standard Tests)
    Route::prefix('assessments')->group(function () {
        Route::get('/', [AdminAssessmentController::class, 'index'])->name('api.admin.assessments.index');
        Route::get('/stats', [AdminAssessmentController::class, 'stats'])->name('api.admin.assessments.stats');
        Route::get('/categories', [AdminAssessmentController::class, 'categories'])->name('api.admin.assessments.categories');
        Route::get('/failed-attempts', [AdminAssessmentController::class, 'failedAttempts'])->name('api.admin.assessments.failed');
        Route::post('/attempts/{attemptId}/grant-retry', [AdminAssessmentController::class, 'grantRetry'])->name('api.admin.assessments.grant-retry');
        Route::post('/attempts/{attemptId}/revoke-retry', [AdminAssessmentController::class, 'revokeRetry'])->name('api.admin.assessments.revoke-retry');
        Route::post('/', [AdminAssessmentController::class, 'store'])->name('api.admin.assessments.store');
        Route::get('/{id}', [AdminAssessmentController::class, 'show'])->name('api.admin.assessments.show');
        Route::put('/{id}', [AdminAssessmentController::class, 'update'])->name('api.admin.assessments.update');
        Route::delete('/{id}', [AdminAssessmentController::class, 'destroy'])->name('api.admin.assessments.destroy');
    });

    // Admin Settings (Global Limits Configuration)
    Route::prefix('settings')->group(function () {
        Route::get('/', [AdminSettingsController::class, 'index'])->name('api.admin.settings.index');
        Route::post('/', [AdminSettingsController::class, 'store'])->name('api.admin.settings.store');
        Route::put('/', [AdminSettingsController::class, 'update'])->name('api.admin.settings.update');
        Route::get('/categories', [AdminSettingsController::class, 'categories'])->name('api.admin.settings.categories');
        Route::get('/{key}', [AdminSettingsController::class, 'show'])->name('api.admin.settings.show');
        Route::delete('/{key}', [AdminSettingsController::class, 'destroy'])->name('api.admin.settings.destroy');
        Route::get('/employer/{employerId}', [AdminSettingsController::class, 'employerSettings'])->name('api.admin.settings.employer');
        Route::put('/employer/{employerId}', [AdminSettingsController::class, 'updateEmployerSettings'])->name('api.admin.settings.employer.update');

        // Branding Management
        Route::get('/branding', [AdminSettingsController::class, 'getBranding'])->name('api.admin.settings.branding');
        Route::put('/branding', [AdminSettingsController::class, 'updateBranding'])->name('api.admin.settings.branding.update');
        Route::post('/branding/logo', [AdminSettingsController::class, 'uploadLogo'])->name('api.admin.settings.branding.logo');
        Route::post('/branding/favicon', [AdminSettingsController::class, 'uploadFavicon'])->name('api.admin.settings.branding.favicon');
        Route::delete('/branding/logo', [AdminSettingsController::class, 'removeLogo'])->name('api.admin.settings.branding.logo.remove');

        // System Settings Management
        Route::get('/system/all', [AdminSettingsController::class, 'getSystemSettings'])->name('api.admin.settings.system.all');
        Route::put('/system/{id}', [AdminSettingsController::class, 'updateSystemSetting'])->name('api.admin.settings.system.update');
    });

    // Plans Management (Finance Module)
    Route::prefix('finance/plans')->group(function () {
        Route::get('/', [AdminPlanController::class, 'index'])->name('api.admin.plans.index');
        Route::post('/', [AdminPlanController::class, 'store'])->name('api.admin.plans.store');
        Route::get('/{id}', [AdminPlanController::class, 'show'])->name('api.admin.plans.show');
        Route::put('/{id}', [AdminPlanController::class, 'update'])->name('api.admin.plans.update');
        Route::delete('/{id}', [AdminPlanController::class, 'destroy'])->name('api.admin.plans.destroy');
        Route::post('/{id}/toggle', [AdminPlanController::class, 'toggleStatus'])->name('api.admin.plans.toggle');
        Route::post('/{id}/duplicate', [AdminPlanController::class, 'duplicate'])->name('api.admin.plans.duplicate');
        Route::get('/role/{role}', [AdminPlanController::class, 'getByRole'])->name('api.admin.plans.by-role');
        Route::get('/role/{role}/addons', [AdminPlanController::class, 'getAddonsByRole'])->name('api.admin.plans.addons-by-role');
        Route::get('/role/{role}/subscriptions', [AdminPlanController::class, 'getSubscriptionsByRole'])->name('api.admin.plans.subscriptions-by-role');
        Route::get('/role/{role}/structure', [AdminPlanController::class, 'getRoleLimitsStructure'])->name('api.admin.plans.role-structure');
    });

    // Role-specific Plan Management
    Route::prefix('plans')->group(function () {
        Route::get('/seeker', [AdminPlanController::class, 'getSubscriptionsByRole'])->name('api.admin.plans.seeker');
        Route::get('/employer', [AdminPlanController::class, 'getSubscriptionsByRole'])->name('api.admin.plans.employer');
        Route::get('/seeker/addons', [AdminPlanController::class, 'getAddonsByRole'])->name('api.admin.plans.seeker-addons');
        Route::get('/employer/addons', [AdminPlanController::class, 'getAddonsByRole'])->name('api.admin.plans.employer-addons');
    });

    // Form Schema Management (Dynamic Forms)
    Route::prefix('form-schemas')->group(function () {
        // Basic CRUD operations
        Route::get('/', [AdminFormSchemaController::class, 'index'])->name('api.admin.form-schemas.index');
        Route::get('/metadata', [AdminFormSchemaController::class, 'metadata'])->name('api.admin.form-schemas.metadata');
        Route::get('/{id}', [AdminFormSchemaController::class, 'show'])->name('api.admin.form-schemas.show');
        Route::post('/', [AdminFormSchemaController::class, 'store'])->name('api.admin.form-schemas.store');
        Route::put('/{id}', [AdminFormSchemaController::class, 'update'])->name('api.admin.form-schemas.update');
        Route::delete('/{id}', [AdminFormSchemaController::class, 'destroy'])->name('api.admin.form-schemas.destroy');
        
        // Additional operations
        Route::post('/{id}/toggle', [AdminFormSchemaController::class, 'toggleStatus'])->name('api.admin.form-schemas.toggle');
        Route::post('/{id}/duplicate', [AdminFormSchemaController::class, 'duplicate'])->name('api.admin.form-schemas.duplicate');
        Route::post('/reorder', [AdminFormSchemaController::class, 'reorder'])->name('api.admin.form-schemas.reorder');
    });

    // Form Section Management (New Architecture)
    Route::prefix('form-sections')->group(function () {
        Route::get('/', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'index'])->name('api.admin.form-sections.index');
        Route::get('/metadata', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'metadata'])->name('api.admin.form-sections.metadata');
        Route::get('/{id}', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'show'])->name('api.admin.form-sections.show');
        Route::post('/', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'store'])->name('api.admin.form-sections.store');
        Route::put('/{id}', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'update'])->name('api.admin.form-sections.update');
        Route::delete('/{id}', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'destroy'])->name('api.admin.form-sections.destroy');
        Route::post('/{id}/toggle', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'toggleStatus'])->name('api.admin.form-sections.toggle');
        
        // Field management
        Route::get('/{sectionId}/fields', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'getFields'])->name('api.admin.form-sections.fields');
        Route::post('/{sectionId}/fields', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'addField'])->name('api.admin.form-sections.fields.add');
        Route::put('/{sectionId}/fields/{fieldId}', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'updateField'])->name('api.admin.form-sections.fields.update');
        Route::delete('/{sectionId}/fields/{fieldId}', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'deleteField'])->name('api.admin.form-sections.fields.delete');
        Route::post('/{sectionId}/fields/reorder', [\App\Http\Controllers\API\Admin\AdminFormSectionController::class, 'reorderFields'])->name('api.admin.form-sections.fields.reorder');
        
        // Module-specific routes
        Route::get('/module/{module}', [AdminFormSchemaController::class, 'getByModule'])->name('api.admin.form-schemas.module');
        
        // Legacy routes for backward compatibility
        Route::get('/modules', [AdminFormSchemaController::class, 'getModules'])->name('api.admin.forms.modules');
        Route::get('/{module}', [AdminFormSchemaController::class, 'getSchema'])->name('api.admin.forms.schema');
        
        // Section Management
        Route::post('/sections', [AdminFormSchemaController::class, 'createSection'])->name('api.admin.forms.sections.create');
        Route::put('/sections/{section}', [AdminFormSchemaController::class, 'updateSection'])->name('api.admin.forms.sections.update');
        Route::delete('/sections/{section}', [AdminFormSchemaController::class, 'deleteSection'])->name('api.admin.forms.sections.delete');
        Route::post('/sections/reorder', [AdminFormSchemaController::class, 'reorderSections'])->name('api.admin.forms.sections.reorder');
        
        // Field Management
        Route::post('/sections/{section}/fields', [AdminFormSchemaController::class, 'createField'])->name('api.admin.forms.fields.create');
        Route::put('/fields/{field}', [AdminFormSchemaController::class, 'updateField'])->name('api.admin.forms.fields.update');
        Route::delete('/fields/{field}', [AdminFormSchemaController::class, 'deleteField'])->name('api.admin.forms.fields.delete');
        Route::post('/sections/{section}/fields/reorder', [AdminFormSchemaController::class, 'reorderFields'])->name('api.admin.forms.fields.reorder');
    });

    // Activity Logs (Audit Trail)
    Route::prefix('logs')->group(function () {
        Route::get('/', [AdminActivityLogController::class, 'index'])->name('api.admin.logs.index');
        Route::get('/stats', [AdminActivityLogController::class, 'stats'])->name('api.admin.logs.stats');
        Route::get('/log-names', [AdminActivityLogController::class, 'logNames'])->name('api.admin.logs.names');
        Route::get('/event-types', [AdminActivityLogController::class, 'eventTypes'])->name('api.admin.logs.events');
        Route::get('/export', [AdminActivityLogController::class, 'export'])->name('api.admin.logs.export');
        Route::get('/subject', [AdminActivityLogController::class, 'forSubject'])->name('api.admin.logs.subject');
        Route::get('/causer/{userId}', [AdminActivityLogController::class, 'forCauser'])->name('api.admin.logs.causer');
        Route::get('/{id}', [AdminActivityLogController::class, 'show'])->name('api.admin.logs.show');
    });

    // RBAC - Roles & Permissions Management
    Route::middleware('permission:admin.manage_roles')->prefix('access-control')->group(function () {
        // Roles
        Route::get('/roles', [AdminRBACController::class, 'roles'])->name('api.admin.rbac.roles');
        Route::get('/roles/{id}', [AdminRBACController::class, 'showRole'])->name('api.admin.rbac.roles.show');
        Route::post('/roles', [AdminRBACController::class, 'createRole'])->name('api.admin.rbac.roles.create');
        Route::put('/roles/{id}', [AdminRBACController::class, 'updateRole'])->name('api.admin.rbac.roles.update');
        Route::delete('/roles/{id}', [AdminRBACController::class, 'deleteRole'])->name('api.admin.rbac.roles.delete');
        
        // Permissions
        Route::get('/permissions', [AdminRBACController::class, 'permissions'])->name('api.admin.rbac.permissions');
        
        // Staff Management
        Route::middleware('permission:admin.manage_staff')->group(function () {
            Route::get('/staff', [AdminRBACController::class, 'staff'])->name('api.admin.rbac.staff');
            Route::get('/staff/{id}', [AdminRBACController::class, 'showStaff'])->name('api.admin.rbac.staff.show');
            Route::post('/staff', [AdminRBACController::class, 'createStaff'])->name('api.admin.rbac.staff.create');
            Route::put('/staff/{id}', [AdminRBACController::class, 'updateStaff'])->name('api.admin.rbac.staff.update');
            Route::delete('/staff/{id}', [AdminRBACController::class, 'deleteStaff'])->name('api.admin.rbac.staff.delete');
        });
    });

    // Content Management System
    Route::middleware('permission:admin.manage_content')->prefix('content')->group(function () {
        // Site Settings
        Route::get('/settings', [ContentManagementController::class, 'getSiteSettings'])->name('api.admin.content.settings');
        Route::put('/settings', [ContentManagementController::class, 'updateSiteSettings'])->name('api.admin.content.settings.update');
        Route::post('/logo', [ContentManagementController::class, 'uploadLogo'])->name('api.admin.content.logo.upload');
        
        // Pages Management
        Route::get('/pages', [ContentManagementController::class, 'getPages'])->name('api.admin.content.pages');
        Route::post('/pages', [ContentManagementController::class, 'createPage'])->name('api.admin.content.pages.create');
        Route::get('/pages/{page}', [ContentManagementController::class, 'getPage'])->name('api.admin.content.pages.show');
        Route::put('/pages/{page}', [ContentManagementController::class, 'updatePage'])->name('api.admin.content.pages.update');
        Route::delete('/pages/{page}', [ContentManagementController::class, 'deletePage'])->name('api.admin.content.pages.delete');
        
        // Content Blocks
        Route::get('/pages/{page}/blocks', [ContentManagementController::class, 'getContentBlocks'])->name('api.admin.content.blocks');
        Route::post('/pages/{page}/blocks', [ContentManagementController::class, 'createContentBlock'])->name('api.admin.content.blocks.create');
        Route::put('/blocks/{block}', [ContentManagementController::class, 'updateContentBlock'])->name('api.admin.content.blocks.update');
        Route::delete('/blocks/{block}', [ContentManagementController::class, 'deleteContentBlock'])->name('api.admin.content.blocks.delete');
        Route::put('/pages/{page}/blocks/reorder', [ContentManagementController::class, 'reorderContentBlocks'])->name('api.admin.content.blocks.reorder');
        
        // Navigation
        Route::get('/navigation', [ContentManagementController::class, 'getNavigationMenus'])->name('api.admin.content.navigation');
        Route::put('/navigation/{menu}', [ContentManagementController::class, 'updateNavigationMenu'])->name('api.admin.content.navigation.update');
        
        // Utilities
        Route::get('/block-types', [ContentManagementController::class, 'getContentBlockTypes'])->name('api.admin.content.block-types');
        Route::get('/block-types/default', [ContentManagementController::class, 'getDefaultContent'])->name('api.admin.content.block-types.default');
    });

    // User Subscription Management (Admin Override)
    Route::post('/users/{userId}/subscription', [\App\Http\Controllers\API\Admin\AdminUserSubscriptionController::class, 'store'])->name('api.admin.users.subscription.update');
    Route::delete('/users/{userId}/subscription', [\App\Http\Controllers\API\Admin\AdminUserSubscriptionController::class, 'destroy'])->name('api.admin.users.subscription.cancel');
});

// Public Plan Routes - No authentication required
// These routes allow seekers and employers to view available plans
Route::prefix('plans')->group(function () {
    Route::get('/', [PlanController::class, 'index'])->name('api.plans.index');
    Route::get('/{id}', [PlanController::class, 'show'])->name('api.plans.show');
    Route::get('/{role}/subscriptions', [PlanController::class, 'getPlansByRole'])->name('api.plans.role.subscriptions');
    Route::get('/{role}/addons', [PlanController::class, 'getAddonsByRole'])->name('api.plans.role.addons');
});

// Public Branding Endpoint - No authentication required
// Used by login/register pages and frontend for global branding
Route::middleware('throttle:60,1')->prefix('branding')->group(function () {
    Route::get('/', [BrandingController::class, 'index'])->name('api.branding.index');
});

// Public Content Routes - No authentication required
Route::prefix('content')->group(function () {
    Route::get('/settings', [PublicContentController::class, 'getSiteSettings'])->name('api.public.settings');
    Route::get('/pages', [PublicContentController::class, 'getAllPages'])->name('api.public.pages');
    Route::get('/pages/{slug}', [PublicContentController::class, 'getPage'])->name('api.public.pages.show');
    Route::get('/homepage', [PublicContentController::class, 'getHomepage'])->name('api.public.homepage');
    Route::get('/navigation', [PublicContentController::class, 'getNavigation'])->name('api.public.navigation');
});

// System Settings Routes - Public access
Route::prefix('settings')->group(function () {
    Route::get('/branding', [SystemSettingsController::class, 'getBranding'])->name('api.branding');
    Route::get('/', [SystemSettingsController::class, 'getPublicSettings'])->name('api.settings');
});

// DEBUG ROUTE: Public form schema endpoint for testing
Route::get('debug/form-schema/{module}', function($module) {
    $controller = new \App\Http\Controllers\API\FormSchemaController();
    $response = $controller->getSchema($module);
    $data = $response->getData(true);
    
    // Manually resolve options for fields with options_source
    if (isset($data['sections'])) {
        foreach ($data['sections'] as &$section) {
            foreach ($section['fields'] as &$field) {
                if (isset($field['options_source']) && $field['options_source']) {
                    // Get the field model to resolve options
                    $fieldModel = \App\Models\FormField::find($field['id']);
                    if ($fieldModel) {
                        $field['options'] = $fieldModel->getResolvedOptions();
                    }
                }
            }
        }
    }
    
    return response()->json($data);
});

// Billing & Credits Routes
Route::middleware('auth:api')->prefix('billing')->group(function () {
    Route::get('/balance', [BillingController::class, 'getBalance'])->name('api.billing.balance');
    Route::get('/transactions', [BillingController::class, 'getTransactions'])->name('api.billing.transactions');
    Route::post('/checkout', [BillingController::class, 'createCheckoutSession'])->name('api.billing.checkout');
});

// Admin Credit Management
Route::middleware(['auth:api', 'permission:admin.manage_finance'])->prefix('admin/credits')->group(function () {
    Route::get('/', [AdminCreditController::class, 'index'])->name('api.admin.credits.index');
    Route::post('/users/{userId}/adjust', [AdminCreditController::class, 'adjust'])->name('api.admin.credits.adjust');
    Route::post('/users/{userId}/deduct', [AdminCreditController::class, 'deduct'])->name('api.admin.credits.deduct');
});

// SendGrid Email Routes
Route::middleware('auth:api')->prefix('email')->group(function () {
    Route::post('/test', [SendGridController::class, 'sendTestEmail'])->name('api.email.test');
    Route::post('/welcome', [SendGridController::class, 'sendWelcomeEmail'])->name('api.email.welcome');
    Route::post('/password-reset', [SendGridController::class, 'sendPasswordResetEmail'])->name('api.email.password-reset');
    Route::post('/contract-notification', [SendGridController::class, 'sendContractNotification'])->name('api.email.contract-notification');
});
