<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreditService
{
    public function addCredits(User $user, float $amount, array $meta = [], string $source = 'stripe', ?string $referenceType = null, ?string $referenceId = null): CreditTransaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $meta, $source, $referenceType, $referenceId) {
            $user->refresh();

            $before = (float) $user->credit_balance;
            $after = $before + $amount;

            $user->credit_balance = $after;
            $user->save();

            return CreditTransaction::create([
                'user_id' => $user->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'source' => $source,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'meta' => $meta,
            ]);
        });
    }

    public function deductCredits(User $user, float $amount, array $meta = [], string $source = 'usage', ?string $referenceType = null, ?string $referenceId = null): CreditTransaction
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $meta, $source, $referenceType, $referenceId) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $before = (float) $user->credit_balance;

            if ($before < $amount) {
                throw new InvalidArgumentException('Insufficient credit balance.');
            }

            $after = $before - $amount;
            $user->credit_balance = $after;
            $user->save();

            return CreditTransaction::create([
                'user_id' => $user->id,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'source' => $source,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'meta' => $meta,
            ]);
        });
    }
}
