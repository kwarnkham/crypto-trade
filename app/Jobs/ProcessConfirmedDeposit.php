<?php

namespace App\Jobs;

use App\Enums\DepositStatus;
use App\Models\Deposit;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Attributes\WithoutRelations;

class ProcessConfirmedDeposit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(10);
    }

    /**
     * Create a new job instance.
     */
    public function __construct(public int $depositId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $deposit = Deposit::find($this->depositId);
        $deposit->attemptToComplete();
        if ($deposit->status == DepositStatus::CONFIRMED->value)
            ProcessConfirmedDeposit::dispatch($deposit->id)->delay(now()->addMinute());
    }
}
