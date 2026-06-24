<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class TwilioConfigController extends Controller
{
    public function create()
    {
        return view('twilio-config');
    }

    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'twilio_sid' => 'required|string',
            'twilio_auth_token' => 'required|string',
            'twilio_whatsapp_number' => 'required|string',
        ]);

        // Store the values in a configuration file or database
        Config::set('twilio.sid', $request->twilio_sid);
        Config::set('twilio.auth_token', $request->twilio_auth_token);
        Config::set('twilio.whatsapp_number', $request->twilio_whatsapp_number);

        // Optionally, save to the database if you want to persist the values
        // Example: UserTwilioConfig::updateOrCreate(['user_id' => auth()->id()], $request->only(['twilio_sid', 'twilio_auth_token', 'twilio_whatsapp_number']));

        return redirect()->back()->with('success', 'Twilio configuration saved successfully!');
    }
}
