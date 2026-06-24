<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Http\Traits\PaymentTrait;
use Mollie\Laravel\Facades\Mollie;

class MollieController extends Controller
{
    use PaymentTrait;

    /**
     * Redirect the user to the Payment Gateway.
     *
     * @return Response
     */
    public function preparePayment()
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
        $converted_amount = usdAmount($price);
        $amount = currencyConvert($price, 'EUR', 2); // Ensure rounding to 2 decimal places

        // Storing payment info in session
        session(['order_payment' => [
            'payment_provider' => 'mollie',
            'amount' => $amount,
            'currency_symbol' => '€',
            'usd_amount' => $converted_amount,
        ]]);

        // Format the amount correctly
        $formatted_amount = number_format($amount, 2, '.', '');

        $payment = Mollie::api()->payments()->create([
            'amount' => [
                'currency' => 'EUR', // Type of currency you want to send
                'value' => $formatted_amount, // Formatted amount with 2 decimal places
            ],
            'description' => 'Payment By '.authUser()->name,
            'redirectUrl' => route('mollie.success'), // after the payment completion where you to redirect
        ]);

        $payment = Mollie::api()->payments()->get($payment->id);

        session(['transaction_id' => $payment->id ?? null]);

        // Redirect customer to Mollie checkout page
        return redirect($payment->getCheckoutUrl(), 303);
    }

    /**
     * Page redirection after the successfull payment
     *
     * @return Response
     */
    public function paymentSuccess()
    {
        $this->orderPlacing();
    }
}
