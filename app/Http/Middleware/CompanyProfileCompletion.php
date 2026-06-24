<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CompanyProfileCompletion
{
    public function handle(Request $request, Closure $next)
    {
        $company = currentCompany();

        if (!$company) {
            return redirect()->route('company.setting');
        }

        if (!$company->profile_completion) {

            if (!$request->is('company/account-progress*')) {
                return redirect()->route('company.account-progress');
            }

        }

        return $next($request);
    }
}