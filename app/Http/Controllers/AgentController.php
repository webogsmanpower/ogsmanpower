<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();
        // dd($user);
        return view('frontend.pages.agent.dashboard', [
            'user' => $user,
        ]);
    }
    public function setting()
    {
        try {
            $user = auth()->user();
            return view('frontend.pages.agent.setting', [
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function settingUpdateInformation(Request $request)
    {
        // Validate the request inputs
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . auth()->id(),
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'whatsapp' => 'required|string|max:15',
            'password' => 'nullable|string|min:8|confirmed',
            'agent' => 'nullable|image|mimes:jpg,png,jpeg,gif,bmp,tif,tiff|max:2048', // Optional image validation
        ]);

        $user = auth()->user(); // Retrieve the currently authenticated user

        // Update the user's details
        $user->name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->whatsapp = $request->whatsapp;

        // Check if a password is provided and update it
        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        // Handle the image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($user->image && file_exists(public_path($user->image))) {
                unlink(public_path($user->image));
            }

            // Upload new image
            $imagePath = $request->file('images')->store('uploads/agents', 'public');
            $user->image = $imagePath;
        }

        // Save the updated user details
        $user->save();

        // Redirect back with success message
        return redirect()->back()->with('success', 'Personal information updated successfully.');
    }
}
