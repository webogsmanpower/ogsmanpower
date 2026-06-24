<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\CashierSubscription;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    protected CreditService $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->creditService = $creditService;
    }

    public function handleInvoicePaymentSucceeded($payload)
    {
        parent::handleInvoicePaymentSucceeded($payload);

        $invoice = $payload['data']['object'];
        $subscriptionId = $invoice['subscription'] ?? null;

        if ($subscriptionId) {
            $subscription = CashierSubscription::where('stripe_id', $subscriptionId)->first();
            if ($subscription && $subscription->user) {
                Log::info('Stripe invoice payment succeeded', [
                    'subscription_id' => $subscriptionId,
                    'user_id' => $subscription->user_id,
                    'amount' => $invoice['amount_paid'],
                ]);

                activity()
                    ->performedOn($subscription)
                    ->causedBy($subscription->user)
                    ->log('Subscription payment succeeded via Stripe');
            }
        }
    }

    public function handleInvoicePaymentFailed($payload)
    {
        parent::handleInvoicePaymentFailed($payload);

        $invoice = $payload['data']['object'];
        $subscriptionId = $invoice['subscription'] ?? null;

        if ($subscriptionId) {
            $subscription = CashierSubscription::where('stripe_id', $subscriptionId)->first();
            if ($subscription && $subscription->user) {
                Log::warning('Stripe invoice payment failed', [
                    'subscription_id' => $subscriptionId,
                    'user_id' => $subscription->user_id,
                    'amount_due' => $invoice['amount_due'],
                ]);

                activity()
                    ->performedOn($subscription)
                    ->causedBy($subscription->user)
                    ->log('Subscription payment failed via Stripe');
            }
        }
    }

    public function handleCustomerSubscriptionDeleted($payload)
    {
        parent::handleCustomerSubscriptionDeleted($payload);

        $stripeSubscription = $payload['data']['object'];
        $subscription = CashierSubscription::where('stripe_id', $stripeSubscription['id'])->first();

        if ($subscription && $subscription->user) {
            Log::info('Stripe subscription deleted', [
                'subscription_id' => $stripeSubscription['id'],
                'user_id' => $subscription->user_id,
            ]);

            activity()
                ->performedOn($subscription)
                ->causedBy($subscription->user)
                ->log('Subscription cancelled via Stripe');
        }
    }

    public function handleCheckoutSessionCompleted($payload)
    {
        $session = $payload['data']['object'];

        if (($session['metadata']['type'] ?? null) === 'credits') {
            $userId = $session['metadata']['user_id'] ?? null;
            $amount = (int) ($session['metadata']['credits_amount'] ?? 0);

            if ($userId && $amount > 0) {
                $user = User::find($userId);
                if ($user) {
                    $this->creditService->addCredits(
                        $user,
                        $amount,
                        ['stripe_checkout_session_id' => $session['id']],
                        'stripe',
                        'checkout_session',
                        $session['id']
                    );

                    Log::info('Credits added via Stripe checkout', [
                        'user_id' => $userId,
                        'amount' => $amount,
                        'session_id' => $session['id'],
                    ]);

                    activity()
                        ->performedOn($user)
                        ->causedBy($user)
                        ->withProperties(['amount' => $amount, 'source' => 'stripe_checkout'])
                        ->log('Credits purchased');
                }
            }
        }
    }
}
