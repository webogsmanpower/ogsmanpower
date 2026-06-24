<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCreditController extends Controller
{
    public function __construct(private CreditService $creditService)
    {
    }

    public function index(Request $request)
    {
        $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'type' => 'nullable|in:credit,debit',
        ]);

        $query = CreditTransaction::with('user');

        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        $transactions = $query->latest()->paginate(50);

        return response()->json($transactions);
    }

    public function adjust(Request $request, $userId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($userId);
        $amount = (float) $request->amount;

        $transaction = $this->creditService->addCredits(
            $user,
            $amount,
            [
                'admin_id' => Auth::id(),
                'reason' => $request->reason,
                'adjusted_at' => now()->toIso8601String(),
            ],
            'admin_adjustment'
        );

        activity()
            ->performedOn($user)
            ->causedBy(Auth::user())
            ->withProperties(['amount' => $amount, 'reason' => $request->reason])
            ->log('Admin adjusted credits');

        return response()->json([
            'message' => 'Credits adjusted successfully',
            'transaction' => $transaction->load('user'),
        ]);
    }

    public function deduct(Request $request, $userId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($userId);
        $amount = (float) $request->amount;

        try {
            $transaction = $this->creditService->deductCredits(
                $user,
                $amount,
                [
                    'admin_id' => Auth::id(),
                    'reason' => $request->reason,
                    'deducted_at' => now()->toIso8601String(),
                ],
                'admin_deduction'
            );

            activity()
                ->performedOn($user)
                ->causedBy(Auth::user())
                ->withProperties(['amount' => $amount, 'reason' => $request->reason])
                ->log('Admin deducted credits');

            return response()->json([
                'message' => 'Credits deducted successfully',
                'transaction' => $transaction->load('user'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
