<?php

namespace App\Jobs;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessConfirmedDeposit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $depositId)
    {
        //
    }

    public function handle(): void
    {
        $maxAttempts = 5;
        $deposit = Deposit::find($this->depositId);
        $deposit->attemptToComplete();
        if ($deposit->attempts < $maxAttempts)
            ProcessConfirmedDeposit::dispatch($deposit->id)->delay(now()->addMinute());
        else if ($deposit->attempts >= $maxAttempts) {
            $deposit->update(['status' => DepositStatus::EXPIRED->value]);
        }
    }
}
