<?php

namespace App\Http\Controllers\Website;

use Illuminate\Support\Facades\Mail;
use App\Mail\SendOTP;
use App\Models\User;
use Carbon\Carbon;
use Twilio\Rest\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OTPController extends Controller
{
    public function showVerifyForm()
    {
        if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->is_otp_verified) {
            return redirect()->route('admin.dashboard');
        }

        if (Auth::check() && Auth::user()->is_otp_verified) {
            return redirect()->route('user.dashboard');
        }

        return view('frontend.auth.otp');
    }

    public function sendOTP(Request $request)
    {
        $authenticatedUser = Auth::user() ?: Auth::guard('admin')->user();

        if (!$authenticatedUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Always use the authenticated user — never trust user_id from request
        $user = $authenticatedUser;

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->otp_code = $otp;
        $user->otp_expiry = Carbon::now()->addMinutes(15);
        $user->save();

        if ($request->via == 'email') {
            Mail::to($user->email)->send(new SendOTP($otp, $user->name));
        } elseif ($request->via == 'whatsapp') {
            $this->sendWhatsAppOTP($user->whatsapp, $otp);
        }

        return response()->json(['success' => true, 'message' => 'OTP sent successfully']);
    }

    public function sendWhatsAppOTP($phoneNumber, $otp)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);

        try {
            $message = $twilio->messages->create(
                'whatsapp:' . $phoneNumber,
                [
                    'from' => 'whatsapp:' . env('TWILIO_WHATSAPP_NUMBER'),
                    'body' => "*$otp* is your OGS OTP code. For your security, do not share this.",
                ]
            );

            return $message;
        } catch (\Twilio\Exceptions\RestException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function verifyOTP(Request $request)
    {
        $authenticatedUser = Auth::user() ?: Auth::guard('admin')->user();

        if (!$authenticatedUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Always use the authenticated user — never trust user_id from request
        $user = $authenticatedUser;

        if ($user->otp_code === $request->otp && Carbon::now()->lessThanOrEqualTo($user->otp_expiry)) {
            $user->otp_code = null;
            $user->otp_expiry = null;
            $user->is_otp_verified = true;
            $user->save();

            return response()->json(['success' => true, 'message' => 'OTP verified successfully']);
        }

        return response()->json(['success' => false, 'error' => 'Invalid or expired OTP']);
    }
}
