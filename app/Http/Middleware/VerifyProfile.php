<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class VerifyProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if ($user && !$user->hasRole('superadmin')) {

            if (!$user->is_profile_compeleted) {
                return redirect()
                    ->route('profile.setting')
                    ->withErrors(['verification' => 'Your profile is not complete. Please complete your profile.']);
            }
            $contract = $user->contractAgreement;
            // dd($contract->is_contract_submitted);
            if ($contract && !$contract->is_contract_submitted) {
                return redirect()
                    ->route('contract.form')
                    ->withErrors(['contract' => 'Your contract has not been submitted. Please submit your contract.']);
            }

            if (!$user->is_profile_approved) {
                return redirect()
                    ->route('profile.setting')
                    ->withErrors(['verification' => 'Your profile is not approved. Please wait for admin approval.']);
            }

            if ($contract && !$contract->is_approved) {
                return redirect()
                    ->route('contract.form')
                    ->withErrors(['contract' => 'Your contract is not approved. Please wait for admin approval.']);
            }
        }

        return $next($request);
    }

}
