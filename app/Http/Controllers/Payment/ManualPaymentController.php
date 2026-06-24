<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Traits\PaymentTrait;
use App\Models\Admin;
use App\Models\BilangualResumeSubscription;
use App\Models\Candidate;
use App\Models\CandidateSubscription;
use App\Models\Company;
use App\Models\Earning;
use App\Models\ManualPayment;
use App\Models\UserPlan;
use App\Notifications\Admin\NewPlanPurchaseNotification;
use App\Notifications\CandidateCreateApprovalPendingNotification;
use App\Notifications\CandidateManualPaymentNotification;
use App\Notifications\Website\Company\PaymentMarkPaidNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Modules\Plan\Entities\Plan;

class ManualPaymentController extends Controller
{
    use PaymentTrait;

    public function paymentPlace(Request $request)
    {
        $job_payment_type = session('job_payment_type') ?? 'package_job';

        if ($job_payment_type == 'per_job') {
            $price = session('job_total_amount') ?? '100';
        } else {
            $plan = Plan::findOrFail($request->plan_id);
            $price = $plan->price;
        }

        $payment = ManualPayment::findOrFail($request->payment_id);
        $usd_amount = currencyConversion($price);

        Earning::create([
            'order_id' => uniqid(),
            'transaction_id' => uniqid('tr_'),
            'payment_provider' => 'offline',
            'plan_id' => $plan->id ?? null,
            'company_id' => currentCompany()->id,
            'amount' => $price,
            // 'currency_symbol' => config('jobpilot.currency_symbol'),
            'currency_symbol' => config('templatecookie.currency_symbol'),
            'usd_amount' => $usd_amount,
            'manual_payment_id' => $payment->id,
            'payment_type' => $job_payment_type == 'per_job' ? 'per_job_based' : 'subscription_based',
        ]);

        if ($job_payment_type == 'per_job') {
            return $this->storeJobData();
        }

        // Session forget
        $this->forgetSessions();

        session()->flash('success', __('payment_is_placed_waiting_for_approval'));

        return redirect()->route('company.plan');
    }

    public function markPaid(Earning $order)
    {
        // payment mark as paid
        $order->update(['payment_status' => 'paid']);

        // update user plan
        $plan = Plan::findOrFail($order->plan_id);
        $user_plan = UserPlan::where('company_id', $order->company_id)->first();
        $company = Company::where('id', $order->company_id)->first();

        if ($user_plan) {
            $user_plan->update([
                'plan_id' => $plan->id,
                'job_limit' => $user_plan->job_limit + $plan->job_limit,
                'featured_job_limit' => $user_plan->featured_job_limit + $plan->featured_job_limit,
                'highlight_job_limit' => $user_plan->highlight_job_limit + $plan->highlight_job_limit,
                'candidate_cv_view_limit' => $user_plan->candidate_cv_view_limit + $plan->candidate_cv_view_limit,
                'candidate_cv_view_limitation' => $plan->candidate_cv_view_limitation,
            ]);
        } else {
            $company->userPlan()->create([
                'plan_id' => $plan->id,
                'job_limit' => $plan->job_limit,
                'featured_job_limit' => $plan->featured_job_limit,
                'highlight_job_limit' => $plan->highlight_job_limit,
                'candidate_cv_view_limit' => $plan->candidate_cv_view_limit,
                'candidate_cv_view_limitation' => $plan->candidate_cv_view_limitation,
            ]);
        }

        $user = $order->company->user;

        // make notification to admins for approved
        if (checkMailConfig()) {
            $admins = Admin::all();
            foreach ($admins as $admin) {
                Notification::send($admin, new NewPlanPurchaseNotification($admin, $order, $plan, $user));
            }
        }

        $order->company->user->notify(new PaymentMarkPaidNotification);

        session()->flash('success', __('payment_is_mark_as_paid'));

        return back();
    }



    public function candidatePaymentPlace(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'candidate_id' => 'required',
            'plan_id' => 'required', // Ensure this corresponds to your plans table
            'duration' => 'required',

        ]);
        // dd($request->all());

        // Retrieve candidate and plan details
        $candidate = Candidate::findOrFail($validated['candidate_id']);
        $planId = $validated['plan_id'];
        $duration = $validated['duration'];

        // Perform manual payment logic
        // For example, create a payment record in the database
        $payment = CandidateSubscription::create([
            'candidate_id' => $candidate->id,
            'candidate_plan_id' => $planId,
            'duration' => $duration,
            'payment_type' => 'manual',
            'status' => 'pending', // Or 'completed' based on your requirement
        ]);
        // $candidate->user->notify(new CandidateManualPaymentNotification($payment));
        return redirect()
            ->route('candidate.plan')

            ->with('success', 'Payment initiated successfully. Please Wait for admin approval');
    }

    public function bilangualPaymentPlace(Request $request)
    {
        // Validate incoming request
        $validated = $request->validate([
            'candidate_id' => 'required',
            'language_code' => 'required',

        ]);
        // dd($request->all());

        // Retrieve candidate and plan details
        $candidate = Candidate::findOrFail($validated['candidate_id']);
        $languageCode = $validated['language_code'];

// dd($language);
        // Perform manual payment logic
        // For example, create a payment record in the database
        $payment = BilangualResumeSubscription::create([
            'candidate_id' => $candidate->id,
            'language_code' => $languageCode,

            'status' => 'pending', // Or 'completed' based on your requirement
            'payment_method' => 'manual',
        ]);
        // $candidate->user->notify(new CandidateManualPaymentNotification($payment));
        return redirect()
        ->route('candidate.view.cv')
        ->with('success','Plan Subscribe Successfully,Please Wait for Admin Approval. ');
    }
}
