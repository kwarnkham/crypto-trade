<?php

namespace App\Observers;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use App\Utility\Encryption;
use Illuminate\Support\Facades\Http;

class DepositOberver
{
    /**
     * Handle the Deposit "created" event.
     */
    public function created(Deposit $deposit): void
    {
        //
    }

    /**
     * Handle the Deposit "updated" event.
     */
    public function updated(Deposit $deposit): void
    {
        if (in_array($deposit->status, [
            DepositStatus::COMPLETED->value,
            DepositStatus::EXPIRED->value
        ])) {
            if ($deposit->user->agent->deposit_callback) {
                Http::get($deposit->user->agent->deposit_callback, [
                    'data' => Encryption::encrypt(json_encode([
                        'id' => $deposit->id,
                        'status' => $deposit->status
                    ]), $deposit->user->agent->aes_key)
                ]);
            }
        }

        if ($deposit->status == DepositStatus::COMPLETED->value) {
            $user = $deposit->user;
            $user->update(['balance' => $user->balance + $deposit->amount]);

            $deposit->balanceLogs()->create([
                'user_id' => $deposit->user_id,
                'amount' => $deposit->amount
            ]);

            $deposit->wallet->updateBalance();
        }
    }

    /**
     * Handle the Deposit "deleted" event.
     */
    public function deleted(Deposit $deposit): void
    {
        //
    }

    /**
     * Handle the Deposit "restored" event.
     */
    public function restored(Deposit $deposit): void
    {
        //
    }

    /**
     * Handle the Deposit "force deleted" event.
     */
    public function forceDeleted(Deposit $deposit): void
    {
        //
    }
}
