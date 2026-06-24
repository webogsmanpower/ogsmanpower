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
        // Redirect if the user is authenticated and OTP is already verified

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
                 // Determine the authenticated user's guard
            $authenticatedUser = Auth::user() ?: Auth::guard('admin')->user();
            // dd($authenticatedUser);

            if (!$authenticatedUser) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // Retrieve the user from the model (User or Admin)
            $model = $authenticatedUser instanceof \App\Models\Admin ? \App\Models\Admin::class : \App\Models\User::class;
            $user = $model::findOrFail($request->user_id);

            // Generate a 6-digit OTP and pad with leading zeros if needed
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // Store OTP and expiration
            $user->otp_code = $otp;
            $user->otp_expiry = Carbon::now()->addMinutes(15);
            $user->save();
// dd($user->email);
            // Determine how to send the OTP (email or WhatsApp)
            if ($request->via == 'email') {
                // Send OTP to user's email
                Mail::to($user->email)->send(new SendOTP($otp, $user->name));
            } elseif ($request->via == 'whatsapp') {
                // Send OTP via WhatsApp using a service like Twilio
                $this->sendWhatsAppOTP($user->whatsapp, $otp);
            }

            return response()->json(['success' => true, 'message' => 'OTP sent successfully']);
    }



    public function sendWhatsAppOTP($phoneNumber, $otp)
    {

        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilio = new Client($sid, $token);
        // dd($twilio);
        try {

            $message = $twilio->messages->create(
                "whatsapp:".$phoneNumber, // WhatsApp phone number with the 'whatsapp:' prefix
                [
                    'from' => 'whatsapp:' . env('TWILIO_WHATSAPP_NUMBER'),
                    'body' => "*$otp* is your OGS OTP code. For your security, do not share this."
                ]
            );
            // dd($message);
            return $message;
        } catch (\Twilio\Exceptions\RestException $e) {
            // Log or handle the error
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    public function verifyOTP(Request $request)
    {
        // Retrieve the user using the user_id
        $authenticatedUser = Auth::user() ?: Auth::guard('admin')->user();
            // dd($authenticatedUser);

            if (!$authenticatedUser) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // Retrieve the user from the model (User or Admin)
            $model = $authenticatedUser instanceof \App\Models\Admin ? \App\Models\Admin::class : \App\Models\User::class;
            $user = $model::findOrFail($request->user_id);

        // Check if the entered OTP matches the stored OTP and it's not expired
        if ($user->otp_code === $request->otp && Carbon::now()->lessThanOrEqualTo($user->otp_expiry)) {
            // OTP is valid, clear the OTP and mark the user as verified (if needed)
            $user->otp_code = null;
            $user->otp_expiry = null;
            $user->is_otp_verified = true; // Assuming you have a verification flag in your database
            $user->save();

            return response()->json(['success' => true, 'message' => 'OTP verified successfully']);
        }

        // OTP is invalid or expired
        return response()->json(['success' => false, 'error' => 'Invalid or expired OTP']);
    }
}
