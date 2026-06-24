<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Traits\PaymentTrait;
use App\Models\BilangualResumeSubscription;
use App\Models\Candidate;
use App\Models\CandidateSubscription;
use Illuminate\Http\Request;
use Stripe\Charge;
use Stripe\Stripe;

class StripeController extends Controller
{
    use PaymentTrait;

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function stripePost(Request $request)
    {
        // Getting payment info from session
        $job_payment_type = session('job_payment_type') ?? 'package_job';

        if ($job_payment_type == 'per_job') {
            $price = session('job_total_amount') ?? '100';
        } else {
            $plan = session('plan');
            $price = $plan->price;
        }

        // Amount conversion
        $converted_amount = currencyConversion($price);

        // Storing payment info in session
        session(['order_payment' => [
            'payment_provider' => 'stripe',
            'amount' => $converted_amount,
            'currency_symbol' => '$',
            'usd_amount' => $converted_amount,
        ]]);

        // Stripe payment process
        try {
            Stripe::setApiKey(config('templatecookie.stripe_secret'));

            if ($job_payment_type == 'per_job') {
                $description = 'Payment for job post in '.config('app.name');
            } else {
                $description = 'Payment for '.$plan->name.' plan purchase'.' in '.config('app.name');
            }

            $charge = Charge::create([
                'amount' => session('stripe_amount'),
                'currency' => 'USD',
                'source' => $request->stripeToken,
                'description' => $description,
            ]);

            session(['transaction_id' => $charge->id ?? null]);
            $this->orderPlacing();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function candidateStripe(Request $request)
    {

    // Stripe payment process
try {
    $validated = $request->validate([
        'candidate_id' => 'required',
        'plan_id' => 'required', // Ensure this corresponds to your plans table
        'duration' => 'required',
    ]);

    // dd($request->all());
    Stripe::setApiKey(config('templatecookie.stripe_secret'));

    // Create a charge with Stripe
    $charge = Charge::create([
        'amount' => $request->amount*100, // Ensure this session value is set correctly
        'currency' => 'USD',
        'source' => $request->stripeToken,
        'description' => 'Candidate feature plan',
    ]);

    // Retrieve candidate and validated details
    $candidate = Candidate::findOrFail($validated['candidate_id']);
    $planId = $validated['plan_id'];
    $duration = $validated['duration'];

    // Create a subscription record in the database
    $payment = CandidateSubscription::create([
        'candidate_id' => $candidate->id,
        'candidate_plan_id' => $planId,
        'duration' => $duration,
        'payment_type' => 'online',
        'status' => 'approved', // Or 'completed' based on your requirement
    ]);

    // Update the candidate to mark as featured
    $candidate->update([
        'is_candidate_featured' => '1', // Assuming this column exists and is a boolean or integer
    ]);

    return redirect()
            ->route('candidate.plan')
            ->with('success','Plan Subscribe Successfully. ');

} catch (\Exception $e) {
    // Handle exceptions and errors
    return redirect()->back()->with('error', __('Payment processing failed. Please try again.'));
}
    }

    public function bilangualStripe(Request $request)
    {

try {

    $validated = $request->validate([
        'candidate_id' => 'required',
        'language_code' => 'required',
       

    ]);


    Stripe::setApiKey(config('templatecookie.stripe_secret'));

    // Create a charge with Stripe
    $charge = Charge::create([
        'amount' => $request->amount*100, // Ensure this session value is set correctly
        'currency' => 'USD',
        'source' => $request->stripeToken,
        'description' => 'Bilangaul Resume Downlaod',
    ]);

    // Retrieve candidate and validated details
    $candidate = Candidate::findOrFail($validated['candidate_id']);
    $languageCode = $validated['language_code'];


    $payment = BilangualResumeSubscription::create([
        'candidate_id' => $candidate->id,
        'language_code' => $languageCode,

        'status' => 'approved', // Or 'completed' based on your requirement
        'payment_method'=>'online',
    ]);

    return redirect()
            ->route('candidate.view.cv')
            ->with('success','Plan Subscribe Successfully. ');

} catch (\Exception $e) {

    // Handle exceptions and errors
    return redirect()->back()->with('error', __('Payment processing failed. Please try again.'));
}
    }
}
