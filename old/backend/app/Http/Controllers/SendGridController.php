<?php

namespace App\Http\Controllers;

use App\Services\SendGridService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SendGridController extends Controller
{
    private SendGridService $sendGridService;

    public function __construct(SendGridService $sendGridService)
    {
        $this->sendGridService = $sendGridService;
    }

    /**
     * Send a test email
     */
    public function sendTestEmail(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'to_name' => 'required|string',
        ]);

        $result = $this->sendGridService->sendEmail(
            $request->to,
            $request->to_name,
            'Test Email from OGS App',
            '<h1>Test Email</h1><p>This is a test email from the OGS App using SendGrid.</p>'
        );

        return response()->json($result);
    }

    /**
     * Send welcome email
     */
    public function sendWelcomeEmail(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'to_name' => 'required|string',
        ]);

        $result = $this->sendGridService->sendWelcomeEmail(
            $request->to,
            $request->to_name
        );

        return response()->json($result);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'to_name' => 'required|string',
            'reset_link' => 'required|url',
        ]);

        $result = $this->sendGridService->sendPasswordResetEmail(
            $request->to,
            $request->to_name,
            $request->reset_link
        );

        return response()->json($result);
    }

    /**
     * Send contract notification email
     */
    public function sendContractNotification(Request $request): JsonResponse
    {
        $request->validate([
            'to' => 'required|email',
            'to_name' => 'required|string',
            'contract_link' => 'required|url',
        ]);

        $result = $this->sendGridService->sendContractNotification(
            $request->to,
            $request->to_name,
            $request->contract_link
        );

        return response()->json($result);
    }
}
