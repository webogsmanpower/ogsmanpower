<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Cashier;

class BillingController extends Controller
{
    public function __construct(private CreditService $creditService)
    {
    }

    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'credits' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $credits = $request->integer('credits');
        $amount = $credits * 100; // $1 per credit (in cents)

        $session = $user->checkoutCharge($amount, 'Credits Purchase', 1, [
            'metadata' => [
                'type' => 'credits',
                'user_id' => $user->id,
                'credits_amount' => $credits,
            ],
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function getBalance(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'balance' => (float) $user->credit_balance,
            'formatted' => '$' . number_format($user->credit_balance, 2),
        ]);
    }

    public function getTransactions(Request $request)
    {
        $user = Auth::user();

        $transactions = $user->creditTransactions()
            ->latest()
            ->paginate(20);

        return response()->json($transactions);
    }
}
