<?php

namespace App\Observers;

use App\Enums\WithdrawStatus;
use App\Models\Withdraw;
use App\Utility\Encryption;
use Illuminate\Support\Facades\Http;

class WithdrawObserver
{
    /**
     * Handle the Withdraw "created" event.
     */
    public function created(Withdraw $withdraw): void
    {
        //
    }

    /**
     * Handle the Withdraw "updated" event.
     */
    public function updated(Withdraw $withdraw): void
    {
        if (in_array($withdraw->status, [
            WithdrawStatus::COMPLETED->value,
            WithdrawStatus::CANCELED->value
        ])) {
            if ($withdraw->user->agent->withdraw_callback) {
                Http::get($withdraw->user->agent->withdraw_callback, [
                    'data' => Encryption::encrypt(json_encode([
                        'id' => $withdraw->id,
                        'status' => $withdraw->status
                    ]), $withdraw->user->agent->aes_key)
                ]);
            }
        }

        if ($withdraw->status == WithdrawStatus::COMPLETED->value) {
            $withdraw->balanceLogs()->create([
                'user_id' => $withdraw->user_id,
                'amount' => $withdraw->amount * -1
            ]);
        }
    }

    /**
     * Handle the Withdraw "deleted" event.
     */
    public function deleted(Withdraw $withdraw): void
    {
        //
    }

    /**
     * Handle the Withdraw "restored" event.
     */
    public function restored(Withdraw $withdraw): void
    {
        //
    }

    /**
     * Handle the Withdraw "force deleted" event.
     */
    public function forceDeleted(Withdraw $withdraw): void
    {
        //
    }
}
